<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('currencies_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('currency_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');

            $table->string('coin_id')->index()->nullable(); // "bitcoin"
            $table->string('symbol');  // "btc"
            $table->string('name');    // "Bitcoin"
            $table->string('source');    // "coingecko"

            $table->decimal('current_price', 20, 8);
            $table->decimal('market_cap', 30, 0)->nullable();
            $table->integer('market_cap_rank')->nullable();
            $table->decimal('total_volume', 30, 0)->nullable();

            $table->decimal('price_change_24h', 20, 8)->nullable();
            $table->decimal('price_change_percentage_24h', 10, 4)->nullable();

            $table->decimal('circulating_supply', 30, 8)->nullable();
            $table->decimal('total_supply', 30, 8)->nullable();
            $table->decimal('max_supply', 30, 8)->nullable();

            $table->decimal('ath', 20, 8)->nullable();
            $table->decimal('atl', 20, 8)->nullable();

            $table->decimal('price_btc', 20, 8)->nullable();
            $table->decimal('price_eth', 20, 8)->nullable();

            $table->decimal('volume_btc', 20, 8)->nullable();
            $table->decimal('volume_eth', 20, 8)->nullable();

            $table->decimal('price_change_vs_btc_24h', 10, 4)->nullable();
            $table->decimal('price_change_vs_eth_24h', 10, 4)->nullable();

            $table->decimal('price_change_vs_btc_12h', 10, 4)->nullable();
            $table->decimal('price_change_vs_eth_12h', 10, 4)->nullable();

            $table->decimal('price_change_vs_btc_4h', 10, 4)->nullable();
            $table->decimal('price_change_vs_eth_4h', 10, 4)->nullable();

            $table->decimal('volume_change_vs_btc_24h', 10, 4)->nullable();
            $table->decimal('volume_change_vs_eth_24h', 10, 4)->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('currencies_prices');
    }
};
