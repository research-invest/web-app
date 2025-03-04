<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('currencies', function (Blueprint $table) {
            $table->timestamp('next_settle_time')->nullable();
        });

        Schema::table('trades', function (Blueprint $table) {
            $table->boolean('is_notify')->default(true);
            $table->boolean('is_spot')->default(false);
        });

        Schema::create('funding_deals_config', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->string('exchange');
            $table->text('notes')->nullable();

            $table->foreignId('user_id')->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->boolean('is_active')->default(true);
            $table->decimal('min_funding_rate', 10, 8);
            $table->decimal('position_size', 20, 8)->default(100);
            $table->integer('leverage')->default(10);
            $table->json('currencies')->nullable();
            $table->json('ignore_currencies')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('funding_deals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('funding_deal_config_id')->nullable()
                ->constrained('funding_deals_config')
                ->cascadeOnDelete();

            $table->foreignId('user_id')->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
            $table->tinyInteger('status')->default(1);
            $table->foreignId('currency_id')->constrained();
            $table->timestamp('funding_time');
            $table->decimal('funding_rate', 10, 8);
            $table->decimal('entry_price', 20, 8)->default(0);
            $table->decimal('exit_price', 20, 8)->default(0);
            $table->decimal('profit_loss', 20, 8)->default(0);
            $table->decimal('position_size', 20, 8)->nullable();
            $table->decimal('contract_quantity', 20, 8)->nullable();
            $table->integer('leverage')->nullable();
            $table->decimal('initial_margin', 20, 8)->nullable();
            $table->decimal('funding_fee', 20, 8)->nullable();
            $table->decimal('pnl_before_funding', 20, 8)->nullable();
            $table->decimal('total_pnl', 20, 8)->nullable();
            $table->decimal('roi_percent', 10, 2)->nullable();
            $table->json('price_history')->nullable(); // Для хранения истории цен
        });

    }

    public function down()
    {
        Schema::dropColumns('trades', [
            'is_notify',
            'is_spot',
        ]);

        Schema::dropColumns('users', [
            'deleted_at',
        ]);


        Schema::dropColumns('currencies', [
            'next_settle_time',
        ]);

        Schema::dropIfExists('funding_deals');
        Schema::dropIfExists('funding_deals_config');
    }
};
