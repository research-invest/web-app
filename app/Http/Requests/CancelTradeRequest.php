<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Trade;
use Illuminate\Support\Facades\Cache;

class CancelTradeRequest extends FormRequest
{
    public function rules()
    {
        return [
            'trade' => 'required|exists:trades,id'
        ];
    }

    private function calculateTotalPnl($user)
    {
        return Cache::remember("user_{$user->id}_total_pnl", 300, function () use ($user) {
            return Trade::where('user_id', $user->id)
                ->where('status', 'closed')
                ->sum('pnl');
        });
    }
} 