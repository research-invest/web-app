<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->decimal('start_funding_8h', 10, 8)->nullable();
            $table->decimal('start_funding_24h', 10, 8)->nullable();
            $table->decimal('start_funding_48h', 10, 8)->nullable();
            $table->decimal('start_funding_7d', 10, 8)->nullable();
            $table->decimal('start_funding_30d', 10, 8)->nullable();
        });

        Schema::dropColumns('funding_rates', [
            'diff_4h',
            'diff_8h',
            'diff_12h',
            'diff_24h',
        ]);

        Schema::table('funding_rates', function (Blueprint $table) {
            $table->decimal('diff_8h', 10, 8)->nullable();
            $table->decimal('diff_24h', 10, 8)->nullable();
            $table->decimal('diff_48h', 10, 8)->nullable();
            $table->decimal('diff_7d', 10, 8)->nullable();
            $table->decimal('diff_30d', 10, 8)->nullable();
        });
    }

    public function down()
    {
        Schema::dropColumns('currencies', [
            'start_funding_8h',
            'start_funding_24h',
            'start_funding_48h',
            'start_funding_7d',
            'start_funding_30d',
        ]);

        Schema::dropColumns('funding_rates', [
            'diff_8h',
            'diff_24h',
            'diff_48h',
            'diff_7d',
            'diff_30d',
        ]);
    }
};
