<?php

namespace App\Services\Api;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 */
class Api
{
    protected string $url;
    protected mixed $params;
    protected string $basepath = '';
    private \Illuminate\Http\Client\PendingRequest $response;

    public function __construct(string $url, $params = [], $headers = [])
    {
        $this->url = $url;
        $this->basepath = config('services.api.server');
        $this->params = array_merge($params, [
        ]);

        $this->response = Http::withHeaders(array_merge([
            'Content-Type' => 'application/json',
        ], $headers));
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
