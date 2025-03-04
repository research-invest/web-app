<?php
/**
 * php artisan hunter-funding:alert
 */

namespace App\Console\Commands\Alerts;

use App\Models\Currency;
use App\Models\Setting;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Jobs\SimulateFundingTrade;

class HunterFunding extends Command
{
    protected $signature = 'hunter-funding:alert';
    protected $description = '';


    private TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        parent::__construct();
        $this->telegram = $telegram;
    }

    public function handle()
    {
        $currencies = Currency::query()
            ->with('latestFundingRate')
            ->where('currencies.funding_rate', '<=', Setting::getHunterFundingLessValue())
            ->get();


        if ($currencies->isNotEmpty()) {
            $this->sendAlertIfNeeded($currencies);
        }
    }

    private function sendAlertIfNeeded($currencies)
    {
        $message = $this->formatAlertMessage($currencies);

        $this->telegram->sendMessage($message, config('services.telegram.hunter_funding_chat_id'));

        /**
         * @var Currency $currency
         */

        foreach ($currencies as $currency) {
            $fundingTime = Carbon::createFromTimestamp(
                $currency->latestFundingRate->next_settle_time / 1000
            );

//            $fundingTime = now()->addMinutes(1);

            // Запускаем job за минуту до изменения фандинга
//            SimulateFundingTrade::dispatchSync($currency, $fundingTime)//->delay($fundingTime->copy()->subMinute())
            ;

            $cacheKey = "funding_queue_{$currency->code}_{$fundingTime->timestamp}";

            if (!Cache::has($cacheKey)) {
//                SimulateFundingTrade::dispatch($currency, $fundingTime)
//                    ->delay($fundingTime->copy()->subMinutes(1));

                Cache::put($cacheKey, true, $fundingTime->copy()->addMinutes(3));
            }
        }


//        $cacheKey = "funding_alert_hourly";
//
//        if (!Cache::has($cacheKey)) {
//
//            if ($this->telegram->sendMessage($message, '-1002466965376')) {
//                Cache::put($cacheKey, true, now()->addMinutes(self::NOTIFICATION_COOLDOWN));
//            }
//        }
    }

    private function formatAlertMessage($currencies): string
    {
        $message = "🔔 <b>Обнаружены валюты с высоким отрицательным фандингом</b>\n\n";

        /**
         * @var Currency $currency
         */
        foreach ($currencies as $currency) {
            $nextSettleTime = Carbon::createFromTimestamp($currency->latestFundingRate->next_settle_time / 1000);
            $remaining = now()->diff($nextSettleTime);

            // Определяем цвет для оставшегося времени
            $totalHours = $remaining->h + ($remaining->d * 24);
            $timeStatus = "⏳";
            if ($totalHours < 1) {
                $timeStatus = "⚠️";
            } elseif ($totalHours < 2) {
                $timeStatus = "⚡";
            }

            $message .= "💰 <b>{$currency->code}</b>\n";
            $message .= "• Фандинг: {$currency->latestFundingRate->funding_rate}\n";
            $message .= sprintf(
                "• Следующее изменение:\n  %s UTC\n  %s MSK\n",
                $nextSettleTime->timezone('UTC')->format('Y-m-d H:i:s'),
                $nextSettleTime->timezone('Europe/Moscow')->format('Y-m-d H:i:s')
            );
            $message .= sprintf(
                "• %s Осталось: %02dч %02dм\n",
                $timeStatus,
                $totalHours,
                $remaining->i
            );

            $message .= "• {$currency->getExchangeLink()}\n";
            $message .= "• {$currency->getAdminPageLink()}\n\n";
        }

//        $message .= "\n⚡ <b>Рекомендуемые действия:</b>\n";
//        $message .= "• Проверьте возможность открытия позиции\n";
//        $message .= "• Оцените риски и потенциальную прибыль\n";
//        $message .= "• Учитывайте время до следующего изменения фандинга";

        return $message;
    }
}
