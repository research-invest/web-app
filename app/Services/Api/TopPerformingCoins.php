<?php

namespace App\Services\Api;

class TopPerformingCoins
{

    /**
     * /get-top-performing-coins?min_price=20&min_volume_diff=50
     * @return array|\GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response|mixed
     * @throws \JsonException
     */
    public function getTopPerformingCoins(
        int    $priceChangePercent,
        int    $minVolumeDiff,
    ): array
    {
        $runner = new Api('/get-top-performing-coins', [
            'price_change_percent' => $priceChangePercent,
            'volume_diff_percent' => $minVolumeDiff,
        ]);
        return $runner->execute();
    }
}
