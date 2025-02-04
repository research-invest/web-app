<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;
use Orchid\Screen\Concerns\ModelStateRetrievable;

/**
 * @property float $entry_price
 * @property float $exit_price
 * @property float $profit_loss
 * @property float $funding_rate
 * @property Carbon $funding_time
 * @property Carbon $created_at
 * @property array $price_history
 *
 * @property Currency $currency
 */
class FundingSimulation extends Model
{

    use AsSource, Filterable, ModelStateRetrievable, Attachable;

    protected $fillable = [
        'currency_id',
        'funding_time',
        'funding_rate',
        'entry_price',
        'exit_price',
        'profit_loss',
        'price_history'
    ];

    protected $casts = [
        'funding_time' => 'datetime',
        'funding_rate' => 'float',
        'entry_price' => 'float',
        'exit_price' => 'float',
        'profit_loss' => 'float',
        'price_history' => 'array'
    ];

    /**
     * Получить валюту, связанную с симуляцией
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Получить процент прибыли/убытка
     */
    public function getProfitPercentageAttribute()
    {
        if ($this->entry_price == 0) return 0;
        return ($this->profit_loss / $this->entry_price) * 100;
    }

    /**
     * Получить длительность симуляции в секундах
     */
    public function getDurationAttribute()
    {
        if (empty($this->price_history)) return 0;

        $first = $this->price_history[0]['timestamp'];
        $last = end($this->price_history)['timestamp'];

        return $last - $first;
    }

    /**
     * Получить максимальную цену во время симуляции
     */
    public function getMaxPriceAttribute()
    {
        if (empty($this->price_history)) return 0;

        return max(array_column($this->price_history, 'price'));
    }

    /**
     * Получить минимальную цену во время симуляции
     */
    public function getMinPriceAttribute()
    {
        if (empty($this->price_history)) return 0;

        return min(array_column($this->price_history, 'price'));
    }

    /**
     * Получить волатильность во время симуляции (max - min)
     */
    public function getVolatilityAttribute()
    {
        return $this->max_price - $this->min_price;
    }

    /**
     * Проверить была ли симуляция прибыльной
     */
    public function isProfitable()
    {
        return $this->profit_loss > 0;
    }

    /**
     * Scope для получения симуляций за определенный период
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('funding_time', [$startDate, $endDate]);
    }

    /**
     * Scope для получения прибыльных симуляций
     */
    public function scopeProfitable($query)
    {
        return $query->where('profit_loss', '>', 0);
    }

    /**
     * Scope для получения убыточных симуляций
     */
    public function scopeLosing($query)
    {
        return $query->where('profit_loss', '<', 0);
    }
}
