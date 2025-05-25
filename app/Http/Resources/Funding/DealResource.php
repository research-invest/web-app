<?php

namespace App\Http\Resources\Funding;

use App\Models\Funding\FundingDeal;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FundingDeal
 */
class DealResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'currency_id' => $this->currency_id,
            'currency_code' => $this->currency->code,
            'funding_time' => $this->funding_time,
            'run_time' => $this->run_time,
            'funding_rate' => $this->funding_rate,
            'entry_price' => $this->entry_price,
            'status' => $this->status,
            'leverage' => $this->leverage,
            'position_size' => $this->position_size,
            'position_size_leverage' => $this->position_size * $this->leverage,
            'comment' => $this->comment,
        ];
    }
}
