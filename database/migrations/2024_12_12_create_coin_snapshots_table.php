<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('top_performing_coin_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->onDelete('cascade');

            $table->string('symbol', 20);
            $table->decimal('price_change_percent', 10, 2);
            $table->decimal('volume_diff_percent', 10, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->index('symbol');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_performing_coin_snapshots');
    }
};
