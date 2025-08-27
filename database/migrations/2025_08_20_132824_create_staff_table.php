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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->date('start_date');
            $table->string('emergency_contact')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active_today')->default(false);
            $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
