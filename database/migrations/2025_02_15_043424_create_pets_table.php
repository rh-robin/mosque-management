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
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('breed_id')->nullable();

            $table->string('name');
            $table->enum('category', ['dog', 'cat']);
            $table->date('d_o_b')->nullable();
            $table->enum('gender', ['male', 'female']);
            //$table->integer('age')->nullable();
            $table->float('weight')->nullable();
            $table->float('weight_goal')->nullable();
            $table->float('height')->nullable();
            $table->text('additional_note')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();

            // Define foreign keys separately
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('breed_id')->references('id')->on('breeds')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};
