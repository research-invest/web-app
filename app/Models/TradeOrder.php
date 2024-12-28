<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;


/**
 *
 * @property  float $size
 * @property  float $price
 * @property  string $type
 * @property  float $realized_pnl
 */
class TradeOrder extends Model
{
    use AsSource;

    public const string TYPE_ADD = 'add';
    public const string TYPE_EXIT = 'exit';

    protected $fillable = [
        'trade_id',
        'price',
        'size',
        'type',
        'executed_at'
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
}
