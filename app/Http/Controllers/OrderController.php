<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function create()
    {
        return Inertia::render('Customer/CheckoutPage');
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'total' => 'required|numeric|min:0.01',
            'restaurant_id' => 'required|exists:restaurants,id',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
        ]);

        $order = Order::create([
            'user_id' => Auth::id(),
            'restaurant_id' => $request->restaurant_id,
            'items' => $request->items,
            'total' => $request->total,
            'status' => 'pending',
            'address' => $request->address,
            'phone' => $request->phone,
        ]);

        return redirect()->route('payment.page', ['orderId' => $order->id]);
    }

    public function payment($orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return Inertia::render('Customer/PaymentPage', [
            'order' => $order,
        ]);
    }

    public function confirm(Request $request, $orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'payment_method' => 'required|in:telebirr,cbe_birr,cbe',
            'transaction_id' => 'required|string|max:50',
        ]);

        $order->update([
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'status' => 'paid', // Or 'pending_verification' if manual
        ]);

        return redirect()->route('user.orders')->with('success', 'Payment submitted! Restaurant will confirm soon.');
    }
}