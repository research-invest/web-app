<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletBalance extends Model
{
    protected $fillable = ['wallet_id', 'balance'];
    
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
} 