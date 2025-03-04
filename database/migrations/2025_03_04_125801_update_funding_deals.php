<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('funding_deals', function (Blueprint $table) {
            $table->timestamp('run_time');
        });
    }

    public function down()
    {
        Schema::dropColumns('funding_deals', [
            'run_time',
        ]);
    }
};
