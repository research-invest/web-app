<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallet_reports', function (Blueprint $table) {
            $table->id();
            $table->timestamp('report_date');
            $table->decimal('total_balance', 20, 8);
            $table->integer('grown_wallets_count');
            $table->integer('dropped_wallets_count');
            $table->integer('unchanged_wallets_count');
            $table->json('top_gainers');
            $table->json('top_losers');
            $table->decimal('market_price', 20, 5)->nullable();
            $table->decimal('market_volume', 20, 5)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->index('report_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_reports');
    }
};
