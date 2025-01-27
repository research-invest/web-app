<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundingRate extends Model
{
    protected $fillable = [
        'currency_id',
        'funding_rate',
        'max_funding_rate',
        'min_funding_rate',
        'collect_cycle',
        'next_settle_time',
        'timestamp',
        'diff_4h',
        'diff_8h', 
        'diff_12h',
        'diff_24h'
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
} 