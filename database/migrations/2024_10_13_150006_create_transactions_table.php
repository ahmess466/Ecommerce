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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->bigInteger('user_id')->unsigned(); // Foreign key to users table
            $table->bigInteger('order_id')->unsigned(); // Foreign key to orders table
            $table->enum('mode', ['cod', 'card', 'paypal']); // Payment modes
            $table->enum('status', ['pending', 'approved', 'declined', 'refunded'])->default('pending'); // Transaction status
            $table->timestamps(); // Timestamps
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade'); // Foreign key constraint on user_id
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade'); // Foreign key constraint on order_id

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
