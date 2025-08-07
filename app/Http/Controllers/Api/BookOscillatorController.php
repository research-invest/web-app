<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BookOscillatorController extends Controller
{
    public function get(Request $request)
    {
        $symbol = strtoupper($request->query('symbol', 'BTCUSDT'));

        $response = Http::get("https://api.binance.com/api/v3/depth", [
            'symbol' => $symbol,
            'limit' => 100
        ]);

        if (!$response->ok()) {
            return response()->json(['error' => 'Failed to fetch order book'], 500);
        }

        $depth = $response->json();
        $price = (float)$depth['bids'][0][0]; // best bid

        // Количество уровней стакана, которые мы анализируем
        $depthMap = [
            3 => 10,
            5 => 30,
            8 => 60
        ];

        $result = [];

        foreach ($depthMap as $pct => $levels) {
            $buyVol = collect($depth['bids'])->take($levels)->sum(fn($b) => (float)$b[1]);
            $sellVol = collect($depth['asks'])->take($levels)->sum(fn($a) => (float)$a[1]);

            $osc = ($buyVol - $sellVol) / max($buyVol + $sellVol, 1e-9);
            $result["osc_{$pct}"] = round($osc, 4);
        }

        return response()->json([
            'symbol' => $symbol,
            'price' => $price,
            'oscillator' => $result,
        ]);
    }
}
