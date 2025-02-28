<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('trade_pnl_history', function (Blueprint $table) {
            $table->double('volume_btc')
                ->default(0);
            $table->double('volume_eth')
                ->default(0);
     });
    }

    public function down()
    {
        Schema::dropColumns('trade_pnl_history', [
            'volume_btc',
            'volume_eth',
        ]);
    }
};
