<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

/**
 * @property string $name
 * @property integer $daily_target
 * @property integer $weekend_target
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property bool $is_active
 */
class TradePeriod extends Model
{
    use AsSource, Filterable, SoftDeletes;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
        'daily_target',
        'weekend_target',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean'
    ];

    public function scopeIsActive($query)
    {
        return $query->where('is_active', true);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }
}
