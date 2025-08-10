<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

/**
 * @property int $id
 * @property string $symbol
 * @property string $action
 * @property string $strategy
 * @property float $price
 * @property string $timeframe
 * @property string $exchange
 * @property array $raw_data
 * @property string $source_ip
 * @property string $user_agent
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class TradingViewWebhook extends BaseModel
{
    use AsSource, Filterable, SoftDeletes;

    protected $table = 'trading_view_webhooks';

            protected $fillable = [
        'symbol',
        'action',
        'strategy',
        'price',
        'timeframe',
        'exchange',
        'raw_data',
        'source_ip',
        'user_agent',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'price' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];



    public function scopeBySymbol($query, $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByStrategy($query, $strategy)
    {
        return $query->where('strategy', $strategy);
    }
}
