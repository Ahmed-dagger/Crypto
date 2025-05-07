<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTakenAmountToP2pTable extends Migration
{
    public function up()
    {
        Schema::table('p2p', function (Blueprint $table) {
            $table->decimal('taken_amount', 20, 8)->nullable()->after('fiat_amount');
        });
    }

    public function down()
    {
        Schema::table('p2p', function (Blueprint $table) {
            $table->dropColumn('taken_amount');
        });
    }
}

