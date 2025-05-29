<?php

namespace App\Services\External\Coingecko\Simple;

use App\Services\External\Coingecko\Coingecko;

class Price
{
    /**
     * https://docs.coingecko.com/v3.0.1/reference/simple-price
     * @return \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response|mixed
     */
    public function getPrice(array $params)
    {
        //https://api.coingecko.com/api/v3/simple/price?ids=the-open-network&vs_currencies=usd
        //https://api.coingecko.com/api/v3/coins/list

        $runner = new Coingecko('/simple/price', $params);
        return $runner->execute();
    }
}
