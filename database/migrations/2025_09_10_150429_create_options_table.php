<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_group_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Contoh: "Panas", "Dingin", "Telur"
            $table->decimal('price', 15, 2)->default(0); // Harga tambahan untuk opsi ini
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
