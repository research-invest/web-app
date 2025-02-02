<?php

namespace App\Models;

use App\Helpers\MathHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;
use Orchid\Screen\Concerns\ModelStateRetrievable;

/**
 *
 * @property  float $position_size
 * @property  float $take_profit_price
 * @property  float $target_profit_amount
 * @property  float $realized_pnl
 * @property  float $unrealized_pnl
 * @property  float $open_currency_volume
 * @property  float $close_currency_volume
 * @property  float $entry_price
 * @property  float $stop_loss_price
 * @property  float $exit_price
 * @property  float $profit_percentage
 * @property  integer $leverage
 * @property  integer $currency_id
 * @property  string $status
 * @property  string $position_type
 * @property  integer $user_id
 * @property  integer $strategy_id
 * @property  float $commission_open
 * @property  float $commission_close
 * @property  float $commission_finance
 * @property  boolean $is_fake
 *
 * @property  Carbon $closed_at
 * @property  Carbon $created_at
 * @property  Currency $currency
 * @property  TradeOrder[] $orders
 * @property  User $user
 * @property  TradePnlHistory[] $pnlHistory
 * @property  CheckListItem[] $checkListItems
 * @property  TradePeriod $tradePeriod
 * @property  float $currentPnL
 * @property  string $target_profit_price
 * @property  float $target_profit_percent
 * @property string $currency_name_format
 */
class Trade extends BaseModel
{
    use AsSource, Filterable, SoftDeletes, ModelStateRetrievable, Attachable;

    public const string POSITION_TYPE_LONG = 'long';
    public const string POSITION_TYPE_SHORT = 'short';
    public const string STATUS_OPEN = 'open';
    public const string STATUS_CLOSED = 'closed';
    public const string STATUS_LIQUIDATED = 'liquidated';
    public const string FAKE_TRADE_TEXT = ' (Fake Trade)';

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
        'unrealized_pnl',
        'realized_pnl',
        'closed_at',
        'notes',
        'trade_period_id',
        'open_currency_volume',
        'close_currency_volume',
        'user_id',
        'commission_open',
        'commission_close',
        'commission_finance',
        'profit_percentage',
        'strategy_id',
        'is_fake',
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

    protected $allowedSorts = [
        'position_type',
        'profit_percentage',
        'realized_pnl',
    ];

    private float $avgEntryPrice = 0;

    public static function getStatuses(): array
    {
        return [
            self::STATUS_OPEN => 'Открыта',
            self::STATUS_CLOSED => 'Закрыта',
            self::STATUS_LIQUIDATED => 'Ликвидирована'
        ];
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }

