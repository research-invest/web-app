<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gate_testnet_api_key')->nullable();
            $table->string('gate_testnet_secret_key')->nullable();

            $table->string('bybit_testnet_api_key')->nullable();
            $table->string('bybit_testnet_secret_key')->nullable();

            $table->string('binance_testnet_api_key')->nullable();
            $table->string('binance_testnet_secret_key')->nullable();
        });

        Schema::table('funding_deals_config', function (Blueprint $table) {
            $table->boolean('is_testnet')->default(false);
        });

        Schema::table('funding_deals', function (Blueprint $table) {
            $table->string('comment')->nullable();
            $table->mediumText('error')->nullable();
        });

        Schema::table('trades', function (Blueprint $table) {
            $table->string('exchange')->default('bybit');
        });
    }

    public function down()
    {
        Schema::dropColumns('users', [
            'gate_testnet_secret_key',
            'gate_testnet_api_key',
            'bybit_testnet_secret_key',
            'bybit_testnet_api_key',
            'binance_testnet_secret_key',
            'binance_testnet_api_key',
        ]);
        Schema::dropColumns('funding_deals_config', [
            'is_testnet',
        ]);
        Schema::dropColumns('funding_deals', [
            'comment',
            'error',
        ]);
        Schema::dropColumns('trades', [
            'exchange',
        ]);
    }
};
