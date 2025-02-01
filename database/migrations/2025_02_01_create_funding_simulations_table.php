<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->tinyInteger('type_field')
                ->default(Setting::TYPE_FIELD_TEXT)
                ->comment('Тип поля');
        });

        Schema::create('funding_simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained();
            $table->timestamp('funding_time');
            $table->decimal('funding_rate', 10, 8);
            $table->decimal('entry_price', 20, 8);
            $table->decimal('exit_price', 20, 8);
            $table->decimal('profit_loss', 20, 8);
            $table->json('price_history')->nullable(); // Для хранения истории цен
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('funding_simulations');
    }
};
