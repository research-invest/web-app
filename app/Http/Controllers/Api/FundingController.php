<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Funding\ConfigResource;
use App\Models\Funding\FundingDeal;
use App\Models\Funding\FundingDealConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FundingController extends Controller
{
    /**
     * Получить все конфигурации фандинга пользователя
     */
    public function getConfigs(Request $request): JsonResponse
    {
        $user = $request->get('user');

        $configs = FundingDealConfig::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('deals', 'deals.currency')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ConfigResource::collection($configs),
        ]);
    }

    /**
     * Получить все сделки фандинга пользователя
     */
    public function getDeals(Request $request): JsonResponse
    {
        $user = $request->get('user');

        $deals = FundingDeal::where('user_id', $user->id)
            ->with(['currency', 'dealConfig'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $deals
        ]);
    }

    /**
     * Обновить результаты сделки
     */
    public function updateDealResult(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'deal_id' => 'required|integer|exists:funding_deals,id',
            'exit_price' => 'required|numeric',
            'profit_loss' => 'required|numeric',
            'funding_fee' => 'required|numeric',
            'total_pnl' => 'required|numeric',
            'roi_percent' => 'required|numeric',
            'price_history' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->get('user');
        $deal = FundingDeal::where('id', $request->deal_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$deal) {
            return response()->json([
                'success' => false,
                'message' => 'Сделка не найдена'
            ], 404);
        }

        $deal->update([
            'exit_price' => $request->exit_price,
            'profit_loss' => $request->profit_loss,
            'funding_fee' => $request->funding_fee,
            'total_pnl' => $request->total_pnl,
            'roi_percent' => $request->roi_percent,
            'price_history' => $request->price_history,
            'status' => FundingDeal::STATUS_DONE,
            'run_time' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $deal
        ]);
    }
}
