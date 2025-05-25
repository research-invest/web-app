<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lin\Gate\GateFuture;

/**
 * @doc https://www.gate.io/docs/developers/apiv4/#funding-account-list
 * @doc https://github.com/zhouaini528/gate-php
 * @site test https://www.gate.io/ru/testnet/futures_trade/USDT/BTC_USDT
 */
class GateIoService
{
    private GateFuture $future;

    public function __construct(string $apiKey, string $apiSecret, bool $isTestnet = true)
    {
        $host = $isTestnet ? 'https://fx-api-testnet.gateio.ws' : 'https://api.gateio.ws';

//        Live trading: https://api.gateio.ws/api/v4
//Futures TestNet trading: https://fx-api-testnet.gateio.ws/api/v4
//Futures live trading alternative (futures only): https://fx-api.gateio.ws/api/v4

        $this->future = new GateFuture($apiKey, $apiSecret, $host);
    }

    public function getCurrentPrice(string $symbol): array
    {
        $startTime = microtime(true);

//  "funding_rate_indicative" => "0.00005"
//  "mark_price_round" => "0.1"
//  "funding_offset" => 0
//  "in_delisting" => false
//  "risk_limit_base" => "20000"
//  "interest_rate" => "0.0003"
//  "index_price" => "209.28"
//  "order_price_round" => "0.1"
//  "order_size_min" => 1
//  "ref_rebate_rate" => "0.2"
//  "name" => "TAO_USDT"
//  "ref_discount_rate" => "0"
//  "order_price_deviate" => "0.1"
//  "maintenance_rate" => "0.01"
//  "mark_type" => "index"
//  "funding_interval" => 14400
//  "type" => "direct"
//  "risk_limit_step" => "4980000"
//  "enable_bonus" => true
//  "enable_credit" => true
//  "leverage_min" => "1"
//  "funding_rate" => "0.00005"
//  "last_price" => "209.2"
//  "mark_price" => "209.2"
//  "order_size_max" => 1000000
//  "funding_next_apply" => 1743768000
//  "short_users" => 99
//  "config_change_time" => 1742203758
//  "create_time" => 1713155329
//  "trade_size" => 211328818
//  "position_size" => 518725
//  "long_users" => 347
//  "quanto_multiplier" => "0.01"
//  "funding_impact_value" => "5000"
//  "leverage_max" => "50"
//  "cross_leverage_default" => "10"
//  "risk_limit_max" => "5000000"
//  "maker_fee_rate" => "-0.0001"
//  "taker_fee_rate" => "0.00075"
//  "orders_limit" => 100
//  "trade_id" => 3585240
//  "orderbook_id" => 722991049
//  "funding_cap_ratio" => "2"
//  "voucher_leverage" => "0"
//  "is_pre_market" => false
//] // app/Services/GateIoService.php:27


        try {
            //$result = $this->future->contract()->get(['settle' => 'usdt', 'contract' => $symbol]);

            $result = $this->future->market()->getCandlesticks([
                'settle' => 'usdt',
                'contract' => $symbol,
                'interval' => '1s',
                'limit' => 1,
            ]);

            $endTime = microtime(true);

//            0 => array:7 [
//                "o" => "0.001155"
//    "v" => 0
//    "t" => 1748152369
//    "c" => "0.001155"
//    "l" => "0.001155"
//    "h" => "0.001155"
//    "sum" => "0"
//  ]

            return [
//                'price' => $result['last_price'] ?? null,
                'open' => $result[0]['o'] ?? null,
                'close' => $result[0]['c'] ?? null,
                'low' => $result[0]['l'] ?? null,
                'high' => $result[0]['h'] ?? null,
                'execution_time' => $this->calcExecutionTime($startTime, $endTime),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get current price', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }


    private function calcExecutionTime(float $startTime, float $endTime): float
    {
        $executionTime = ($endTime - $startTime) * 1000;
        return round($executionTime, 2);
    }


    /**
     * Открыть позицию
     */
    public function openPosition($contract, $side, $type = 'limit', $size = 1, $price = null, $takeProfit = null, $stopLoss = null)
    {

//        $contracts = $this->future->contract()->get([
//            'settle' => 'usdt',
//            'contract' => $contract,
//        ]);
//
//        dd($contracts);

        try {
            $params = [
                'contract' => $contract,
                'side' => $side,
                'size' => $size,
                'type' => $type,
                'tif' => 'gtc',
            ];

            if ($type === 'limit' && $price !== null) {
                $params['price'] = $price;
            }

            // Добавим Take Profit / Stop Loss
            $closeOrders = [];

            if ($takeProfit !== null) {
                $closeOrders[] = [
                    'price' => (string)$takeProfit,
                    'size' => $size,
                    'close' => true
                ];
            }

            if ($stopLoss !== null) {
                $closeOrders[] = [
                    'price' => (string)$stopLoss,
                    'size' => $size,
                    'close' => true
                ];
            }

            if (!empty($closeOrders)) {
                $params['close_orders'] = $closeOrders;
            }

            $result = $this->future->order()->post($params);
            echo "🟢 Ордер с TP/SL отправлен: " . json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . PHP_EOL;
            return $result;
        } catch (\Exception $e) {
            echo "❌ Ошибка при открытии позиции: " . $e->getMessage() . PHP_EOL;
            return null;
        }
    }

    /**
     * Закрыть позицию маркет-ордом (противоположным)
     */
    public function closePosition($contract)
    {
        try {
            // Узнаем текущую позицию
            $position = $this->future->position()->get(['contract' => $contract]);
            if (empty($position) || $position['size'] == 0) {
                echo "ℹ️ Нет открытых позиций по $contract" . PHP_EOL;
                return null;
            }

            $side = $position['size'] > 0 ? 'sell' : 'buy';

            $result = $this->future->order()->post([
                'contract' => $contract,
                'side' => $side,
                'size' => abs($position['size']),
                'type' => 'market',
            ]);

            echo "🔴 Позиция закрыта: " . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
            return $result;
        } catch (\Exception $e) {
            echo "❌ Ошибка при закрытии позиции: " . $e->getMessage() . PHP_EOL;
            return null;
        }
    }

    /**
     * Проверка баланса
     */
    public function checkBalance($currency = 'USDT')
    {
        try {
            $balances = $this->future->account()->get([
                'settle' => $currency
            ]);

            dd($balances);
            foreach ($balances as $balance) {
                if ($balance['currency'] === strtoupper($currency)) {
                    echo "💰 Баланс {$currency}: " . $balance['available'] . PHP_EOL;
                    return $balance;
                }
            }

            echo "❌ Валюта $currency не найдена" . PHP_EOL;
            return null;
        } catch (\Exception $e) {
            echo "❌ Ошибка при получении баланса: " . $e->getMessage() . PHP_EOL;
            return null;
        }
    }


    /**
     * Открыть позицию
     *
     * @param string $symbol Торговая пара (например, 'BTC_USDT')
     * @param float $quantity Размер позиции
     * @param string $side Сторона (BUY или SELL)
     * @param int $leverage Плечо
     * @return array
     */
    public function openPosition1(string $symbol, float $quantity, string $side = 'BUY', int $leverage = 5): array
    {

        $response = $this->future->position()->postLeverage([
            'settle' => 'usdt',
            'contract' => $symbol,
            'size' => $quantity,
            'price' => 0, // 0 для рыночного ордера
            'tif' => 'ioc', // immediate-or-cancel для рыночного ордера
            'side' => $side,
        ]);

        return [
            'success' => true,
            'data' => $response,
        ];


        try {
//            $this->future->privates()->postPositionLeverage([
//                'settle' => 'usdt',
//                'contract' => $symbol,
//                'leverage' => $leverage,
//            ]);

            // Открываем позицию

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Закрыть конкретную позицию
     *
     * @param string $symbol Торговая пара
     * @param float $quantity Размер позиции
     * @param string $side Сторона (противоположная открытию)
     * @return array
     */
    public function closePositio2n2(string $symbol, float $quantity, string $side): array
    {
        try {
            $response = $this->future->privates()->postOrder([
                'settle' => 'usdt',
                'contract' => $symbol,
                'size' => $quantity,
                'price' => 0, // рыночный ордер
                'tif' => 'ioc',
                'side' => $side,
                'close' => true, // указываем что это закрытие позиции
            ]);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Закрыть все открытые позиции
     *
     * @return array
     */
    public function closeAllPositions3(): array
    {
        try {
            // Получаем все открытые позиции
            $positions = $this->future->privates()->getPositions([
                'settle' => 'usdt',
            ]);

            $results = [];

            foreach ($positions as $position) {
                if ($position['size'] == 0) continue; // Пропускаем уже закрытые позиции

                // Определяем сторону для закрытия (противоположную текущей позиции)
                $closeSide = $position['size'] > 0 ? 'SELL' : 'BUY';

                // Закрываем каждую позицию
                $result = $this->closePosition(
                    $position['contract'],
                    abs($position['size']),
                    $closeSide
                );

                $results[$position['contract']] = $result;
            }

            return [
                'success' => true,
                'data' => $results,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Получить информацию о позиции
     *
     * @param string $symbol Торговая пара
     * @return array
     */
    public function getPosition3(string $symbol): array
    {
        try {
            $response = $this->future->privates()->getPosition([
                'settle' => 'usdt',
                'contract' => $symbol,
            ]);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }


}
