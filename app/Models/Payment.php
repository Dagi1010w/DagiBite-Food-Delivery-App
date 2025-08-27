<?php
// app/Models/Payment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id','tx_ref','ref_id','amount','currency','status','checkout_url','paid_at','meta'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'meta'    => 'array',
    ];

    public function order() { return $this->belongsTo(\App\Models\Order::class); }
}
