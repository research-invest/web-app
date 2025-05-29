<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->string('source_price')
                ->index()
                ->default(\App\Models\Currency::SOURCE_PRICE_SELLL);

            $table->string('coingecko_code')
                ->nullable();

            $table->string('binance_code')
                ->nullable();
        });
    }

    public function down()
    {
        Schema::dropColumns('currencies', [
            'source_price',
            'coingecko_code',
            'binance_code',
        ]);
    }
};
