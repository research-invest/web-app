<?php

namespace App\Jobs;

use App\Models\Currency;
use App\Models\FundingSimulation;
use App\Services\MexcService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        // Проверяем, не прошло ли уже время фандинга
        if (now()->isAfter($this->fundingTime->copy()->addMinutes(1))) {
            Log::info('уже прошло время фандинга', [
                'code' => $this->currency->code,
                'funding_time' => $this->fundingTime
            ]);
            return;
        }

        $simulation = FundingSimulation::create([
            'currency_id' => $this->currency->id,
            'funding_time' => $this->fundingTime,
            'funding_rate' => $this->currency->latestFundingRate->funding_rate,
            'price_history' => [],
        ]);

        // Начинаем мониторинг за минуту до
        $startTime = $this->fundingTime->copy()->subMinute();
        $endTime = $this->fundingTime->copy()->addSeconds(60); // +90 секунд (1 минута после + 30 секунд дополнительно)

        $entryPrice = null;
        $positionClosed = false;

        while (now() <= $endTime) {
            try {

                $currentTime = now();
                $price = $mexc->getCurrentPrice($this->currency->code);

                $this->priceHistory[] = [
                    'timestamp' => $currentTime->timestamp,
                    'price' => $price
                ];

                $simulation->update([
                    'price_history' => $this->priceHistory
                ]);

                Log::info('diffInSeconds ' . $this->currency->code, [
                    'diffInSeconds' => $currentTime->diffInSeconds($this->fundingTime),
                    'isAfter' => $currentTime->isAfter($this->fundingTime->copy()->addSecond()),
                    '$entryPrice' => $entryPrice,
                ]);

                // Если время фандинга - 1 секунда (с погрешностью), открываем позицию
                $secondsUntilFunding = $currentTime->diffInSeconds($this->fundingTime);
                if ($secondsUntilFunding <= 1 && $secondsUntilFunding > 0 && !$entryPrice) {
                    $entryPrice = $price;
                    $simulation->update([
                        'entry_price' => $entryPrice
                    ]);

                    Log::info('Position opened', [
                        'code' => $this->currency->code,
                        'price' => $entryPrice,
                        'seconds_until_funding' => $secondsUntilFunding,
                        'simulation_id' => $simulation->id
                    ]);
                }

                // Если время фандинга + 1 секунда (с погрешностью), закрываем позицию
                $secondsAfterFunding = $currentTime->diffInSeconds($this->fundingTime);
                if ($entryPrice && !$positionClosed && $secondsAfterFunding >= 1 && $secondsAfterFunding < 2) {
                    $exitPrice = $price;
                    $positionClosed = true;

                    $simulation->update([
                        'exit_price' => $exitPrice,
                        'profit_loss' => $exitPrice - $entryPrice
                    ]);
                }

                // Продолжаем собирать данные еще 30 секунд после закрытия позиции
//                if ($positionClosed && $currentTime->isAfter($this->fundingTime->copy()->addSeconds(31))) {
//                    Log::info('Simulation completed with additional monitoring', [
//                        'code' => $this->currency->code,
//                        'total_price_points' => count($this->priceHistory),
//                        'simulation_id' => $simulation->id
//                    ]);
//                    return;
//                }

                sleep(1);

            } catch (\Exception $e) {
                Log::error('Funding simulation failed', [
                    'code' => $this->currency->code,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    public function uniqueId()
    {
        return 'trade_currency_' . $this->currency->id;
    }

//    public function uniqueFor()
//    {
//        return $this->fundingTime->addMinutes(2)->diffInSeconds(now());
//    }

    public function tags()
    {
        return [
            'funding_simulation',
            'currency_' . $this->currency->id,
            'symbol_' . $this->currency->symbol,
            'funding_time_' . $this->fundingTime->timestamp
        ];
    }

    public function retryUntil()
    {
        return $this->fundingTime->copy()->addMinutes(5);
    }
}
