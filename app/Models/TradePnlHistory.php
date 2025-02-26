<?php

namespace App\Models;

use Orchid\Screen\AsSource;

/**
 * @property float $price
 * @property float $roe
 * @property float $unrealized_pnl
 * @property float $volume
 * @property float $funding_rate
 */
class TradePnlHistory extends BaseModel
{
    use AsSource;

    protected $table = 'trade_pnl_history';

    protected $fillable = [
        'trade_id',
        'price',
        'unrealized_pnl',
        'realized_pnl',
        'roe',
        'volume',
        'funding_rate',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'unrealized_pnl' => 'decimal:8',
        'realized_pnl' => 'decimal:8',
        'roe' => 'decimal:4'
    ];

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }
}
