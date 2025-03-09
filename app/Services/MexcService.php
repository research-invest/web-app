<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lin\Mxc\MxcContract;

class MexcService
{
    private MxcContract $mxcContract;

    public function __construct(string $apiKey, string $apiSecret)
    {
        $this->mxcContract = new MxcContract($apiKey, $apiSecret);
    }

    public function getCurrentPrice(string $symbol): array
    {
        $startTime = microtime(true);

        try {
            $result = $this->mxcContract->market()->getFairPrice(['symbol' => $symbol]);
            $endTime = microtime(true);

            return [
                'price' => $result['data']['fairPrice'] ?? null,
                'execution_time' => $this->calcExecutionTime($startTime, $endTime),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get current price', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getContractInfo(string $symbol): array
    {
//        "data" => array:65 [
//        "symbol" => "TAO_USDT"
//    "displayName" => "TAO_USDT永续"
//    "displayNameEn" => "TAO_USDT PERPETUAL"
//    "positionOpenType" => 3
//    "baseCoin" => "TAO"
//    "quoteCoin" => "USDT"
//    "baseCoinName" => "TAO"
//    "quoteCoinName" => "USDT"
//    "futureType" => 1
//    "settleCoin" => "USDT"
//    "contractSize" => 0.01
//    "minLeverage" => 1
//    "maxLeverage" => 200
//    "countryConfigContractMaxLeverage" => 0
//    "priceScale" => 1
//    "volScale" => 0
//    "amountScale" => 4
//    "priceUnit" => 0.1
//    "volUnit" => 1
//    "minVol" => 1
//    "maxVol" => 300000
//    "bidLimitPriceRate" => 0.2
//    "askLimitPriceRate" => 0.2
//    "takerFeeRate" => 0
//    "makerFeeRate" => 0
//    "maintenanceMarginRate" => 0.004
//    "initialMarginRate" => 0.005
//    "riskBaseVol" => 300000
//    "riskIncrVol" => 300000
//    "riskLongShortSwitch" => 0
//    "riskIncrMmr" => 0.056
//    "riskIncrImr" => 0.095
//    "riskLevelLimit" => 1
//    "priceCoefficientVariation" => 0.2
//    "indexOrigin" => array:3 [
//        0 => "BITGET"
//      1 => "BINANCE"
//      2 => "KUCOIN"
//    ]
//    "state" => 0
//    "isNew" => false
//    "isHot" => false
//    "isHidden" => false
//    "conceptPlate" => array:2 [
//        0 => "mc-trade-zone-0fees"
//      1 => "mc-trade-zone-ai"
//    ]
//    "conceptPlateId" => array:2 [
//        0 => 45
//      1 => 23
//    ]
//    "riskLimitType" => "BY_VOLUME"
//    "maxNumOrders" => array:2 [
//        0 => 200
//      1 => 50
//    ]
//    "marketOrderMaxLevel" => 20
//    "marketOrderPriceLimitRate1" => 0.2
//    "marketOrderPriceLimitRate2" => 0.005
//    "triggerProtect" => 0.1
//    "appraisal" => 0
//    "showAppraisalCountdown" => 0
//    "automaticDelivery" => 0
//    "apiAllowed" => false
//    "depthStepList" => array:2 [
//        0 => "0.1"
//      1 => "1"
//    ]
//    "limitMaxVol" => 300000
//    "threshold" => 0
//    "baseCoinIconUrl" => "https://public.mocortech.com/coin/F20240703110927545DFbHsKjteBFTqu.png"
//    "id" => 531
//    "vid" => "128f589271cb4951b03e71e6323eb7be"
//    "baseCoinId" => "c3e65cd2e516470a840a0cd0fcf78ad7"
//    "createTime" => 1706670696000
//    "openingTime" => 0
//    "openingCountdownOption" => 1
//    "showBeforeOpen" => true
//    "isMaxLeverage" => false
//    "isZeroFeeRate" => true
//    "riskLimitMode" => "INCREASE"
//  ]
//  "execution_time" => 413.25
//]

        $startTime = microtime(true);

        try {
            $result = $this->mxcContract->market()->getDetail(['symbol' => $symbol]);
            $endTime = microtime(true);

            return [
                'data' => $result['data'] ?? [],
                'execution_time' => $this->calcExecutionTime($startTime, $endTime),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get get contract info', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

//        'max_leverage' => $data['maxLeverage'], // Максимальное плечо
//                    'min_volume' => $data['minVol'], // Минимальный объем
//                    'max_volume' => $data['maxVol'], // Максимальный объем
//                    'volume_precision' => $data['volPrecision'], // Точность объема
//                    'price_precision' => $data['pricePrec'], // Точность цены
//                    'maintenance_margin_rate' => $data['maintMarginRate'], // Маржа поддержки
//                    'make_fee' => $data['makeFee'], // Комиссия мейкера
//                    'take_fee' => $data['takeFee'], // Комиссия тейкера
    }

    private function calcExecutionTime(float $startTime, float $endTime): float
    {
        $executionTime = ($endTime - $startTime) * 1000;
        return round($executionTime, 2);
    }

    public function openPosition(string $symbol, float $quantity, string $side = 'BUY', int $leverage = 5): array
    {
        $startTime = microtime(true);

        try {
            // Сначала проверим, что API доступен
//            $r = $this->mxcContract->market()->getDetail(['symbol' => $symbol]);



            // Установка плеча с повторными попытками
//            $retries = 3;
//            while ($retries > 0) {
//                try {
//                    $this->mxcContract->position()->setLeverage([
//                        'symbol' => $symbol,
//                        'leverage' => $leverage,
//                        'openType' => 1 // 1 - isolated margin, 2 - cross margin
//                    ]);
//                    break;
//                } catch (\Exception $e) {
//                    $retries--;
//                    if ($retries === 0) throw $e;
//                    sleep(1);
//                }
//            }

            // Открытие позиции
            $params = [
                'symbol' => $symbol,
                'price' => 0,
                'vol' => $quantity,
                'leverage' => $leverage,
                'side' => $side === 'BUY' ? 1 : 3,
                'type' => 1,
                'openType' => 1,
                'positionMode' => 1, // 1 - one-way mode
                'timeInForce' => 'IOC', // Immediate or Cancel
            ];

            Log::info('Attempting to open position', $params);

            $result = $this->mxcContract->order()->postSubmit($params);

            Log::info('Position opened successfully', [
                'symbol' => $symbol,
                'response' => $result
            ]);

            return [
                'data' => $result['data'] ?? [],
                'execution_time' => $this->calcExecutionTime($startTime, microtime(true)),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to open position', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'side' => $side,
                'leverage' => $leverage,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function validateResponse($response): void
    {
        if (empty($response)) {
            throw new \Exception('Empty response from MEXC API');
        }

        if (isset($response['code']) && $response['code'] !== 0) {
            throw new \Exception('MEXC API error: ' . ($response['msg'] ?? 'Unknown error'));
        }
    }

    public function closePosition(string $symbol, float $quantity, string $side = 'SELL'): array
    {
        $startTime = microtime(true);

        try {
            $result = $this->mxcContract->order()->postCancel([
                'symbol' => $symbol,
                'price' => 0, // 0 для рыночного ордера
                'vol' => $quantity,
                'side' => $side === 'SELL' ? 2 : 4, // 2 - close long, 4 - close short
                'type' => 1, // 1 - market order
                'openType' => 2, // 2 - cross margin
            ]);

            return [
                'data' => $result['data'] ?? [],
                'execution_time' => $this->calcExecutionTime($startTime, microtime(true)),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to close position', [
                'symbol' => $symbol,
                'quantity' => $quantity,
                'side' => $side,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function cancelAllOrders(string $symbol): array
    {
        $startTime = microtime(true);

        try {
            $result = $this->mxcContract->order()->postCancelAll([
                'symbol' => $symbol
            ]);

            return [
                'data' => $result['data'] ?? [],
                'execution_time' => $this->calcExecutionTime($startTime, microtime(true)),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to cancel all orders', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }


}
