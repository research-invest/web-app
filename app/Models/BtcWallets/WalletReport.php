<?php

namespace App\Models\BtcWallets;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property Carbon $report_date
 * @property float $total_balance
 * @property int $grown_wallets_count
 * @property int $dropped_wallets_count
 * @property int $unchanged_wallets_count
 * @property array $top_gainers
 * @property array $top_losers
 * @property float $market_price
 * @property float $market_volume
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class WalletReport extends Model
{
    protected $fillable = [
        'report_date',
        'total_balance',
        'grown_wallets_count',
        'dropped_wallets_count',
        'unchanged_wallets_count',
        'top_gainers',
        'top_losers',
        'market_price',
        'market_volume',
    ];

    protected $casts = [
        'report_date' => 'datetime',
        'top_gainers' => 'array',
        'top_losers' => 'array',
    ];
}
