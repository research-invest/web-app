<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MexcService
{
    private const BASE_URL = 'https://contract.mexc.com/api/v1/contract';

    public function getCurrentPrice(string $symbol): float
    {

//        array:3 [
//        "success" => true
//  "code" => 0
//  "data" => array:20 [
//        "contractId" => 96
//    "symbol" => "LIT_USDT"
//    "lastPrice" => 0.7807
//    "bid1" => 0.7806
//    "ask1" => 0.7807
//    "volume24" => 274486787
//    "amount24" => 18245169.31272
//    "holdVol" => 28867950
//    "lower24Price" => 0.5265
//    "high24Price" => 0.8084
//    "riseFallRate" => 0.1953
//    "riseFallValue" => 0.1276
//    "indexPrice" => 0.8262
//    "fairPrice" => 0.7813
//    "fundingRate" => -0.025
//    "maxBidPrice" => 1.2393
//    "minAskPrice" => 0.4131
//    "timestamp" => 1738433189245
//    "riseFallRates" => array:8 [
//        "zone" => "UTC+8"
//      "r" => 0.1953
//      "v" => 0.1276
//      "r7" => 0.2861
//      "r30" => -0.1909
//      "r90" => 0.4246
//      "r180" => 0.4377
//      "r365" => -0.0229
//    ]
//    "riseFallRatesOfTimezone" => array:3 [
//        0 => 0.4406
//      1 => 0.4655
//      2 => 0.1953
//    ]
//  ]
//]
            $response = Http::get(self::BASE_URL . "/ticker", [
            'symbol' => $symbol
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['data']['lastPrice'];
        }

        throw new \Exception("Failed to get price for {$symbol}");
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
            $maxPositionSize = $maxVolume * $currentPrice;

            return $maxPositionSize;
        } catch (\Exception $e) {
            Log::error('Failed to calculate max position size', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
