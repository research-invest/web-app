<?php

namespace App\Models;

use App\Helpers\MathHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereDateStartEnd;
use Orchid\Screen\AsSource;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property integer $type
 * @property float $last_price
 * @property float $volume
 * @property integer $is_favorite
 * @property string $exchange
 * @property integer $is_active
 * @property float $start_volume_1h
 * @property float $start_volume_4h
 * @property float $start_volume_24h
 * @property float $start_price_1h
 * @property float $start_price_4h
 * @property float $start_price_24h
 *
 * @property TopPerformingCoinSnapshot[] $topPerformingSnapshots
 * @property float $price_change_24h
 */
class Currency extends BaseModel
{
    use HasFactory, AsSource, Filterable;

    protected $guarded = [];

    public function getNamePriceAttribute(): string
    {
        return sprintf('%s (%s)', $this->name, $this->last_price);
    }


    /**
     * The attributes for which you can use filters in url.
     *
     * @var array
     */
    protected $allowedFilters = [
        'id'         => Where::class,
        'name'       => Like::class,
        'updated_at' => WhereDateStartEnd::class,
        'created_at' => WhereDateStartEnd::class,
    ];

    /**
     * The attributes for which can use sort in url.
     *
     * @var array
     */
    protected $allowedSorts = [
        'id',
        'name',
        'updated_at',
        'created_at',
        'volume',
        'last_price',
    ];


    public function isFavorite()
    {
        if (!auth()->check()) {
            return false;
        }

        return $this->favorites()->where('user_id', auth()->id())->exists();
    }

    public function favorites()
    {
        return $this->hasMany(CurrencyFavorite::class);
    }

    public function topPerformingSnapshots()
    {
        return $this->hasMany(TopPerformingCoinSnapshot::class);
    }


    /**
     * price_change_24h
     * @return float|null
     */
    public function getPriceChange24hAttribute(): ?float
    {
        return round(MathHelper::getPercentOfNumber($this->start_price_24h, $this->last_price), 2);
    }
}
