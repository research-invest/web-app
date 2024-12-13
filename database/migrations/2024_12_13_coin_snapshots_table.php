<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('top_performing_coin_snapshots', function (Blueprint $table) {
            $table->double('price')->nullable();
        });
//
        Schema::table('trade_periods', function (Blueprint $table) {
            $table->integer('daily_target')->default(100);
            $table->integer('weekend_target')->default(50);
            $table->boolean('is_active')->default(false)->change();

        });
    }

    public function down(): void
    {
        Schema::dropColumns('top_performing_coin_snapshots', [
            'price'
        ]);
        Schema::dropColumns('trade_periods', [
            'daily_target',
            'weekend_target',
        ]);
    }
};
