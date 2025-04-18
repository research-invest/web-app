<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BlockonomicsService
{
    private string $apiUrl = 'https://www.blockonomics.co/api';
    private string $apiKey = '';

    public function __construct()
    {
        $this->apiKey = config('services.blockonomics.api_key', '');
    }

    /**
     * Получить балансы для списка адресов
     *
     * @param array $addresses Массив BTC адресов
     * @return array
     * @throws \Exception
     */
    public function getBalances(array $addresses): array
    {
        // Разбиваем адреса на чанки по 50 штук (ограничение API)
        $chunks = array_chunk($addresses, 50);
        $result = [];

        foreach ($chunks as $chunk) {
            // Формируем строку адресов, разделенных пробелами
            $addressString = implode(' ', $chunk);

            try {

                $response = $this->getDataUrlWithRetry($addressString, 10,  5);

                if ($response) {
                    $result = array_merge($result, $this->processResponse($response));
                } else {
                    throw new \Exception("API вернул ошибку");
                }
            } catch (\Exception $e) {
                throw new \Exception("Ошибка при запросе к API: " . $e->getMessage());
            }
        }

        return $result;
    }

    private  function getDataUrlWithRetry($addressString, int $maxRetries = 5, int $retryInterval = 1)
    {
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}"
                ])->get("{$this->apiUrl}/balance", [
                    'addr' => $addressString
                ]);

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

    /**
     * Обработка ответа от API
     */
    private function processResponse(array $data): array
    {
        $result = [];
        foreach ($data['response'] as $item) {
//            $result[$item['addr']] = [
//                'balance' => $item['confirmed'] / 100000000, // Конвертация сатоши в BTC
//            ];
            $result[$item['addr']] = $item['confirmed'] / 100_000_000;
        }

        return $result;
    }

    /**
     * Получить баланс для одного адреса с кешированием
     */
    public function getCachedBalance(string $address): array
    {
        $cacheKey = "btc_balance_{$address}";

        return Cache::remember($cacheKey, 3600, function () use ($address) {
            $balances = $this->getBalances([$address]);
            return $balances[$address] ?? 0;
        });
    }
}
