<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->unique(['exchange', 'code', 'type']);
        });

        Schema::create('funding_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')
                ->constrained()
                ->onDelete('cascade');
            $table->decimal('funding_rate', 10, 8);
            $table->decimal('max_funding_rate', 10, 8);
            $table->decimal('min_funding_rate', 10, 8);
            $table->integer('collect_cycle');
            $table->bigInteger('next_settle_time');
            $table->bigInteger('timestamp')->index();

            $table->decimal('diff_4h', 10, 8)->nullable();
            $table->decimal('diff_8h', 10, 8)->nullable();
            $table->decimal('diff_12h', 10, 8)->nullable();
            $table->decimal('diff_24h', 10, 8)->nullable();

            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('funding_rates');

    }
};
