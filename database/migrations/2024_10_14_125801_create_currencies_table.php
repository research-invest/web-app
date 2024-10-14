<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (!Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('exchange')->nullable();
                $table->string('code')->index();
                $table->tinyInteger('type')->default(1);
                $table->double('last_price')->nullable();
                $table->boolean('is_favorite')->default(false);

                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
