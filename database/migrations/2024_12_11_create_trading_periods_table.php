<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {

        Schema::dropIfExists('trade_periods');
        Schema::create('trade_periods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('trade_periods')->insert([
            'name' => 'Ноябрь',
            'start_date' => '2024-11-11',
            'end_date' => '2024-12-11',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $defaultPeriodId = DB::getPdo()->lastInsertId();

        Schema::table('trades', function (Blueprint $table) use ($defaultPeriodId) {
            $table->foreignId('trade_period_id')
                ->default($defaultPeriodId)
                ->after('id');
        });

        DB::table('trades')
            ->whereNull('trade_period_id')
            ->update(['trade_period_id' => $defaultPeriodId]);

        Schema::table('trades', function (Blueprint $table) {
            $table->foreign('trade_period_id')
                ->references('id')
                ->on('trade_periods')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropForeign(['trade_periods']);
            $table->dropColumn('trade_periods');
        });

        Schema::dropIfExists('trade_periods');
    }
};
