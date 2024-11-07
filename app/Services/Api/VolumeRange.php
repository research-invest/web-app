<?php

namespace App\Services\Api;

class VolumeRange
{

    /**
     * /volumes/by-range?symbol=TAOUSDT&interval=20
     * @return array|\GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response|mixed
     * @throws \JsonException
     */
    public function getVolumeByRange(
        string    $symbol,
        int    $interval,
    ): array
    {
        $runner = new Api('/volumes/by-range', [
            'symbol' => $symbol,
            'interval' => $interval,
        ]);
        return $runner->execute();
    }
}
