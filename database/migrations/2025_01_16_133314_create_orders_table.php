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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('currency_pair'); // change this to string to match `pair` in `market_data`
            $table->string('order_type');
            $table->decimal('amount', 15, 2);
            $table->decimal('price', 15, 2);
            $table->string('status');
            $table->timestamps();

            $table->foreign('currency_pair')->references('pair')->on('market_data')->onDelete('cascade');
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
