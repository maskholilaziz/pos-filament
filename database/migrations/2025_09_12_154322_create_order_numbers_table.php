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
        Schema::create('order_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('number_label')->unique(); // e.g., "11", "12", "A5"
            $table->string('status')->default('available'); // available, in_use
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_numbers');
    }
};
