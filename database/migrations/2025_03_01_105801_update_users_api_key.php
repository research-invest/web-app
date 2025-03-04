<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bybit_secret_key')->nullable();
            $table->string('bybit_api_key')->nullable();

            $table->string('mexc_secret_key')->nullable();
            $table->string('mexc_api_key')->nullable();

            $table->string('binance_secret_key')->nullable();
            $table->string('binance_api_key')->nullable();

            $table->string('bigx_secret_key')->nullable();
            $table->string('bigx_api_key')->nullable();
        });
    }

    public function down()
    {
        Schema::dropColumns('users', [
            'bybit_secret_key',
            'bybit_api_key',

            'mexc_secret_key',
            'mexc_api_key',

            'binance_secret_key',
            'binance_api_key',

            'bigx_secret_key',
            'bigx_api_key',
        ]);
    }
};
