<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->boolean('is_fake')->default(false);
        });
    }

    public function down()
    {
        Schema::dropColumns('trades', [
            'is_fake',
        ]);
    }
};
