<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trading_view_webhooks', function (Blueprint $table) {

            $table->foreignId('user_id')
                ->default(1)
                ->constrained('users')
                ->onDelete('cascade');


            $table->boolean('is_read')->default(false)->after('user_agent');
            $table->index('is_read');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trading_view_webhooks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropIndex(['is_read']);
            $table->dropColumn('is_read');
        });
    }
};
