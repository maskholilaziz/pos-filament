<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Menyimpan opsi terpilih dalam format JSON
            $table->json('selected_options')->nullable()->after('total_price');
            // Menyimpan catatan custom
            $table->text('notes')->nullable()->after('selected_options');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['selected_options', 'notes']);
        });
    }
};
