<?php
/**
 * php artisan db:seed --class=CurrenciesSeeder
 */

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class CurrenciesSeeder extends Seeder
{
    public function run()
    {
        Currency::firstOrCreate(['code' => 'BTCUSDT'], [
            'name' => 'BTCUSDT',
        ]);

        Currency::firstOrCreate(['code' => 'TAOUSDT'], [
            'name' => 'TAOUSDT',
        ]);

        Currency::firstOrCreate(['code' => 'PEPEUSDT'], [
            'name' => 'PEPEUSDT',
        ]);
    }
}
