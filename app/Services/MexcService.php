<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lin\Mxc\MxcContract;

class MexcService
{
    private const string BASE_URL = 'https://contract.mexc.com/api';
    private string $apiKey;
    private string $apiSecret;

    public function __construct(string $apiKey, string $apiSecret)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    private function generateSignature(array $params, string $timestamp): string
    {
        $queryString = http_build_query($params);
        $signString = $timestamp . $this->apiKey . $queryString;
        return hash_hmac('sha256', $signString, $this->apiSecret);
    }

    private function makeAuthenticatedRequest(string $method, string $endpoint, array $params = [])
    {
        $timestamp = now()->timestamp * 1000; // MEXC требует миллисекунды

        // Для POST запросов тело должно быть в JSON формате
        $requestBody = $method === 'post' ? json_encode($params) : '';

        // Для GET запросов параметры идут в query string
        $queryString = $method === 'get' ? '?' . http_build_query($params) : '';

        // Строка для подписи отличается для GET и POST
        $signString = $timestamp . $this->apiKey . ($method === 'post' ? $requestBody : $queryString);
        $signature = hash_hmac('sha256', $signString, $this->apiSecret);

        $headers = [
            'ApiKey' => $this->apiKey,
            'Request-Time' => $timestamp,
            'Signature' => $signature,
            'Content-Type' => 'application/json',
        ];

        $url = self::BASE_URL . $endpoint . ($method === 'get' ? $queryString : '');

        if ($method === 'post') {
            return Http::withHeaders($headers)
                ->timeout(5)
                ->post($url, $params);
        }

        return Http::withHeaders($headers)
            ->timeout(5)
            ->get($url);
    }

