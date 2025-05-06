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

    public $timeout = 900;

    private FundingDeal $deal;
    private array $priceHistory = [];

    public function __construct(FundingDeal $deal)
    {
        $this->deal = $deal;
    }

    public function handle()
    {

        Log::info("🚀 Start job for deal {$this->deal->id} at " . now());

        if ($this->deal->isStatusDone()) {
            return true;
        }

        // Проверяем, не прошло ли уже время фандинга
        if (now()->isAfter($this->deal->funding_time)) {
            Log::info('уже прошло время фандинга', [
                'id' => $this->deal->id,
                'code' => $this->deal->currency->code,
                'funding_time' => $this->deal->funding_time,
                'run_time' => $this->deal->run_time,
                'now' => now(),
            ]);

            $this->error('уже прошло время фандинга');

            return false;
        }

        $this->deal->update([
            'status' => FundingDeal::STATUS_PROCESS,
        ]);

        $isTestNet = $this->deal->dealConfig->is_testnet;

        //выбор биржы
        $gate = new GateIoService(
            $isTestNet ? $this->deal->user->gate_testnet_api_key : $this->deal->user->gate_api_key,
            $isTestNet ? $this->deal->user->gate_testnet_secret_key : $this->deal->user->gate_secret_key,
            $isTestNet,
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
                if ($secondsUntilFunding < 2 && $secondsUntilFunding > 0 && !$entryPrice) {

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
                            'id' => $this->deal->id,
                            'code' => $this->deal->currency->code,
                            'error' => $e->getMessage()
                        ]);

                        $this->error('Failed to close position ' . $e->getMessage());

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
                    'id' => $this->deal->id,
                    'code' => $this->deal->currency->code,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->error('Funding deal failed ' . $e->getMessage());
            }
        }

        $this->done();

        Log::info("✅ End job for deal {$this->deal->id} at " . now());

        return true;
    }

    private function done()
    {
        $this->deal->update([
            'status' => FundingDeal::STATUS_DONE,
        ]);
    }

    private function error(string $error)
    {
        $this->deal->update([
            'error' => $error,
            'status' => FundingDeal::STATUS_ERROR,
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
