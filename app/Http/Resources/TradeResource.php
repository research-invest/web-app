<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TradeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'type' => $this->type,
            'entry_price' => $this->entry_price,
            'current_price' => $this->current_price,
            'pnl' => $this->calculatePnl(),
            'can_cancel' => $this->canBeCancelled(),
            'created_at' => $this->created_at,
        ];
    }
} 