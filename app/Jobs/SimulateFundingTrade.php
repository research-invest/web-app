<?php

namespace App\Jobs;

use App\Models\Currency;
use App\Models\FundingSimulation;
use App\Services\MexcService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SimulateFundingTrade implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Currency $currency;
    private Carbon $fundingTime;
    private array $priceHistory = [];

    public function __construct(Currency $currency, Carbon $fundingTime)
    {
        $this->currency = $currency;
        $this->fundingTime = $fundingTime;
    }

    public function handle(MexcService $mexc)
    {
        try {
            // Начинаем мониторинг за минуту до
            $startTime = $this->fundingTime->copy()->subMinute();
            $endTime = $this->fundingTime->copy()->addMinute();

            // Записываем цены каждую секунду в течение 2 минут
            while (now() <= $endTime) {
                $price = $mexc->getCurrentPrice($this->currency->code);

                $this->priceHistory[] = [
                    'timestamp' => now()->timestamp,
                    'price' => $price
                ];

                // Если время фандинга - 1 секунда, открываем позицию
                if (now()->diffInSeconds($this->fundingTime) === 1) {
                    $entryPrice = $price;
                }

                // Если время фандинга + 1 секунда, закрываем позицию
                if (now()->timestamp === $this->fundingTime->addSecond()->timestamp) {
                    $exitPrice = $price;

                    FundingSimulation::create([
                        'currency_id' => $this->currency->id,
                        'funding_time' => $this->fundingTime,
                        'funding_rate' => $this->currency->latestFundingRate->funding_rate,
                        'entry_price' => $entryPrice,
                        'exit_price' => $exitPrice,
                        'profit_loss' => $exitPrice - $entryPrice,
                        'price_history' => $this->priceHistory
                    ]);

                    Log::info('Funding simulation completed', [
                        'symbol' => $this->currency->symbol,
                        'entry_price' => $entryPrice,
                        'exit_price' => $exitPrice,
                        'profit' => $exitPrice - $entryPrice
                    ]);

                    return;
                }

                sleep(1); // Пауза в 1 секунду
            }
        } catch (\Exception $e) {
            Log::error('Funding simulation failed', [
                'symbol' => $this->currency->code,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function uniqueId()
    {
        return 'trade_currency_' . $this->currency->id;

        return $this->currency->id . '_' . $this->fundingTime->timestamp;
    }

    public function uniqueFor()
    {
        // Держим блокировку до времени фандинга + 2 минуты
        return $this->fundingTime->addMinutes(2)->diffInSeconds(now());
    }


    public function tags()
    {
        return [
            'funding_simulation',
            'currency_' . $this->currency->id,
            'symbol_' . $this->currency->code,
            'funding_time_' . $this->fundingTime->timestamp
        ];
    }
}
