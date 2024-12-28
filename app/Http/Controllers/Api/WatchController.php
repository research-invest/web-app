<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trade;
use App\Models\Notification;
use App\Models\TradeOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WatchController extends Controller
{
    /**
     * Получить всю информацию для часов
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $data = [
            'summary' => [
                'total_pnl' => $this->calculateTotalPnl($user),
                'today_pnl' => $this->calculateTodayPnl($user),
                'active_trades' => Trade::where('user_id', 1)
                    ->where('status', Trade::STATUS_OPEN)
                    ->count(),
            ],

            // Активные сделки
            'trades' => Trade::where('user_id', 1)
                ->where('status', Trade::STATUS_OPEN)
                ->latest()
                ->take(10)
                ->get()
                ->map(function (Trade $trade) {
                    return [
                        'id' => $trade->id,
                        'symbol' => $trade->currency->code,
                        'type' => $trade->position_type, // buy/sell
                        'entry_price' => $trade->entry_price,
                        'current_price' => $trade->currency->last_price,
                        'pnl' => $trade->currentPnL,
                        'can_cancel' => true,
                    ];
                }),

            // Непрочитанные уведомления
            'notifications' => [],

//            Notification::where('user_id', 1)
//                ->where('read', false)
//                ->latest()
//                ->take(5)
//                ->get()
//                ->map(function ($notification) {
//                    return [
//                        'id' => $notification->id,
//                        'type' => $notification->type,
//                        'message' => $notification->message,
//                        'created_at' => $notification->created_at,
//                    ];
//                })


        ];

        return response()->json($data);
    }

    /**
     * Получить список сделок
     */
    public function trades(Request $request): JsonResponse
    {
        $user = auth()->user();

        $trades = Trade::where('user_id', $user->id)
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->paginate(20);

        return response()->json($trades);
    }

    /**
     * Отменить сделку
     */
    public function cancelTrade(Trade $trade): JsonResponse
    {
        try {
            if (!$trade->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Сделка не может быть отменена'
                ], 400);
            }

            // TODO: Добавить логику отмены через API биржи
            $trade->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Сделка успешно отменена'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отмене сделки: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Расчет общего PNL
     */
    private function calculateTotalPnl($user)
    {
        return Trade::where('status', Trade::STATUS_OPEN)
            ->withSum(['orders' => function ($query) {
                $query->where('type', '!=', TradeOrder::TYPE_EXIT);
            }], 'unrealized_pnl')
            ->get()
            ->sum('orders_sum_unrealized_pnl');
    }

    /**
     * Расчет PNL за сегодня
     */
    private function calculateTodayPnl($user)
    {
        return Trade::where('user_id', 1)
            ->where('status', Trade::STATUS_OPEN)
            ->whereDate('closed_at', today())
            ->sum('realized_pnl');
    }
}
