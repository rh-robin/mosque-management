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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('stripe_price_id',200)->nullable();
            $table->text('stripe_product_id',200)->nullable();
            $table->string('name',200);
            $table->double('price',10.2)->nullable();
            $table->enum('type',['medium','premium'])->nullable();  
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
