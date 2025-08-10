<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trading_view_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 50)->index(); // BTC/USDT, ETH/USDT etc.
            $table->string('action', 20)->index(); // buy, sell, close, alert
            $table->string('strategy', 100)->nullable()->index(); // название стратегии
            $table->decimal('price', 20, 8)->nullable(); // цена в момент сигнала
            $table->string('timeframe', 10)->nullable(); // 1m, 5m, 1h, 1d etc.
            $table->string('exchange', 50)->nullable(); // binance, bybit etc.
            $table->json('raw_data'); // полные данные вебхука
            $table->string('source_ip', 45)->nullable(); // IP адрес источника
            $table->text('user_agent')->nullable(); // User Agent
            $table->timestamps();
            $table->softDeletes();

            // Индексы для быстрого поиска
            $table->index(['symbol', 'action']);
            $table->index(['strategy', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trading_view_webhooks');
    }
};
