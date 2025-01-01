<?php

namespace App\Models;

use Orchid\Screen\AsSource;


/**
 *
 * @property  int $id
 * @property  float $size
 * @property  float $price
 * @property  string $type
 * @property  float $realized_pnl
 */
class TradeOrder extends BaseModel
{
    use AsSource;

    public const string TYPE_ENTRY = 'entry';
    public const string TYPE_ADD = 'add';
    public const string TYPE_EXIT = 'exit';

    protected $fillable = [
        'trade_id',
        'price',
        'size',
        'type',
        'executed_at',
        'unrealized_pnl',
        'pnl_updated_at'
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'size' => 'decimal:2',
        'executed_at' => 'datetime'
    ];

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    public function isTypeEntry(): bool
    {
        return $this->type === self::TYPE_ENTRY;
    }

    public function isTypeAdd(): bool
    {
        return $this->type === self::TYPE_ADD;
    }

    public function isTypeExit(): bool
    {
        return $this->type === self::TYPE_EXIT;
    }
}