    public function getCurrentPrice(string $symbol): array
    {
        $startTime = microtime(true);

        try {

            $mexc = new MxcContract($this->apiKey, $this->apiSecret);
            $result = $mexc->market()->getFairPrice(['symbol' => 'TAO_USDT']);
            $endTime = microtime(true);

            $executionTime = ($endTime - $startTime) * 1000; // Конвертируем в миллисекунды

            return [
                'price' => $result['data']['fairPrice'] ?? null,
                'execution_time' => round($executionTime, 2),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get current price', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getContractInfo(string $symbol)
    {

//        array:64 [
//        "symbol" => "HBAR_USDT"
//  "displayName" => "HBAR_USDT永续"
//  "displayNameEn" => "HBAR_USDT PERPETUAL"
//  "positionOpenType" => 3
//  "baseCoin" => "HBAR"
//  "quoteCoin" => "USDT"
//  "baseCoinName" => "HBAR"
//  "quoteCoinName" => "USDT"
//  "futureType" => 1
//  "settleCoin" => "USDT"
//  "contractSize" => 1
//  "minLeverage" => 1
//  "maxLeverage" => 200
//  "countryConfigContractMaxLeverage" => 0
//  "priceScale" => 5
//  "volScale" => 0
//  "amountScale" => 4
//  "priceUnit" => 1.0E-5
//  "volUnit" => 1
//  "minVol" => 1
//  "maxVol" => 2700000
//  "bidLimitPriceRate" => 0.2
//  "askLimitPriceRate" => 0.2
//  "takerFeeRate" => 0.0002
//  "makerFeeRate" => 0
//  "maintenanceMarginRate" => 0.004
//  "initialMarginRate" => 0.005
//  "riskBaseVol" => 2700000
//  "riskIncrVol" => 2700000
//  "riskLongShortSwitch" => 0
//  "riskIncrMmr" => 0.056
//  "riskIncrImr" => 0.095
//  "riskLevelLimit" => 1
//  "priceCoefficientVariation" => 0.2
//  "indexOrigin" => array:5 [
//        0 => "BYBIT"
//    1 => "BINANCE"
//    2 => "OKX"
//    3 => "MEXC"
//    4 => "KUCOIN"
//  ]
//  "state" => 0
//  "isNew" => false
//  "isHot" => false
//  "isHidden" => false
//  "conceptPlate" => []
//  "conceptPlateId" => []
//  "riskLimitType" => "BY_VOLUME"
//  "maxNumOrders" => array:2 [
//        0 => 200
//    1 => 50
//  ]
//  "marketOrderMaxLevel" => 20
//  "marketOrderPriceLimitRate1" => 0.2
//  "marketOrderPriceLimitRate2" => 0.005
//  "triggerProtect" => 0.1
//  "appraisal" => 0
//  "showAppraisalCountdown" => 0
//  "automaticDelivery" => 0
//  "apiAllowed" => false
//  "depthStepList" => array:3 [
//        0 => "0.00001"
//    1 => "0.0001"
//    2 => "0.001"
//  ]
//  "limitMaxVol" => 2700000
//  "threshold" => 0
//  "baseCoinIconUrl" => "https://public.mocortech.com/coin/F20230701191651284uwTj2nQVcafJwU.png"
//  "id" => 106
//  "vid" => "128f589271cb4951b03e71e6323eb7be"
//  "baseCoinId" => "770aa628c79140ffa3ae8d16215dc8cb"
//  "createTime" => 1617155502000
//  "openingTime" => 0
//  "openingCountdownOption" => 1
//  "showBeforeOpen" => true
//  "isMaxLeverage" => false
//  "isZeroFeeRate" => false
//]


        try {
            $response = Http::get(self::BASE_URL . "/detail", [
                'symbol' => $symbol
            ]);

            if ($response->successful()) {
                $data = $response->json()['data'];

                dd($data);
                return [
                    'max_leverage' => $data['maxLeverage'], // Максимальное плечо
                    'min_volume' => $data['minVol'], // Минимальный объем
                    'max_volume' => $data['maxVol'], // Максимальный объем
                    'volume_precision' => $data['volPrecision'], // Точность объема
                    'price_precision' => $data['pricePrec'], // Точность цены
                    'maintenance_margin_rate' => $data['maintMarginRate'], // Маржа поддержки
                    'make_fee' => $data['makeFee'], // Комиссия мейкера
                    'take_fee' => $data['takeFee'], // Комиссия тейкера
                ];
            }

            throw new \Exception("Failed to get contract info: " . $response->body());
        } catch (\Exception $e) {
            Log::error('Failed to get contract info', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function calculateMaxPositionSize(string $symbol): float
    {
        try {
            $contractInfo = $this->getContractInfo($symbol);
            $currentPrice = $this->getCurrentPrice($symbol);

            // Максимальный объем в контрактах
            $maxVolume = $contractInfo['max_volume'];

            // Переводим в USD
            $maxPositionSize = $maxVolume * $currentPrice['price'];

            return $maxPositionSize;
        } catch (\Exception $e) {
            Log::error('Failed to calculate max position size', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function openPosition(string $symbol, float $quantity, string $side = 'BUY', int $leverage = 5)
    {
        try {
            $params = [
                'symbol' => $symbol,
                'vol' => $quantity,
                'leverage' => $leverage,
                'side' => $side === 'BUY' ? 1 : 3, // 1 - open long, 3 - open short
                'type' => 5, // 5 - market order
                'openType' => 1, // open type,1:isolated,2:cross
            ];

            $response = $this->makeAuthenticatedRequest('post', '/v1/private/order/submit', $params);

            if (!$response->successful()) {
                throw new \Exception("Failed to open position: " . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Failed to open position', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function closePosition(string $symbol, float $quantity, string $side = 'SELL', ?string $positionId = null)
    {
        try {
            $params = [
                'symbol' => $symbol,
                'vol' => $quantity,
                'side' => $side === 'SELL' ? 2 : 4, // 2 - close short, 4 - close long
                'type' => 5, // 5 - market order
                'openType' => 2, // 2 - cross margin
            ];

            // Добавляем positionId если он предоставлен
            if ($positionId) {
                $params['positionId'] = $positionId;
            }

            $response = $this->makeAuthenticatedRequest('post', '/v1/private/order/submit_batch', $params);

            if (!$response->successful()) {
                throw new \Exception("Failed to close position: " . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Failed to close position', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }


    public function cancelAll(string $symbol = '')
    {
        try {
            $params = [
                'symbol' => $symbol,
            ];

            $response = $this->makeAuthenticatedRequest('post', '/v1/private/order/cancel_all', $params);

            if (!$response->successful()) {
                throw new \Exception("Failed to cancel all: " . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Failed to cancel all', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }


}
