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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('futures_wallet_id')->constrained('future_wallets')->onDelete('cascade');
            $table->string('currency');
            $table->enum('direction', ['long', 'short']);
            $table->decimal('entry_price', 20, 8);
            $table->decimal('size', 20, 8); // total position size
            $table->decimal('leverage', 5, 2);
            $table->decimal('margin', 20, 8); // initial margin
            $table->decimal('unrealized_pnl', 20, 8)->default(0);
            $table->boolean('is_open')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
