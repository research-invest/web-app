<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class RequestHelper
{
    /**
     * @param $url
     * @param int $maxRetries - кол-во попыток
     * @param int $retryInterval - задержка в секундах
     * @return array|mixed|null
     */
    public static function getDataUrlWithRetry($url, int $maxRetries = 5, int $retryInterval = 1)
    {
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            try {
                $response = Http::get($url);

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (RequestException $e) {
                Log::error('Request failed: ' . $e->getMessage());
            }

            sleep($retryInterval);

            $retryCount++;
        }

        return null;
    }
}
