<?php

namespace App\Services\External\Coingecko\Coins;

use App\Services\External\Coingecko\Coingecko;

class Tickers
{
    /**
     * https://docs.coingecko.com/v3.0.1/reference/coins-id-tickers
     * @return \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response|mixed
     */
    public function getTickers(string $id = 'tontoken')
    {
        $runner = new Coingecko(sprintf('/coins/%s/tickers', $id), []);
        return $runner->execute();
    }
}
