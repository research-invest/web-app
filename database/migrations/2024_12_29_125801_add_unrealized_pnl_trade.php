<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->decimal('unrealized_pnl')
                ->after('realized_pnl')
                ->default(0);
        });
    }

    public function down()
    {
        Schema::dropColumns('trades', [
            'unrealized_pnl',
        ]);
    }
};