    public function orders()
    {
        return $this->hasMany(TradeOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pnlHistory()
    {
        return $this->hasMany(TradePnlHistory::class);
    }

    public function checkListItems()
    {
        return $this->hasMany(TradeCheckListItem::class, 'trade_id');
    }


    public function scopeFake($query)
    {
        return $query->where('is_fake', true);
    }

    public function scopeNotFake($query)
    {
        return $query->where('is_fake', false);
    }

    /**
     * currentPnL
     * @return float|null
     */
    public function getCurrentPnLAttribute(): ?float
    {
        return $this->isStatusOpen()
            ? $this->getUnrealizedPnL($this->currency->last_price)
            : $this->realized_pnl;
    }

    /**
     * Получить среднюю цену входа с учетом всех ордеров
     *
     * @return float
     */
    public function getAverageEntryPrice(): float
    {
        if ($this->avgEntryPrice) {
            return $this->avgEntryPrice;
        }

        $entryOrders = $this->orders()
            ->whereIn('type', [TradeOrder::TYPE_ENTRY, TradeOrder::TYPE_ADD])
            ->get();

        $totalQuantity = 0;
        $totalValue = 0;

        foreach ($entryOrders as $order) {
            $quantity = $order->size / $order->price;
            $totalQuantity += $quantity;
            $totalValue += $order->size;
        }

        return $this->avgEntryPrice = $totalQuantity > 0 ? $totalValue / $totalQuantity : 0;
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

        if ($this->isTypeLong()) {
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
        if ($this->isTypeLong()) {
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
        if ($this->isTypeLong()) {
            return $currentPrice <= $this->stop_loss_price;
        }
        return $currentPrice >= $this->stop_loss_price;
    }

    /**
     * Обновить PNL для всех ордеров и сохранить историю
     */
    public function updatePnL(float $currentPrice)
    {
//        $averagePrice = $this->getAverageEntryPrice();

        $unrealizedTradePnl = 0;

        // Обновляем PNL для каждого ордера
        foreach ($this->orders as $order) {
            if ($order->isTypeExit()) {
                continue; // Пропускаем ордера выхода
            }

            // Расчет количества монет в ордере
            $quantity = $order->size / $order->price;

            // Расчет нереализованного PNL для ордера
            if ($this->isTypeLong()) {
                $unrealizedPnl = ($currentPrice - $order->price) * $quantity;
            } else {
                $unrealizedPnl = ($order->price - $currentPrice) * $quantity;
            }

            $unrealizedTradePnl += $unrealizedPnl;

            // Обновляем ордер
            $order->update([
                'unrealized_pnl' => $unrealizedPnl,
                'pnl_updated_at' => now()
            ]);
        }

        $this->update([
            'unrealized_pnl' => $unrealizedTradePnl,
        ]);

        // Расчет общего PNL для позиции
        $totalUnrealizedPnl = $this->getUnrealizedPnL($currentPrice);
        $roe = $this->getCurrentRoe($currentPrice);

        // Сохраняем запись в историю
        $this->pnlHistory()->create([
            'price' => $currentPrice,
            'unrealized_pnl' => $totalUnrealizedPnl,
            'realized_pnl' => $this->realized_pnl ?? 0,
            'roe' => $roe
        ]);
    }

    /**
     * Получить цену ликвидации
     *
     * Формула для расчета:
     * Long: Entry Price * (1 - 1/leverage)
     * Short: Entry Price * (1 + 1/leverage)
     *
     * @return float
     */
    public function getLiquidationPrice(): float
    {
        if (!$this->leverage) {
            return 0;
        }

        $averagePrice = $this->getAverageEntryPrice();
        $maintenanceMargin = 1 / $this->leverage; // Упрощенная формула, может отличаться на разных биржах

        if ($this->isTypeLong()) {
            $price = $averagePrice * (1 - $maintenanceMargin);
        } else {
            $price = $averagePrice * (1 + $maintenanceMargin);
        }

        return MathHelper::addPercent($price, 2); // прибавляем чучуть для запаса и защиты от проскальзывания

    }

    /**
     * Получить расстояние до ликвидации в процентах
     *
     * @param float|null $currentPrice
     * @return float|null
     */
    public function getDistanceToLiquidation(?float $currentPrice = null): ?float
    {
        if (!$currentPrice) {
            return null;
        }

        $liquidationPrice = $this->getLiquidationPrice();
        $averagePrice = $this->getAverageEntryPrice();

        if ($this->isTypeLong()) {
            return (($currentPrice - $liquidationPrice) / $averagePrice) * 100;
        }

        return (($liquidationPrice - $currentPrice) / $averagePrice) * 100;
    }

    public function isTypeLong(): bool
    {
        return $this->position_type === self::POSITION_TYPE_LONG;
    }

    public function isTypeShort(): bool
    {
        return $this->position_type === self::POSITION_TYPE_SHORT;
    }

    public function isEvenDay(): bool
    {
        return (int)$this->created_at->format('z') % 2 === 0;
    }

    public function isOddDay(): bool
    {
        return !$this->isEvenDay();
    }

    public function tradePeriod()
    {
        return $this->belongsTo(TradePeriod::class);
    }


    public function isStatusOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function getProfitPercentage(): float
    {
        $initialDeposit = $this->orders()
            ->whereIn('type', [
                TradeOrder::TYPE_ENTRY,
                TradeOrder::TYPE_ADD
            ])
            ->sum('size');

        if ($initialDeposit > 0 && $this->realized_pnl !== null) {
            return ($this->realized_pnl / $initialDeposit) * 100;

            return MathHelper::getPercentOfNumber(
                (float)$this->realized_pnl,
                (float)$initialDeposit
            );
        }

        return 0.0;
    }

    /**
     * Длительность сделки в днях
     * @return string
     */
    public function getDurationTime(): string
    {
        if ($this->closed_at) {
            $days = $this->created_at->diffInDays($this->closed_at);
            $hours = $this->created_at->copy()->addDays($days)->diffInHours($this->closed_at);

            if ($days > 0) {
                return $days . 'д ' . $hours . 'ч';
            }

            return $hours . 'ч';
        }

        if ($this->created_at) {
            $days = $this->created_at->diffInDays(now());
            $hours = $this->created_at->copy()->addDays($days)->diffInHours(now());

            if ($days > 0) {
                return $days . 'д ' . $hours . 'ч';
            }

            return $hours . 'ч';
        }

        return '';

        if ($this->closed_at) {
            return $this->closed_at->diffForHumans($this->created_at, true);
        }

        return $this->created_at ? $this->created_at->diffForHumans(now(), true) : '';


    }

    /**
     * currency_name_format
     * @return string
     */
    public function getCurrencyNameFormatAttribute(): string
    {
        return $this->is_fake ? ($this->currency->name . self::FAKE_TRADE_TEXT) : $this->currency->name;
    }

    /**
     * target_profit_price
     * @return string
     */
    public function getTargetProfitPriceAttribute(): string
    {
        $averagePrice = $this->getAverageEntryPrice();
        $totalContracts = $this->position_size * $this->leverage;

        if ($this->isTypeLong()) {
            $price = $averagePrice + ($this->target_profit_amount * $averagePrice / $totalContracts);
        } else {
            $price = $averagePrice - ($this->target_profit_amount * $averagePrice / $totalContracts);
        }

        return MathHelper::formatNumber($price);
    }

    /**
     * target_profit_percent
     * @return float
     */
    public function getTargetProfitPercentAttribute(): float
    {
        $averagePrice = $this->getAverageEntryPrice();

        if ($this->isTypeLong()) {
            $percent = (($this->target_profit_price - $averagePrice) / $averagePrice) * 100;
        } else {
            $percent = (($averagePrice - $this->target_profit_price) / $averagePrice) * 100;
        }

        return round($percent, 2);
    }

}
