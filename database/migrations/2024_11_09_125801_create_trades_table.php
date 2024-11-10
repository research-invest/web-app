<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (!Schema::hasTable('trades')) {
            Schema::create('trades', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('currency_id')->constrained('currencies');
                $table->string('position_type'); // long/short
                $table->decimal('entry_price', 16, 8);
                $table->decimal('position_size', 16, 2);
                $table->integer('leverage');
                $table->decimal('stop_loss_price', 16, 8);
                $table->decimal('take_profit_price', 16, 8);
                $table->decimal('target_profit_amount', 16, 2)->nullable();
                $table->string('status')->default('open'); // open, closed, liquidated
                $table->decimal('exit_price', 16, 8)->nullable();
                $table->decimal('realized_pnl', 16, 2)->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->text('notes')->nullable();

                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('trade_orders')) {
            Schema::create('trade_orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('trade_id')->constrained()->cascadeOnDelete();
                $table->decimal('price', 16, 8);
                $table->decimal('size', 16, 2);
                $table->string('type'); // entry, add, exit
                $table->timestamp('executed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_orders');
        Schema::dropIfExists('trades');
    }
};
