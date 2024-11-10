<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('trade_orders', function (Blueprint $table) {
            $table->decimal('unrealized_pnl', 16, 8)->nullable();
            $table->decimal('realized_pnl', 16, 8)->nullable();
            $table->timestamp('pnl_updated_at')->nullable();
        });

        Schema::create('trade_pnl_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 16, 8);
            $table->decimal('unrealized_pnl', 16, 8);
            $table->decimal('realized_pnl', 16, 8)->default(0);
            $table->decimal('roe', 8, 4); // Return on Equity в процентах
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('trade_pnl_history');
        Schema::dropColumns('trade_orders', [
            'unrealized_pnl',
            'realized_pnl',
            'pnl_updated_at'
        ]);
        Schema::dropColumns('trades', ['deleted_at']);

    }
};
