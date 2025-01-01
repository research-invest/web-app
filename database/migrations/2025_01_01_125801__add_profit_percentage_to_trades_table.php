<?php

use App\Helpers\MathHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use \App\Models\TradeOrder;
use \App\Models\Trade;

return new class extends Migration
{
    public function up()
    {
        Schema::create('strategies', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name');
            $table->text('description')->nullable();

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('trades', function (Blueprint $table) {
            $table->decimal('profit_percentage', 8, 2)->default(0);
            $table->unsignedBigInteger('strategy_id')->nullable()->index();
            $table->foreign('strategy_id')
                ->references('id')
                ->on('strategies');
        });

        Trade::whereIn('status', [
            Trade::STATUS_CLOSED,
            Trade::STATUS_LIQUIDATED,
        ])->chunk(100, function ($trades) {
            foreach ($trades as $trade) {
                $trade->profit_percentage = $trade->getProfitPercentage();
                $trade->save();
            }
        });
    }

    public function down()
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropColumn('profit_percentage');

            $table->dropForeign('trades_strategy_id_foreign');
            $table->dropColumn('strategy_id');
        });

        Schema::dropIfExists('strategies');
    }
};
