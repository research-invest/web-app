<?php

namespace App\Models\BtcWallets;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property float $whale_score
 * @property float $correlation
 * @property float $smart_index
 * @property float $stability
 * @property integer $momentum
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class WalletMetric extends Model
{
    protected $fillable = [
        'wallet_id',
        'whale_score',
        'momentum',
        'correlation',
        'smart_index',
        'stability',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
