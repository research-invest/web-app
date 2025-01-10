<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->string('tradingview_code')
                ->after('code')
                ->nullable();
        });

//        $codes = [
//            '' => '0UqI7O2n',
//            '' => '0UqI7O2n',
//        ];
//
//        foreach ($codes as $currecnryCode => $code) {
//
//        }

        Schema::rename('strategies', 'trade_strategies');

        //check_list_items - пункты шаблона чек-листа
        Schema::create('check_list_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('trade_strategy_id')->nullable()
                ->constrained('trade_strategies')
                ->cascadeOnDelete();

            $table->foreignId('user_id')->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('priority')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

//        platform.trading.check-list
//            platform.trading.check-item.create
//            platform.trading.check-item.edit

        //trade_check_list_items - выполненные пункты чек-листа для конкретной сделки
        Schema::create('trade_check_list_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('trade_id')
                ->constrained('trades')->cascadeOnDelete();

            $table->foreignId('check_list_item_id')
                ->constrained('check_list_items')
                ->cascadeOnDelete();

            $table->boolean('is_completed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::rename('trade_strategies', 'strategies');

        Schema::dropColumns('currencies', [
            'tradingview_code',
        ]);

        Schema::dropIfExists('trade_check_list_items');
        Schema::dropIfExists('check_list_items');

    }
};
