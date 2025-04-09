<?php

namespace App\Jobs;

use App\Helpers\MathHelper;
use App\Models\Funding\FundingDeal;
use App\Services\GateIoService;
use App\Services\MexcService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FundingTrade implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 180;

    private FundingDeal $deal;
    private array $priceHistory = [];

    public function __construct(FundingDeal $deal)
    {
        $this->deal = $deal;
    }

    public function handle()
    {
        // Проверяем, не прошло ли уже время фандинга
        if (now()->isAfter($this->deal->funding_time)) {
            Log::info('уже прошло время фандинга', [
                'code' => $this->deal->currency->code,
                'funding_time' => $this->deal->funding_time,
                'now' => now(),
                'id' => $this->deal->id,
            ]);

            $this->done();

            return false;
        }

        $this->deal->update([
            'status' => FundingDeal::STATUS_PROCESS,
        ]);

        $gate = new GateIoService(
            $this->deal->user->mexc_api_key,
            $this->deal->user->mexc_secret_key,
        );

        $endTime = $this->deal->funding_time->copy()->addSeconds(30); // +90 секунд (1 минута после + 30 секунд дополнительно)

        $entryPrice = null;
        $positionClosed = false;

        $initialAmount = $this->deal->dealConfig->position_size;
        $leverage = $this->deal->dealConfig->leverage;
        $fundingRate = $this->deal->currency->funding_rate;

        while (now() <= $endTime) {
            try {

                $currentTime = now();
                $priceData = $gate->getCurrentPrice($this->deal->currency->code);
                $price = $priceData['price'];

                $this->priceHistory[] = [
                    'timestamp' => $currentTime->timestamp,
                    'price' => $price,
                    'execution_time' => $priceData['execution_time']
                ];

                $positionSize = $initialAmount * $leverage;
                $this->deal->update([
                    'price_history' => $this->priceHistory,
                    'initial_margin' => $initialAmount,
                    'position_size' => $positionSize,
                ]);

                // Если время фандинга - 1 секунда (с погрешностью), открываем позицию
                $secondsUntilFunding = $currentTime->diffInSeconds($this->deal->funding_time);
                if ($secondsUntilFunding <= 1 && $secondsUntilFunding > 0 && !$entryPrice) {

                    $entryPrice = $price;

                    $prices = array_column($this->priceHistory, 'price');

                    // Рассчитываем индекс волатильности
                    $volatilityIndex = MathHelper::calculateVolatilityIndex($prices);

                    // Параметры сделки

                    $contractQuantity = $positionSize / $entryPrice;
                    $fundingFee = ($positionSize * abs($fundingRate) / 100);

                    // Открываем реальную позицию
//                    $openPositionResult = $mexc->openPosition(
//                        $this->currency->code,
//                        $contractQuantity,
//                        $fundingRate > 0 ? 'SELL' : 'BUY', // Если funding rate положительный - шортим, если отрицательный - лонгим
//                        $leverage
//                    );

                    $this->deal->update([
                        'entry_price' => $entryPrice,
                        'contract_quantity' => $contractQuantity,
                        'leverage' => $leverage,
                        'funding_fee' => $fundingFee,
                        'pre_funding_volatility' => $volatilityIndex
                    ]);
                }

                // Если время фандинга + 1 секунда (с погрешностью), закрываем позицию
                $secondsAfterFunding = $currentTime->diffInSeconds($this->deal->funding_time);

                if ($entryPrice && !$positionClosed && $secondsAfterFunding < 0) {
                    try {
                        // Закрываем реальную позицию
//                        $closePositionResult = $mexc->closePosition(
//                            $this->currency->code,
//                            $this->deal->contract_quantity,
//                            $fundingRate > 0 ? 'BUY' : 'SELL', // Закрываем в противоположную сторону
//                            $openPositionResult['positionId'] ?? null // Передаем positionId если он был получен при открытии
//                        );

                        $exitPrice = $price;
                        $positionClosed = true;

                        // Расчет PnL
                        $priceChange = ($exitPrice - $entryPrice) / $entryPrice;
                        $pnlBeforeFunding = $this->deal->position_size * $priceChange;
                        $totalPnL = $pnlBeforeFunding + $this->deal->funding_fee;
                        $roiPercent = ($totalPnL / $this->deal->initial_margin) * 100;

                        $this->deal->update([
                            'exit_price' => $exitPrice,
                            'pnl_before_funding' => $pnlBeforeFunding,
                            'total_pnl' => $totalPnL,
                            'roi_percent' => $roiPercent
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to close position after all attempts', [
                            'code' => $this->deal->currency->code,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
                }

                // Продолжаем собирать данные еще 30 секунд после закрытия позиции
//                if ($positionClosed && $currentTime->isAfter($this->fundingTime->copy()->addSeconds(31))) {
//                    Log::info('Simulation completed with additional monitoring', [
//                        'code' => $this->currency->code,
//                        'total_price_points' => count($this->priceHistory),
//                        'simulation_id' => $this->deal->id
//                    ]);
//                    return;
//                }

                sleep(1);

            } catch (\Exception $e) {
                Log::error('Funding deal failed', [
                    'code' => $this->deal->currency->code,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->done();

        return true;
    }

    private function done()
    {
        $this->deal->update([
            'status' => FundingDeal::STATUS_DONE,
        ]);
    }

    public function uniqueId()
    {
        return 'trade_deal_' . $this->deal->id;
    }

    public function retryUntil()
    {
        return $this->deal->funding_time->copy()->addMinutes(5);
    }

}
