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

            $rawMessage = $request->getContent();

            $parsedData = $this->parseWebhookMessage($rawMessage);

            Log::info('TradingView Webhook parseWebhookMessage', [
                'rawMessage' => $rawMessage,
                'parsedData' => $parsedData,
            ]);

            // Создаем запись в базе данных
            $webhook = TradingViewWebhook::create([
                'symbol' => $parsedData['symbol'],
                'action' => $parsedData['action'],
                'strategy' => $parsedData['strategy'],
                'price' => $parsedData['price'],
                'timeframe' => $parsedData['timeframe'],
                'exchange' => $parsedData['exchange'],
                'raw_data' => [
                    'original_message' => $rawMessage,
                    'parsed_data' => $parsedData,
                    'request_data' => $request->all(),
                    'headers' => $request->headers->all(),
                    'volume' => $parsedData['volume'],
                    'time' => $parsedData['time']
                ],
                'source_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Вебхук успешно обработан',
                'webhook_id' => $webhook->id,
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
     * Формат: SYMBOL ACTION STRATEGY EXCHANGE INTERVAL PRICE VOLUME TIME
     * Пример: PUMPUSDT BUY Стратегия BYBIT 1 0.003116 98424.6 2025-08-10T10:24:00Z
     */
    private function parseWebhookMessage(string $message): array
    {
        $parsed = [];

        // Убираем лишние пробелы и разбиваем по пробелам
        $parts = array_filter(explode(' ', trim($message)));

        // Если частей меньше 3, то это неполное сообщение
        if (count($parts) < 3) {
            $parsed['symbol'] = 'UNKNOWN';
            $parsed['action'] = 'alert';
            $parsed['strategy'] = $message;
            return $parsed;
        }

        // Парсим по позициям (фиксированный формат)
        $parsed['symbol'] = $parts[0] ?? 'UNKNOWN';           // PUMPUSDT
        $parsed['action'] = $this->normalizeAction($parts[1] ?? 'alert'); // BUY
        $parsed['strategy'] = $parts[2] ?? null;              // Стратегия
        $parsed['exchange'] = $parts[3] ?? null;              // BYBIT
        $parsed['timeframe'] = $parts[4] ?? null;             // 1
        $parsed['price'] = isset($parts[5]) ? floatval($parts[5]) : null; // 0.003116
        $parsed['volume'] = isset($parts[6]) ? floatval($parts[6]) : null; // 98424.6
        $parsed['time'] = $parts[7] ?? null;                  // 2025-08-10T10:24:00Z

        return $parsed;
    }

    /**
     * Нормализация действия
     */
    private function normalizeAction(string $action): string
    {
        $action = strtolower($action);

        if (in_array($action, ['buy', 'покупка', 'long'])) {
            return 'buy';
        } elseif (in_array($action, ['sell', 'продажа', 'short'])) {
            return 'sell';
        } elseif (in_array($action, ['close', 'закрыт'])) {
            return 'close';
        }

        return 'alert';
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
