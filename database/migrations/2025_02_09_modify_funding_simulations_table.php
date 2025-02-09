<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('funding_simulations', function (Blueprint $table) {
            $table->decimal('position_size', 20, 8)->nullable()->after('exit_price');
            $table->decimal('contract_quantity', 20, 8)->nullable()->after('position_size');
            $table->integer('leverage')->nullable()->after('contract_quantity');
            $table->decimal('initial_margin', 20, 8)->nullable()->after('leverage');
            $table->decimal('funding_fee', 20, 8)->nullable()->after('initial_margin');
            $table->decimal('pnl_before_funding', 20, 8)->nullable()->after('funding_fee');
            $table->decimal('total_pnl', 20, 8)->nullable()->after('pnl_before_funding');
            $table->decimal('roi_percent', 10, 2)->nullable()->after('total_pnl');
        });
    }

    public function down()
    {
        Schema::table('funding_simulations', function (Blueprint $table) {
            $table->dropColumn([
                'position_size',
                'contract_quantity',
                'leverage',
                'initial_margin',
                'funding_fee',
                'pnl_before_funding',
                'total_pnl',
                'roi_percent'
            ]);
        });
    }
};
