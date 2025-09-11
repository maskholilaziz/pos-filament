<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Contoh: "Suhu", "Add-on"
            $table->enum('type', ['radio', 'checkbox'])->default('radio'); // radio: pilih satu, checkbox: pilih banyak
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('option_groups');
    }
};
