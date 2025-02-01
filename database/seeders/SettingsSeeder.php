<?php
/**
 * php artisan db:seed --class=SettingsSeeder
 */

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        Setting::firstOrCreate(['key' => Setting::HUNTER_FUNDING_LESS_VALUE,], [
            'value' => -0.75,
            'description' => 'Минимальное значение для выборки funding hunter',
        ]);
    }
}
