<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TradingViewWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{

    /**
     * Получение списка уведомлений для мобильного приложения
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->per_page ?? 50, 100); // Максимум 100 за раз

        $webhooks = TradingViewWebhook::query()
            ->select(['id', 'symbol', 'action', 'strategy', 'price', 'exchange', 'timeframe', 'is_read', 'created_at'])
            ->when($request->unread_only === 'true', fn($q) => $q->unread())
            ->when($request->symbol, fn($q) => $q->where('symbol', 'like', '%' . $request->symbol . '%'))
            ->when($request->action, fn($q) => $q->byAction($request->action))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Форматируем данные для мобильного приложения
        $formattedData = $webhooks->map(function ($webhook) {
            return [
                'id' => $webhook->id,
                'symbol' => $webhook->symbol,
                'action' => $webhook->action,
                'strategy' => $webhook->strategy,
                'price' => $webhook->price ? number_format($webhook->price, 8) : null,
                'exchange' => $webhook->exchange,
                'timeframe' => $webhook->timeframe,
                'is_read' => $webhook->is_read,
                'created_at' => $webhook->created_at->toISOString(),
                'created_at_human' => $webhook->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedData,
            'pagination' => [
                'current_page' => $webhooks->currentPage(),
                'per_page' => $webhooks->perPage(),
                'total' => $webhooks->total(),
                'last_page' => $webhooks->lastPage(),
                'has_more' => $webhooks->hasMorePages(),
            ],
            'counters' => [
                'total_unread' => TradingViewWebhook::unread()->count(),
                'total_read' => TradingViewWebhook::read()->count(),
                'total' => TradingViewWebhook::count()
            ]
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
     * Пометить уведомление как прочитанное
     */
    public function markAsRead(TradingViewWebhook $webhook): JsonResponse
    {
        $webhook->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Уведомление помечено как прочитанное',
            'data' => [
                'id' => $webhook->id,
                'is_read' => $webhook->is_read,
                'updated_at' => $webhook->updated_at->toISOString()
            ]
        ]);
    }

    /**
     * Пометить уведомление как непрочитанное
     */
    public function markAsUnread(TradingViewWebhook $webhook): JsonResponse
    {
        $webhook->markAsUnread();

        return response()->json([
            'success' => true,
            'message' => 'Уведомление помечено как непрочитанное',
            'data' => [
                'id' => $webhook->id,
                'is_read' => $webhook->is_read,
                'updated_at' => $webhook->updated_at->toISOString()
            ]
        ]);
    }

    /**
     * Пометить несколько уведомлений как прочитанные
     */
    public function markMultipleAsRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:trading_view_webhooks,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $updated = TradingViewWebhook::whereIn('id', $request->ids)
            ->update(['is_read' => true, 'updated_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Помечено как прочитанное: {$updated} уведомлений",
            'data' => [
                'updated_count' => $updated,
                'ids' => $request->ids
            ]
        ]);
    }

    /**
     * Пометить все уведомления как прочитанные
     */
    public function markAllAsRead(): JsonResponse
    {
        $updated = TradingViewWebhook::unread()->update([
            'is_read' => true,
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => "Все уведомления помечены как прочитанные",
            'data' => [
                'updated_count' => $updated
            ]
        ]);
    }

    /**
     * Получить статистику уведомлений
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => TradingViewWebhook::count(),
            'unread' => TradingViewWebhook::unread()->count(),
            'read' => TradingViewWebhook::read()->count(),
            'today' => TradingViewWebhook::whereDate('created_at', today())->count(),
            'this_week' => TradingViewWebhook::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'by_action' => TradingViewWebhook::selectRaw('action, count(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
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
