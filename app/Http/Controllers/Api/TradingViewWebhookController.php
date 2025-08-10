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

            Log::info('TradingView Webhook parseWebhookMessage', [
                'rawMessage' => $rawMessage,
                'parsedData' => $parsedData,
            ]);

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
        
        // Убираем лишние пробелы
        $message = trim($message);
        
        // Сначала заменяем все плейсхолдеры на маркеры, чтобы получить чистую структуру
        $cleanMessage = $message;
        $placeholders = [
            '{{ticker}}', '{{exchange}}', '{{interval}}', '{{close}}', '{{open}}', 
            '{{high}}', '{{low}}', '{{volume}}', '{{time}}', '{{timenow}}'
        ];
        
        foreach ($placeholders as $placeholder) {
            $cleanMessage = str_replace($placeholder, '[PLACEHOLDER]', $cleanMessage);
        }
        
        // Разбиваем по пробелам, убирая плейсхолдеры
        $parts = array_filter(explode(' ', $cleanMessage), function($part) {
            return $part !== '[PLACEHOLDER]' && !empty(trim($part));
        });
        
        // Переиндексируем массив
        $parts = array_values($parts);
        
        // Парсим по позициям:
        // Позиция 0: может быть символом (если не плейсхолдер) или действием
        // Позиция 1: действие (BUY/SELL/CLOSE) или стратегия
        // Позиция 2+: название стратегии
        
        $symbolFromText = null;
        $actionFromText = null;
        $strategyParts = [];
        
        foreach ($parts as $index => $part) {
            $partUpper = strtoupper($part);
            $partLower = strtolower($part);
            
            // Проверяем, является ли это действием
            if (in_array($partUpper, ['BUY', 'SELL', 'CLOSE']) || 
                in_array($partLower, ['покупка', 'продажа', 'закрыт', 'long', 'short'])) {
                $actionFromText = $this->normalizeAction($part);
                continue;
            }
            
            // Проверяем, является ли это символом (содержит USDT, BTC и т.д.)
            if (preg_match('/^[A-Z]{3,10}(USDT|BTC|ETH)$/i', $part)) {
                $symbolFromText = $this->formatSymbol($part);
                continue;
            }
            
            // Все остальное считаем частью стратегии
            $strategyParts[] = $part;
        }
        
        // Устанавливаем распарсенные данные
        $parsed['symbol'] = $symbolFromText ?: 'UNKNOWN';
        $parsed['action'] = $actionFromText ?: 'alert';
        $parsed['strategy'] = !empty($strategyParts) ? implode(' ', $strategyParts) : null;
        
        // Определяем какие плейсхолдеры присутствуют
        $this->detectPlaceholders($message, $parsed);
        
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
     * Форматирование символа
     */
    private function formatSymbol(string $symbol): string
    {
        $symbol = strtoupper($symbol);
        
        if (str_ends_with($symbol, 'USDT')) {
            $base = str_replace('USDT', '', $symbol);
            return $base . '/USDT';
        } elseif (str_ends_with($symbol, 'BTC')) {
            $base = str_replace('BTC', '', $symbol);
            return $base . '/BTC';
        } elseif (str_ends_with($symbol, 'ETH')) {
            $base = str_replace('ETH', '', $symbol);
            return $base . '/ETH';
        }
        
        return $symbol;
    }
    
    /**
     * Определение присутствующих плейсхолдеров
     */
    private function detectPlaceholders(string $message, array &$parsed): void
    {
        $placeholders = [
            'ticker' => 'symbol_placeholder',
            'exchange' => 'exchange',
            'interval' => 'timeframe',
            'volume' => 'volume',
            'open' => 'open_price',
            'close' => 'close_price',
            'high' => 'high_price',
            'low' => 'low_price',
            'time' => 'time',
            'timenow' => 'current_time'
        ];

        foreach ($placeholders as $placeholder => $field) {
            if (strpos($message, '{{' . $placeholder . '}}') !== false) {
                $parsed['has_' . $field] = true;
            }
        }
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
