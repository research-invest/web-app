<?php
/**
 * php artisan index:send-chart
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;
use App\Services\IndexCalculator;
use App\Services\Api\Tickers;
use Illuminate\Support\Facades\Http;

class SendIndexChart extends Command
{
    protected $signature = 'index:send-chart';
    protected $description = '';

    public function handle(TelegramService $telegram)
    {
        // Получаем данные для индекса
        $tickerService = new Tickers();
        $indexCalculator = new IndexCalculator();
        $currency = 'BTCUSDT';

        $data3m = $tickerService->getTickers($currency, 180);
        $data15m = $tickerService->getTickers($currency, 900);
        $data1h = $tickerService->getTickers($currency, 3600);

        // Рассчитываем индекс
        $indexData = $indexCalculator->calculateIndex($data3m, $data15m, $data1h);

        // Формируем данные для графика
        $chartData = json_encode([
            'title' => [
                'text' => 'Композитный индекс ' . $currency
            ],
            'series' => [[
                'data' => array_map(function ($item) {
                    return [$item['timestamp'], $item['score']];
                }, $indexData)
            ]]
        ]);

        // Используем Export Server Highcharts для создания изображения
        $response = Http::post('https://export.highcharts.com/', [
            'async' => false,
            'type' => 'png',
            'width' => 800,
            'options' => $chartData
        ]);

        if ($response->successful()) {
            $imageData = $response->body();

            // Отправляем изображение в Telegram
            $telegram->sendPhoto(
                "Композитный индекс $currency\nВремя: " . now()->format('Y-m-d H:i:s'),
                $imageData
            );

            dd($imageData);

        }

    }
}
