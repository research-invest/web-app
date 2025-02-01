<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MexcService
{
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




        $response = Http::get("https://contract.mexc.com/api/v1/contract/ticker", [
            'symbol' => $symbol
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['data']['lastPrice'];
        }

        throw new \Exception("Failed to get price for {$symbol}");
    }
}
