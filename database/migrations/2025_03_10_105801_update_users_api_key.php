<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gate_secret_key')->nullable();
            $table->string('gate_api_key')->nullable();

            $table->string('kucoin_api_key')->nullable();
            $table->string('kucoin_secret_key')->nullable();
            $table->string('kucoin_api_passphrase')->nullable();
        });
    }

    public function down()
    {
        Schema::dropColumns('users', [
            'gate_secret_key',
            'gate_api_key',

            'kucoin_secret_key',
            'kucoin_api_key',
            'kucoin_api_passphrase',
        ]);
    }
};
