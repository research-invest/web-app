<?php

namespace App\Services\External\Coingecko;

use Illuminate\Support\Facades\Http;

class Coingecko
{

    protected string $url;
    protected mixed $params;
    protected string $basepath = 'https://api.coingecko.com/api/v3';
    private \Illuminate\Http\Client\PendingRequest $response;

    public function __construct(string $url, $params = [])
    {
        $this->url = $url;
        $this->params = $params;

        $this->response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @return \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response|mixed
     */
    public function execute()
    {
        $response = $this->response->get($this->basepath . $this->url, $this->params);

        if ($response->getStatusCode() === 200) {
            return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        }

        return [
            'error' => (string)$response->getBody(),
            'status' => $response->getStatusCode(),
        ];
    }
}
