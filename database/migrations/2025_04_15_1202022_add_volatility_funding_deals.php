<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('funding_deals', function (Blueprint $table) {
            $table->decimal('pre_funding_volatility', 10, 4)
                ->nullable()
                ->after('roi_percent');
        });
    }

    public function down()
    {
        Schema::table('funding_deals', function (Blueprint $table) {
            $table->dropColumn('pre_funding_volatility');
        });
    }
};
