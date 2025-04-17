<?php

namespace App\Models\BtcWallets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

/**
 * @property string $address
 * @property string $label
 * @property float $balance
 *
 * @property WalletBalance[] $balances
 */

class Wallet extends Model
{
    use SoftDeletes, AsSource, Filterable;

    protected $fillable = ['address', 'label', 'balance'];

    public function balances(): HasMany
    {
        return $this->hasMany(WalletBalance::class);
    }
}
