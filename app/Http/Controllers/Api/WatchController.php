<?php

namespace App\Http\Controllers\Api;

use App\Helpers\MathHelper;
use App\Http\Controllers\Controller;
use App\Models\Currency;
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
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $trades = $this->getTrades($user);
        $favorites = $this->getFavorites($user);
        $data = [
            'summary' => [
                'total_pnl' => $this->calculateTotalPnl($user, $trades),
                'today_pnl' => $this->calculateTodayPnl($user),
                'period_pnl' => 0,
                'active_trades' => Trade::where('user_id', 1)
                    ->where('status', Trade::STATUS_OPEN)
                    ->count(),
            ],

            // Активные сделки
            'trades' => $trades,
            'favorites' => $favorites,

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
    private function calculateTotalPnl($user, $trades = [])
    {
        return round(collect($trades)->sum('pnl'), 3);

        return round(Trade::where('status', Trade::STATUS_OPEN)
            ->withSum(['orders' => function ($query) {
                $query->where('type', '!=', TradeOrder::TYPE_EXIT);
            }], 'unrealized_pnl')
            ->get()
            ->sum('orders_sum_unrealized_pnl'), 3);
    }

    /**
     * Расчет PNL за сегодня
     */
    private function calculateTodayPnl($user)
    {
        return round(Trade::where('user_id', 1)
            ->where('status', Trade::STATUS_CLOSED)
            ->whereDate('closed_at', today())
            ->sum('realized_pnl'), 2);
    }

    private function getTrades($user)
    {
        return Trade::where('user_id', 1)
            ->where('status', Trade::STATUS_OPEN)
            ->latest()
            ->take(10)
            ->get()
            ->map(function (Trade $trade) {
                return [
                    'id' => $trade->id,
                    'symbol' => $trade->currency->code,
                    'type' => $trade->position_type, // buy/sell
                    'entry_price' => (float)$trade->entry_price,
                    'current_price' => (float)$trade->currency->last_price,
                    'pnl' => round($trade->currentPnL, 3),
                    'can_cancel' => true,
                    'average_price' => (float)$trade->getAverageEntryPrice(),
                    'orders' => $trade->orders()->get()
                        ->map(function (TradeOrder $order) {
                            return [
                                'id'=> $order->id,
                                'price'=> (float)$order->price,
                                'size'=> (float)$order->size,
                            ];
                        }),
                ];
            });
    }

    private function getFavorites($user)
    {
        return Currency::query()
            ->isActive()
            ->join('currencies_favorites', function ($join) {
                $join->on('currencies.id', '=', 'currencies_favorites.currency_id')
                    ->where('currencies_favorites.user_id', '=', 1);
            })
            ->orderByDesc('last_price')
            ->get()
//            ->select('currencies.*')
            ->map(function (Currency $currency) {
                return [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'price' => $currency->last_price,
                    'price_24h' => $currency->start_price_24h,
                    'price_4h' => $currency->start_price_4h,
                    'price_1h' => $currency->start_price_1h,
                    'price_24h_percent' => round(MathHelper::getPercentOfNumber($currency->last_price, $currency->start_price_24h), 2),
                    'price_4h_percent' => round(MathHelper::getPercentOfNumber($currency->last_price, $currency->start_price_4h), 2),
                    'price_1h_percent' => round(MathHelper::getPercentOfNumber($currency->last_price, $currency->start_price_1h), 2),
                ];
            });
    }
}
