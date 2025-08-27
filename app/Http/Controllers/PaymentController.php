<?php
// app/Http/Controllers/PaymentController.php
namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * 1) Create Chapa checkout session (hosted payment page)
     */
    public function initialize(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        try {
            Log::info('Payment initialization started', ['order_id' => $request->order_id]);
            $order = Order::with('user')->findOrFail($request->order_id);
            
            // Check if order already has a successful payment
            if ($order->payments()->where('status', 'success')->exists()) {
                return back()->withErrors([
                    'payment' => 'This order has already been paid.'
                ]);
            }

            // Create payment record
            $tx_ref = 'tx-' . uniqid();
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total,
                'currency' => 'ETB',
                'tx_ref' => $tx_ref,
                'status' => 'pending',
            ]);

            // Get user details
            $user = $order->user;
            $email = $user ? $user->email : (Auth::user()->email ?? 'customer@example.com');
            $firstName = $user ? $user->name : (Auth::user()->name ?? 'Customer');

            $chapaKey = config('services.chapa.secret_key');
            Log::info('Chapa key loaded', ['key_exists' => !empty($chapaKey), 'key_length' => strlen($chapaKey)]);
            
            if (!$chapaKey || $chapaKey === 'your_chapa_secret_key_here') {
                Log::error('Chapa secret key not configured or using placeholder', ['key' => $chapaKey]);
                $payment->update(['status' => 'failed']);
                return back()->withErrors([
                    'payment' => 'Payment service not configured. Please add your Chapa secret key to .env file.'
                ]);
            }

            $response = Http::withToken($chapaKey)
                ->post('https://api.chapa.co/v1/transaction/initialize', [
                    'amount'       => (string) $order->total,
                    'currency'     => 'ETB',
                    'email'        => $email,
                    'first_name'   => $firstName,
                    'last_name'    => 'User', 
                    'tx_ref'       => $tx_ref,
                    'callback_url' => route('payments.callback'),
                    'return_url'   => route('payments.callback'),
                    'customization'=> [
                        'title'       => 'Food Delivery Payment',
                        'description' => 'Payment for order #' . $order->id,
                    ],
                ]);

            if (!$response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? $response->body();
                
                Log::error('Chapa Init Failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'order_id' => $order->id,
                    'amount' => $order->total
                ]);
                
                // Update payment status
                $payment->update(['status' => 'failed']);
                
                return back()->withErrors([
                    'payment' => 'Payment initialization failed: ' . $errorMessage
                ]);
            }

            $data = $response->json();

            // Check if we have a checkout URL
            if (!isset($data['data']['checkout_url'])) {
                Log::error('Chapa response missing checkout_url', ['response' => $data]);
                $payment->update(['status' => 'failed']);
                return back()->withErrors([
                    'payment' => 'Invalid response from payment provider.'
                ]);
            }

            // Instead of returning the URL as a prop, redirect to it
            return redirect()->away($data['data']['checkout_url']);
            
        } catch (\Exception $e) {
            Log::error('Payment initialization error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors([
                'payment' => 'An error occurred while processing your payment. Please try again.'
            ]);
        }
    }


    /**
     * 2) Callback after payment UI (must verify on your server)
     * Chapa may send query/body like: { trx_ref, ref_id, status }
     */
    public function callback(Request $request)
    {
        // Get tx_ref from common fields
        $txRef = $request->query('trx_ref')
            ?? $request->input('trx_ref')
            ?? $request->query('tx_ref')
            ?? $request->input('tx_ref');

        if (!$txRef) {
            return redirect()->route('home')->withErrors('Missing transaction reference.');
        }

        // Always verify with Chapa per docs
        $verify = Http::withToken(config('services.chapa.secret_key'))
            ->acceptJson()
            ->get("https://api.chapa.co/v1/transaction/verify/{$txRef}");

        if (!$verify->ok()) {
            return redirect()->route('home')->withErrors('Could not verify payment.');
        }

        $v = $verify->json();

        // Success path
        if (($v['status'] ?? null) === 'success' && ($v['data']['status'] ?? null) === 'success') {

            $payment = Payment::where('tx_ref', $txRef)->first();
            if (!$payment) {
                // create record if you support paying without pre-created Payment
                $payment = Payment::create([
                    'tx_ref' => $txRef,
                    'amount' => $v['data']['amount'] ?? 0,
                    'currency'=> $v['data']['currency'] ?? 'ETB',
                    'status'  => 'pending',
                ]);
            }

            // Guard: match amount/currency you expect
            $amountMatch = (string)$payment->amount === (string)($v['data']['amount'] ?? '');
            $currencyMatch = strtoupper($payment->currency) === strtoupper($v['data']['currency'] ?? '');

            if ($amountMatch && $currencyMatch) {
                DB::transaction(function () use ($payment, $v) {
                    if ($payment->status !== 'success') {
                        $payment->update([
                            'status'  => 'success',
                            'ref_id'  => $v['data']['reference'] ?? $v['data']['ref_id'] ?? null,
                            'paid_at' => Carbon::now(),
                            'meta'    => $v['data'] ?? [],
                        ]);
                        // If you have orders:
                        $payment->order?->update(['status' => 'paid']);
                    }
                });
            }
        }

        // Send customer to order or receipt page
        if ($orderId = optional($payment)->order_id) {
            return redirect()->route('orders.show', $orderId)
                ->with('success', 'Payment successful');
        }

        return redirect()->route('home')->with('success', 'Payment verified');
    }

    /**
     * 3) Webhook (reliable server-to-server confirmation)
     * Verifies HMAC signature per Chapa Merchant Handbook.
     */
    public function webhook(Request $request)
    {
        $raw = $request->getContent();
        $secret = (string) env('CHAPA_WEBHOOK_SECRET');

        // Support both headers: 'Chapa-Signature' and 'x-chapa-signature'
        $sigHeaderA = $request->header('Chapa-Signature');
        $sigHeaderB = $request->header('x-chapa-signature');

        // Compute HMAC-SHA256 over the raw body (as shown in their example)
        $computed = hash_hmac('sha256', $raw, $secret);

        if (!hash_equals((string)$computed, (string)$sigHeaderA)
            && !hash_equals((string)$computed, (string)$sigHeaderB)) {
            Log::warning('Invalid Chapa webhook signature');
            return response('invalid signature', 401);
        }

        $event = $request->json('event') ?? 'payment.completed';
        $data  = $request->json('data', []);

        if (($data['tx_ref'] ?? null) && ($data['status'] ?? null) === 'success') {
            $payment = Payment::where('tx_ref', $data['tx_ref'])->first();
            if ($payment && $payment->status !== 'success') {
                DB::transaction(function () use ($payment, $data) {
                    $payment->update([
                        'status'  => 'success',
                        'ref_id'  => $data['reference'] ?? $data['ref_id'] ?? null,
                        'paid_at' => Carbon::parse($data['charged_at'] ?? now()),
                        'meta'    => $data,
                    ]);
                    $payment->order?->update(['status' => 'paid']);
                });
            }
        }

        return response()->json(['received' => true]);
    }
}
