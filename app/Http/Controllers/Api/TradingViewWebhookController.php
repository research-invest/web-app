<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TradingViewWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TradingViewWebhookController extends Controller
{
    /**
     * Прием вебхука от TradingView
     */
    public function receive(Request $request): JsonResponse
    {
        try {
            // Логируем входящий запрос для отладки
            Log::info('TradingView Webhook received', [
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Получаем сырой текст сообщения
            $rawMessage = '';
            
            // Проверяем разные способы получения данных
            if ($request->has('message')) {
                $rawMessage = $request->input('message');
            } elseif ($request->has('text')) {
                $rawMessage = $request->input('text');
            } elseif ($request->getContent()) {
                $rawMessage = $request->getContent();
            } else {
                $rawMessage = json_encode($request->all());
            }

            // Парсим данные из сообщения
            $parsedData = $this->parseWebhookMessage($rawMessage);

            // Создаем запись в базе данных
            $webhook = TradingViewWebhook::create([
                'symbol' => $parsedData['symbol'] ?? 'UNKNOWN',
                'action' => $parsedData['action'] ?? 'alert',
                'strategy' => $parsedData['strategy'] ?? null,
                'price' => $parsedData['price'] ?? null,
                'timeframe' => $parsedData['timeframe'] ?? null,
                'exchange' => $parsedData['exchange'] ?? 'unknown',
                'raw_data' => [
                    'original_message' => $rawMessage,
                    'parsed_data' => $parsedData,
                    'request_data' => $request->all(),
                    'headers' => $request->headers->all()
                ],
                'source_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            Log::info('TradingView Webhook saved successfully', [
                'webhook_id' => $webhook->id,
                'symbol' => $webhook->symbol,
                'action' => $webhook->action,
                'raw_message' => $rawMessage
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Вебхук успешно обработан',
                'webhook_id' => $webhook->id,
                'parsed_data' => $parsedData
            ], 200);

        } catch (\Exception $e) {
            Log::error('TradingView Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Внутренняя ошибка сервера'
            ], 500);
        }
    }

    /**
     * Парсинг сообщения от TradingView для извлечения данных
     */
    private function parseWebhookMessage(string $message): array
    {
        $parsed = [];
        
        // Извлекаем символ (первое слово, обычно в формате BTCUSDT, ETHUSDT)
        if (preg_match('/^([A-Z]{3,10}USDT?|[A-Z]{3,10}BTC|[A-Z]{3,10}ETH)/', $message, $matches)) {
            $symbol = $matches[1];
            // Форматируем в читаемый вид (BTCUSDT -> BTC/USDT)
            if (str_ends_with($symbol, 'USDT')) {
                $base = str_replace('USDT', '', $symbol);
                $parsed['symbol'] = $base . '/USDT';
            } elseif (str_ends_with($symbol, 'BTC')) {
                $base = str_replace('BTC', '', $symbol);
                $parsed['symbol'] = $base . '/BTC';
            } elseif (str_ends_with($symbol, 'ETH')) {
                $base = str_replace('ETH', '', $symbol);
                $parsed['symbol'] = $base . '/ETH';
            } else {
                $parsed['symbol'] = $symbol;
            }
        }

        // Определяем действие на основе ключевых слов
        $message_lower = mb_strtolower($message);
        if (strpos($message_lower, 'buy') !== false || strpos($message_lower, 'покупка') !== false || strpos($message_lower, 'long') !== false) {
            $parsed['action'] = 'buy';
        } elseif (strpos($message_lower, 'sell') !== false || strpos($message_lower, 'продажа') !== false || strpos($message_lower, 'short') !== false) {
            $parsed['action'] = 'sell';
        } elseif (strpos($message_lower, 'close') !== false || strpos($message_lower, 'закрыт') !== false) {
            $parsed['action'] = 'close';
        } else {
            $parsed['action'] = 'alert';
        }

        // Извлекаем стратегию (текст между запятой и следующими данными)
        if (preg_match('/,\s*(.+?)\s+{{/', $message, $matches)) {
            $parsed['strategy'] = trim($matches[1]);
        }

        // Извлекаем данные из плейсхолдеров
        $placeholders = [
            'ticker' => 'symbol',
            'exchange' => 'exchange', 
            'interval' => 'timeframe',
            'volume' => 'volume',
            'open' => 'open_price',
            'close' => 'price',
            'high' => 'high_price',
            'low' => 'low_price',
            'time' => 'time',
            'timenow' => 'current_time'
        ];

        foreach ($placeholders as $placeholder => $field) {
            if (preg_match('/{{' . $placeholder . '}}/', $message)) {
                $parsed['has_' . $field] = true;
            }
        }

        // Пытаемся извлечь числовые значения (цены)
        if (preg_match('/(\d+\.?\d*)/', $message, $matches)) {
            $parsed['extracted_number'] = floatval($matches[1]);
        }

        return $parsed;
    }

    /**
     * Получение списка вебхуков (для API)
     */
    public function index(Request $request): JsonResponse
    {
        $webhooks = TradingViewWebhook::query()
            ->when($request->symbol, fn($q) => $q->bySymbol($request->symbol))
            ->when($request->action, fn($q) => $q->byAction($request->action))
            ->when($request->strategy, fn($q) => $q->byStrategy($request->strategy))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $webhooks
        ]);
    }

    /**
     * Получение конкретного вебхука
     */
    public function show(TradingViewWebhook $webhook): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $webhook
        ]);
    }

    /**
     * Тестовый эндпоинт для проверки работы
     */
    public function test(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'TradingView Webhook endpoint работает корректно',
            'timestamp' => now()->toISOString()
        ]);
    }
}
