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
        Schema::create('p2p', function (Blueprint $table) {
            $table->id();

            // The user who created the offer or trade
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // If it's an offer between two users
            $table->foreignId('counterparty_id')->nullable()->constrained('users')->onDelete('set null');

            // Type of action: buy or sell
            $table->enum('trade_type', ['buy', 'sell']);

            // Coin/currency involved (e.g., BTC, USDT)
            $table->string('currency');

            // Amount of the coin being traded
            $table->decimal('amount', 20, 8);

            // Total fiat value for the trade
            $table->decimal('fiat_amount', 20, 2);

            // Fiat currency (e.g., USD, EUR)
            $table->string('fiat_currency');

            // Preferred payment method (bank, PayPal, etc.)
            $table->string('payment_method');

            // Trade status
            $table->enum('transfer_status', ['pending', 'in_progress', 'completed', 'cancelled', 'disputed'])->default('pending');

            // Optional note or reference

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('userads');
    }
};
