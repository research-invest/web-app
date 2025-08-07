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
        $percentages = [3, 5, 8];

        // Binance REST API depth
        $response = Http::get("https://api.binance.com/api/v3/depth", [
            'symbol' => $symbol,
            'limit' => 100
        ]);

        if (!$response->ok()) {
            return response()->json(['error' => 'Failed to fetch order book'], 500);
        }

        $depth = $response->json();
        $price = (float)$depth['bids'][0][0]; // best bid

        $result = [];

        foreach ($percentages as $pct) {
            $range = $price * ($pct / 100);

            $buyVol = collect($depth['bids'])
                ->filter(fn($bid) => (float)$bid[0] >= $price - $range)
                ->sum(fn($bid) => (float)$bid[1]);

            $sellVol = collect($depth['asks'])
                ->filter(fn($ask) => (float)$ask[0] <= $price + $range)
                ->sum(fn($ask) => (float)$ask[1]);

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
