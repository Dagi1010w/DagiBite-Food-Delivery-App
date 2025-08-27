<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $t) {
      $t->id();
      $t->foreignId('order_id')->nullable()->constrained()->nullOnDelete(); // if you have orders
      $t->string('tx_ref')->unique();
      $t->string('ref_id')->nullable();       // Chapa’s internal ID (set after verify)
      $t->decimal('amount', 12, 2);
      $t->string('currency', 3)->default('ETB');
      $t->string('status')->default('pending'); // pending|success|failed
      $t->text('checkout_url')->nullable();
      $t->timestamp('paid_at')->nullable();
      $t->json('meta')->nullable();           // store payloads/responses
      $t->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
