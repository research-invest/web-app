<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->double('start_volume_1h')
                ->default(0);
            $table->double('start_volume_4h')
                ->default(0);
            $table->double('start_volume_24h')
                ->default(0);
            $table->double('start_price_1h')
                ->default(0);
            $table->double('start_price_4h')
                ->default(0);
            $table->double('start_price_24h')
                ->default(0);
        });


        Schema::table('trades', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->default(1)->index();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('trade_periods', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->default(1)->index();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropColumns('currencies', [
            'is_active',
            'start_volume_1h',
            'start_volume_4h',
            'start_volume_24h',
            'start_price_1h',
            'start_price_4h',
            'start_price_24h',
        ]);
        Schema::dropColumns('trades', [
            'user_id',
        ]);
        Schema::dropColumns('trade_periods', [
            'user_id',
        ]);
    }
};
