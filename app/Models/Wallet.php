<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use SoftDeletes;

    protected $fillable = ['address', 'label', 'balance'];

    public function balances(): HasMany
    {
        return $this->hasMany(WalletBalance::class);
    }
} 