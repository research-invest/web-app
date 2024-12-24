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
        $data = (new Tickers())->getTickers($currency, 1800);
        $analise = (new SmartMoneyStrategy())->analyze($data);

        if ($analise) {
            $this->telegram->sendMessage(($currency . ' ' . $analise['message']),
                '-1002321524146');
        } else {
            $this->info(sprintf('Анализ %s не выявил SmartMoney закономерностей', $currency));
        }
    }
}
