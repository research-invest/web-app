<?php

/**
 * php artisan db:seed --class=WalletSeeder
 */
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WalletSeeder extends Seeder
{
    private function parseBalance(string $value): float
    {
        // Удаляем BTC и пробелы
        $value = trim($value, 'BTC ');

        // Заменяем разделитель тысяч на пустоту и десятичный разделитель на точку
        $value = preg_replace('/[.,]/', '', $value, count(explode(',', $value)) - 1);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }

    public function run()
    {
        $file = storage_path('btc-wallets.txt');
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $data = explode(';', $line);

            $balance = $this->parseBalance($data[1]);

            DB::table('wallets')->insertOrIgnore([
                'address' => $data[0],
                'balance' => $balance,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
