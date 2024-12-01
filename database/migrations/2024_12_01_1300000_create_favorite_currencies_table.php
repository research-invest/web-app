<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropColumns('currencies', ['is_favorite']);

        Schema::create('currencies_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('currency_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'currency_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('currencies_favorites');
    }
};
