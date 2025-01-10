<?php
/**
 * php artisan db:seed --class=CheckListSeeder
 */

namespace Database\Seeders;

use App\Models\CheckListItem;
use Illuminate\Database\Seeder;

class CheckListSeeder extends Seeder
{
    public function run()
    {
        $items = [
            [
                'title' => 'Проверить теханализ на tradingview',
                'description' => 'Справа внизу, котировки, информация новости, нажать на квадратик и там пункт теханализ',
            ],
            [
                'title' => 'Проверить объемы в ATAS',
                'description' => 'Помотреть объемы по разным ТФ',
            ],
            [
                'title' => 'Войти в фейковую сделку',
                'description' => 'Хотя бы на 10-20 минут',
            ],
        ];

        foreach ($items as $item) {
            CheckListItem::firstOrCreate(['title' => $item['title']], $item);
        }

    }
}
