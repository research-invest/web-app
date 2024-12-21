<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->double('open_currency_volume')
                ->default(0);
            $table->double('close_currency_volume')
                ->default(0);
        });

        Schema::table('currencies', function (Blueprint $table) {
            $table->double('volume')
                ->default(0);
        });
    }

    public function down()
    {
        Schema::dropColumns('trades', [
            'open_currency_volume',
            'close_currency_volume',
        ]);
        Schema::dropColumns('currencies', [
            'volume',
        ]);
    }
};
