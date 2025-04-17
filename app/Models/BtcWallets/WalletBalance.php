<?php

namespace App\Models\BtcWallets;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property float $balance
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class WalletBalance extends Model
{
    protected $fillable = ['wallet_id', 'balance'];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
