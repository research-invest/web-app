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

            // Базовая валидация
            $validator = Validator::make($request->all(), [
                'symbol' => 'required|string|max:50',
                'action' => 'required|string|max:20',
            ]);

            if ($validator->fails()) {
                Log::warning('TradingView Webhook validation failed', [
                    'errors' => $validator->errors(),
                    'payload' => $request->all()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации данных',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Создаем запись в базе данных
            $webhook = TradingViewWebhook::create([
                'symbol' => $request->input('symbol'),
                'action' => $request->input('action'),
                'strategy' => $request->input('strategy'),
                'price' => $request->input('price'),
                'timeframe' => $request->input('timeframe'),
                'exchange' => $request->input('exchange', 'unknown'),
                'raw_data' => $request->all(),
                'source_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            Log::info('TradingView Webhook saved successfully', [
                'webhook_id' => $webhook->id,
                'symbol' => $webhook->symbol,
                'action' => $webhook->action
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Вебхук успешно обработан',
                'webhook_id' => $webhook->id
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
