<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

/**
 *
 * @property  Currency $currency
 */
class Trade extends Model
{
    use AsSource, Filterable;

    protected $fillable = [
        'currency_id',
        'position_type',
        'entry_price',
        'position_size',
        'leverage',
        'stop_loss_price',
        'take_profit_price',
        'target_profit_amount',
        'status',
        'exit_price',
        'realized_pnl',
        'closed_at',
        'notes'
    ];

    protected $casts = [
        'entry_price' => 'decimal:8',
        'position_size' => 'decimal:2',
        'leverage' => 'integer',
        'stop_loss_price' => 'decimal:8',
        'take_profit_price' => 'decimal:8',
        'target_profit_amount' => 'decimal:2',
        'exit_price' => 'decimal:8',
        'realized_pnl' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function orders()
    {
        return $this->hasMany(TradeOrder::class);
    }

    public function getCurrentPnLAttribute()
    {
        // Здесь можно добавить логику расчета текущего P&L
        if ($this->status === 'closed') {
            return $this->realized_pnl;
        }

        // Получаем текущую цену из API
        return null;
    }
}
