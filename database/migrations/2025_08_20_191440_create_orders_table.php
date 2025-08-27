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
       Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->decimal('total', 10, 2);
            $table->json('items'); // Cart items as JSON
            $table->string('status')->default('pending'); // pending, paid, failed, delivered
            $table->string('payment_method')->nullable(); // telebirr, cbe_birr, cbe
            $table->string('transaction_id')->nullable(); // Reference number
            $table->string('phone')->nullable(); // For payment verification
            $table->text('address'); // Delivery address
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
