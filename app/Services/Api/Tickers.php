<?php

namespace App\Services\Api;

class Tickers
{

    /**
     * /tickers?symbol=TAOUSDT&interval=60&start=2024-10-11T04:49:14.859761%2B03:00&end=2024-10-11T04:50:14.859761%2B03:00
     * @return array|\GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response|mixed
     * @throws \JsonException
     */
    public function getTickers(
        string $symbol,
        int    $interval,
        string $start = '',
        string $end = ''
    ): array
    {
        $runner = new Api('/tickers', [
            'symbol' => $symbol,
            'interval' => $interval,
            'start' => $start,
            'end' => $end,
        ]);
        return $runner->execute();
    }
}
