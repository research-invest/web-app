<?php
/**
 * php artisan send-smart-money-alert:run
 */

namespace App\Console\Commands\Alerts;

use App\Services\Api\Tickers;
use App\Services\Strategy\SmartMoneyStrategy;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class SendSmartMoneyAlert extends Command
{
    protected $signature = 'send-smart-money-alert:run';
    protected $description = '';


    private TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        parent::__construct();
        $this->telegram = $telegram;
    }

    public function handle()
    {
//        $this->send('BTCUSDT');
        $this->send('TAOUSDT');
        $this->send('DOGEUSDT');
    }

    private function send(string $currency)
    {
        // Получаем данные для индекса
        $tickerService = new Tickers();
        $data1h = $tickerService->getTickers($currency, 3600);
        $smStrategy = new SmartMoneyStrategy();
        $analise = $smStrategy->analyze($data1h);

        if ($analise['is_accumulation']) {
            $this->telegram->sendMessage($analise['message']);
        }
    }
}
