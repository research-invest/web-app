<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopPerformingCoinSnapshot extends BaseModel
{
    public $timestamps = false;

    protected $table = 'top_performing_coin_snapshots';

    protected $fillable = [
        'currency_id',
        'symbol',
        'price',
        'price_change_percent',
        'volume_diff_percent',
        'created_at'
    ];

    protected $casts = [
        'price_change_percent' => 'float',
        'volume_diff_percent' => 'float',
        'created_at' => 'datetime'
    ];


    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
