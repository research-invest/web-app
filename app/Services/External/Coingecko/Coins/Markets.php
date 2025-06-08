<?php

namespace App\Services\External\Coingecko\Coins;

use App\Services\External\Coingecko\Coingecko;

class Markets
{

    /**
     * hhttps://docs.coingecko.com/v3.0.1/reference/coins-markets
     * @param string $vsCurrency
     * @param int $page
     * @param int $perPage
     * @return array|\GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response|mixed
     * @throws \JsonException
     */
    public function getMarkets(string $vsCurrency = 'usd', int $page = 1, int $perPage = 250)
    {
        $runner = new Coingecko(sprintf('/coins/markets'), [
            'vs_currency' => $vsCurrency,
            'order' => 'market_cap_desc',
            'page' => $page,
            'per_page' => $perPage,
        ]);
        return $runner->execute();
    }
}
