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

    /**
     * Получить среднюю цену входа с учетом всех ордеров
     *
     * @return float
     */
    public function getAverageEntryPrice(): float
    {
        $entryOrders = $this->orders()
            ->whereIn('type', ['entry', 'add'])
            ->get();

        if ($entryOrders->isEmpty()) {
            return (float)$this->entry_price;
        }

        $totalSize = $entryOrders->sum('size');
        
        if ($totalSize <= 0) {
            return (float)$this->entry_price;
        }

        $weightedSum = $entryOrders->reduce(function ($carry, $order) {
            return $carry + ($order->price * $order->size);
        }, 0);

        return (float)($weightedSum / $totalSize);
    }

    /**
     * Получить текущий размер позиции
     * (с учетом частичных выходов)
     *
     * @return float
     */
    public function getCurrentPositionSize(): float
    {
        $entrySize = $this->orders()
            ->whereIn('type', ['entry', 'add'])
            ->sum('size');

        $exitSize = $this->orders()
            ->where('type', 'exit')
            ->sum('size');

        return (float)($entrySize - $exitSize);
    }

    /**
     * Получить нереализованный P&L для текущей цены
     *
     * @param float|null $currentPrice
     * @return float|null
     */
    public function getUnrealizedPnL(?float $currentPrice = null): ?float
    {
        if (!$currentPrice) {
            return null;
        }

        $averagePrice = $this->getAverageEntryPrice();
        $currentSize = $this->getCurrentPositionSize();

        if ($this->position_type === 'long') {
            return ($currentPrice - $averagePrice) * $currentSize * $this->leverage / $averagePrice;
        }

        return ($averagePrice - $currentPrice) * $currentSize * $this->leverage / $averagePrice;
    }

    /**
     * Получить ROE для текущей цены
     *
     * @param float|null $currentPrice
     * @return float|null
     */
    public function getCurrentRoe(?float $currentPrice = null): ?float
    {
        $pnl = $this->getUnrealizedPnL($currentPrice);
        
        if ($pnl === null || $this->position_size <= 0) {
            return null;
        }

        return ($pnl / $this->position_size) * 100;
    }

    /**
     * Проверить, достигнут ли уровень Take Profit
     *
     * @param float $currentPrice
     * @return bool
     */
    public function isTakeProfitReached(float $currentPrice): bool
    {
        if ($this->position_type === 'long') {
            return $currentPrice >= $this->take_profit_price;
        }
        return $currentPrice <= $this->take_profit_price;
    }

    /**
     * Проверить, достигнут ли уровень Stop Loss
     *
     * @param float $currentPrice
     * @return bool
     */
    public function isStopLossReached(float $currentPrice): bool
    {
        if ($this->position_type === 'long') {
            return $currentPrice <= $this->stop_loss_price;
        }
        return $currentPrice >= $this->stop_loss_price;
    }
}
