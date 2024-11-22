<?php
/**
 * php artisan index:send-chart
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;
use App\Services\IndexCalculator;
use App\Services\ChartGenerator;
use App\Services\Api\Tickers;

class SendIndexChart extends Command
{
    protected $signature = 'index:send-chart';
    protected $description = '';


    private TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        parent::__construct();
        $this->telegram = $telegram;
    }

    public function handle()
    {
        $this->send('BTCUSDT');
        $this->send('TAOUSDT');

        $this->info('Индексы успешно отправлены в телеграмм');
    }

    private function send(string $currency)
    {
        // Получаем данные для индекса
        $tickerService = new Tickers();
        $indexCalculator = new IndexCalculator();
        $chartGenerator = new ChartGenerator();

        $data3m = $tickerService->getTickers($currency, 180);
        $data15m = $tickerService->getTickers($currency, 900);
        $data1h = $tickerService->getTickers($currency, 3600);

        // Рассчитываем индекс
        $indexData = $indexCalculator->calculateIndex($data3m, $data15m, $data1h);

        // Генерируем изображение
        $imageData = $chartGenerator->generateIndexChart($indexData);

        $this->telegram->sendPhoto(
            "Композитный индекс $currency\nВремя: " . now()->format('Y-m-d H:i:s'),
            $imageData
        );
    }
}
