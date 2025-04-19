<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->json('diff_percent_history')
                ->nullable();
            $table->float('last_price')
                ->default(0);
            $table->float('last_volume')
                ->default(0);
            $table->tinyInteger('visible_type')
                ->comment('Вид наблюдения за кошельком')
                ->nullable();
            $table->tinyInteger('label_type')
                ->comment('Тип метки')
                ->after('label')
                ->nullable();

        });

        Schema::table('wallet_balances', function (Blueprint $table) {
            $table->float('price')
                ->default(0);
            $table->float('volume')
                ->default(0);
        });

        Schema::create('wallet_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained();
            $table->float('whale_score')->nullable();
            $table->integer('momentum')->nullable();
            $table->float('correlation')->nullable();
            $table->float('smart_index')->nullable();
            $table->float('stability')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wallet_metrics');
        Schema::dropColumns('wallets', [
            'visible_type',
            'label_type',
            'last_price',
            'last_volume',
            'diff_percent_history',
        ]);
        Schema::dropColumns('wallet_balances', [
            'price',
            'volume',
        ]);
    }
};
