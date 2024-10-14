<?php

namespace App\Services\Api;

class LatestPrice
{

    /**
     * /latest-prices?currencies=taousdt,btcusdt
     * @return array|\GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response|mixed
     * @throws \JsonException
     */
    public function getLatestPrices(array $currencies): array
    {
        $runner = new Api('/latest-prices', [
                'currencies' => implode(',', $currencies),
            ]);

        return $runner->execute();
    }
}
