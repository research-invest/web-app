<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->decimal('commission_open')
                ->comment('Комиссия за открытие')
                ->default(0);
            $table->decimal('commission_close')
                ->comment('Комиссия за закрытие')
                ->default(0);
            $table->decimal('commission_finance')
                ->comment('Комиссия за финансирование')
                ->default(0);
        });

        Schema::table('trade_periods', function (Blueprint $table) {
            $table->decimal('deposit')
                ->default(2000);
        });
    }

    public function down()
    {
        Schema::dropColumns('trades', [
            'commission_open',
            'commission_close',
            'commission_finance',
        ]);
        Schema::dropColumns('trade_periods', [
            'deposit',
        ]);
    }
};
