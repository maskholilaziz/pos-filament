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
            $table->string('invoice_number')->unique();
            $table->foreignId('user_id')->comment('Kasir yang melayani')->constrained('users');
            $table->string('customer_name')->default('Pelanggan');
            $table->decimal('total_price', 15, 2);
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('change', 15, 2);
            $table->enum('status', ['paid', 'pending', 'cancelled'])->default('paid');
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
