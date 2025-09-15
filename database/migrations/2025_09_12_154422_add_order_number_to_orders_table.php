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
        Schema::table('orders', function (Blueprint $table) {
            // ID dari nomor pesanan yang sedang digunakan (tongkat nomor)
            $table->foreignId('order_number_id')->nullable()->constrained()->after('id');

            // Ubah status order untuk melacak status pengantaran
            // preparing, partially_served, served, completed
            $table->string('status')->default('preparing')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
