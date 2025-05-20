<?php

namespace App\Http\Resources\Funding;

use App\Models\Funding\FundingDealConfig;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FundingDealConfig
 */
class ConfigResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'exchange' => $this->exchange,
            'notes' => $this->notes,
            'min_funding_rate' => $this->min_funding_rate,
            'position_size' => $this->position_size,
            'leverage' => $this->leverage,
            'currencies' => $this->currencies,
            'ignore_currencies' => $this->ignore_currencies,
            'is_testnet' => $this->is_testnet,
            'deals' => DealResource::collection($this->deals()->new()->get()),
        ];
    }
}
