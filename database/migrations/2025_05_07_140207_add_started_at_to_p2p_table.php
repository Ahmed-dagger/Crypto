<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('p2p', function (Blueprint $table) {
        $table->timestamp('started_at')->nullable()->after('taken_amount');
    });
}

public function down()
{
    Schema::table('p2p', function (Blueprint $table) {
        $table->dropColumn('started_at');
    });
}
};
