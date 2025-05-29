<?php

namespace App\Models;

use App\Helpers\MathHelper;
use App\Models\Funding\FundingRate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereDateStartEnd;
use Orchid\Screen\AsSource;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $tradingview_code
 * @property integer $type
 * @property float $last_price
 * @property float $volume
 * @property float $funding_rate
 * @property integer $is_favorite
 * @property string $exchange
 * @property integer $is_active
 * @property Carbon|int $next_settle_time
 * @property float $start_volume_1h
 * @property float $start_volume_4h
 * @property float $start_volume_24h
 * @property float $start_price_1h
 * @property float $start_price_4h
 * @property float $start_price_24h
 * @property float $start_funding_8h
 * @property float $start_funding_24h
 * @property float $start_funding_48h
 * @property float $start_funding_7d
 * @property float $start_funding_30d
 * @property string $source_price
 * @property string $coingecko_code
 * @property string $binance_code
 *
 * @property TopPerformingCoinSnapshot[] $topPerformingSnapshots
 * @property float $price_change_24h
 * @property string $type_name
 * @property FundingRate $latestFundingRate
 * @property FundingRate[] $fundingRates
 */
class Currency extends BaseModel
{
    use HasFactory, AsSource, Filterable;

    public const int TYPE_SPOT = 1;
    public const int TYPE_FEATURE = 2;
    public const string EXCHANGE_BINANCE = 'binance';
    public const string EXCHANGE_MEXC = 'mexc';
    public const string EXCHANGE_GATE = 'gate.io';
    public const string EXCHANGE_KUKOIN = 'kukoin';
    public const string EXCHANGE_BYBIT = 'bybit';
    const CODE_BTC = 'BTCUSDT';
    const CODE_ETH = 'ETHUSDT';
    const string SOURCE_PRICE_SELLL = 'api_selll';
    const string SOURCE_PRICE_COINGECKO = 'coingecko';

    protected $guarded = [];

    protected $casts = [
        'next_settle_time' => 'datetime'
    ];


    /**
     * The attributes for which you can use filters in url.
     *
     * @var array
     */
    protected $allowedFilters = [
        'id' => Where::class,
        'name' => Like::class,
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
        'next_settle_time',
        'tradingview_code',
        'start_funding_8h',
        'start_funding_24h',
        'start_funding_48h',
        'start_funding_7d',
        'start_funding_30d',
        'funding_rate',
        'source_price',
        'coingecko_code',
        'binance_code',
    ];

    public function scopeFeatures($query)
    {
        return $query->where('type', self::TYPE_FEATURE);
    }

    public function scopeSpot($query)
    {
        return $query->where('type', self::TYPE_SPOT);
    }


    public function scopeExchangeGate($query)
    {
        return $query->where('exchange', self::EXCHANGE_GATE);
    }

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

    /**
     * Ссылка на TradingView
     * @return string
     */
    public function getTVLink(): string
    {
        $code = $this->tradingview_code ?: $this->code;

        if ($this->isExchangeMexc() && $this->isTypeFeature()) {
            $code = str_replace('_', '', $code);
        }
        return sprintf('https://ru.tradingview.com/chart/?symbol=%s&interval=%s',
            $code, 240);
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_SPOT => 'Spot',
            self::TYPE_FEATURE => 'Feature',
        ];
    }

    public static function getPriceSources(): array
    {
        return [
            self::SOURCE_PRICE_SELLL => 'SELLL API',
            self::SOURCE_PRICE_COINGECKO => 'coingecko.com',
        ];
    }


    public static function getExchanges(): array
    {
        return [
            self::EXCHANGE_MEXC => 'mexc',
            self::EXCHANGE_GATE => 'gate.io',
            self::EXCHANGE_BINANCE => 'binance',
            self::EXCHANGE_BYBIT => 'bybit',
            self::EXCHANGE_KUKOIN => 'kukoin',
        ];
    }

    public function getNamePriceAttribute(): string
    {
        return sprintf('%s (%s)', $this->name, $this->last_price);
    }

    public function getTypeNameAttribute(): string
    {
        return self::getTypes()[$this->type] ?? '-';
    }


    public function fundingRates()
    {
        return $this->hasMany(FundingRate::class);
    }

    public function latestFundingRate()
    {
        return $this->hasOne(FundingRate::class)->latestOfMany();
    }


    public function isSpot(): bool
    {
        return $this->type === self::TYPE_SPOT;
    }

    public function isExchangeBinance(): bool
    {
        return $this->exchange === self::EXCHANGE_BINANCE;
    }

    public function isExchangeMexc(): bool
    {
        return $this->exchange === self::EXCHANGE_MEXC;
    }

    public function isExchangeGate(): bool
    {
        return $this->exchange === self::EXCHANGE_GATE;
    }

    public function isTypeFeature(): bool
    {
        return $this->type === self::TYPE_FEATURE;
    }

    public function isTypeSpot(): bool
    {
        return $this->type === self::TYPE_SPOT;
    }

    public function getExchangeLink(): string
    {
        if ($this->isExchangeMexc() && $this->isTypeFeature()) {
            return sprintf('https://futures.mexc.com/ru-RU/exchange/%s?type=linear_swap', $this->code);
        }
        if ($this->isExchangeBinance() && $this->isTypeFeature()) {
            return sprintf('https://www.binance.com/ru/trade/%s?type=cross', $this->code);
        }

        if ($this->isExchangeGate() && $this->isTypeFeature()) {
            return sprintf('https://www.gate.io/ru/futures/USDT/%s', $this->code);
        }

        return sprintf('https://www.binance.com/ru/trade/%s', $this->code);
    }

    public function getAdminPageLink(): string
    {
        return route('platform.currencies.edit', $this);
    }

    public static function getBtc():self
    {
        return self::query()->where('code', self::CODE_BTC)->first();
    }
}
