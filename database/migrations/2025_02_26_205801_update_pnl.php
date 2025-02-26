<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('trade_pnl_history', function (Blueprint $table) {
            $table->double('volume')
                ->default(0);
            $table->decimal('funding_rate', 10, 8)
                ->nullable()
                ->default(0);
     });
    }

    public function down()
    {
        Schema::dropColumns('trade_pnl_history', [
            'volume',
            'funding_rate',
        ]);
    }
};
