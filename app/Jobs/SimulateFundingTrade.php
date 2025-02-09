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

    public $tries = 1;

    public $timeout = 180;

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

        /**
         * @var FundingSimulation $simulation
         */
        $simulation = FundingSimulation::create([
            'currency_id' => $this->currency->id,
            'funding_time' => $this->fundingTime,
            'funding_rate' => $this->currency->latestFundingRate->funding_rate,
            'price_history' => [],
        ]);

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

                // Если время фандинга - 1 секунда (с погрешностью), открываем позицию
                $secondsUntilFunding = $currentTime->diffInSeconds($this->fundingTime);
                if ($secondsUntilFunding <= 1 && $secondsUntilFunding > 0 && !$entryPrice) {
                    $entryPrice = $price;

                    $prices = array_column($this->priceHistory, 'price');

                    // Рассчитываем индекс волатильности
                    $volatilityIndex = $this->calculateVolatilityIndex($prices);

                    // Параметры сделки
                    $initialAmount = 1000;
                    $leverage = 50;
                    $positionSize = $initialAmount * $leverage;
                    $contractQuantity = $positionSize / $entryPrice;
                    $fundingRate = $this->currency->latestFundingRate->funding_rate;
                    $fundingFee = ($positionSize * abs($fundingRate));

                    $simulation->update([
                        'entry_price' => $entryPrice,
                        'position_size' => $positionSize,
                        'contract_quantity' => $contractQuantity,
                        'leverage' => $leverage,
                        'initial_margin' => $initialAmount,
                        'funding_fee' => $fundingFee,
                        'pre_funding_volatility' => $volatilityIndex
                    ]);

                    Log::info('Position opened', [
                        'code' => $this->currency->code,
                        'price' => $entryPrice,
                        'position_size' => $positionSize,
                        'contract_quantity' => $contractQuantity,
                        'leverage' => $leverage,
                        'initial_margin' => $initialAmount,
                        'funding_rate' => $fundingRate,
                        'funding_fee' => $fundingFee,
                        'volatility_index' => $volatilityIndex,
                        'seconds_until_funding' => $secondsUntilFunding,
                        'simulation_id' => $simulation->id
                    ]);
                }

                // Если время фандинга + 1 секунда (с погрешностью), закрываем позицию
                $secondsAfterFunding = $currentTime->diffInSeconds($this->fundingTime);

                if ($entryPrice && !$positionClosed) {
                    Log::info('$secondsAfterFunding ' . $this->currency->code, [
                        '$secondsAfterFunding' => $secondsAfterFunding,
                    ]);
                }

                if ($entryPrice && !$positionClosed && $secondsAfterFunding >= 1 && $secondsAfterFunding < 3) {
                    $exitPrice = $price;
                    $positionClosed = true;

                    // Расчет PnL
                    $priceChange = ($exitPrice - $entryPrice) / $entryPrice;
                    $pnlBeforeFunding = $simulation->position_size * $priceChange;
                    $totalPnL = $pnlBeforeFunding - $simulation->funding_fee;
                    $roiPercent = ($totalPnL / $simulation->initial_margin) * 100;

                    $simulation->update([
                        'exit_price' => $exitPrice,
                        'pnl_before_funding' => $pnlBeforeFunding,
                        'total_pnl' => $totalPnL,
                        'roi_percent' => $roiPercent
                    ]);

                    Log::info('Position closed', [
                        'code' => $this->currency->code,
                        'entry_price' => $entryPrice,
                        'exit_price' => $exitPrice,
                        'pnl_before_funding' => $pnlBeforeFunding,
                        'funding_fee' => $simulation->funding_fee,
                        'total_pnl' => $totalPnL,
                        'roi_percent' => $roiPercent,
                        'simulation_id' => $simulation->id
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

    /**
     * Рассчитывает индекс волатильности на основе массива цен
     */
    private function calculateVolatilityIndex(array $prices): float
    {
        if (empty($prices)) {
            return 0;
        }

        // Находим максимальную и минимальную цены
        $maxPrice = max($prices);
        $minPrice = min($prices);

        // Находим среднюю цену
        $avgPrice = array_sum($prices) / count($prices);

        // Рассчитываем процентный размах от средней цены
        $volatilityIndex = (($maxPrice - $minPrice) / $avgPrice) * 100;

        return round($volatilityIndex, 4);
    }
}
