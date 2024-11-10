<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class TradeOrder extends Model
{
    use AsSource;

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