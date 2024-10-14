<?php

namespace App\Services\Api;

class Currencies
{

    /**
     * /currencies
     * @return array|\GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response|mixed
     * @throws \JsonException
     */
    public function getCurrencies(): array
    {
        $runner = new Api('/currencies');
        return $runner->execute();
    }
}
