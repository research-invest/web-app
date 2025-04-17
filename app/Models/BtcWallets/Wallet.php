<?php

namespace App\Models\BtcWallets;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

/**
 * @property string $address
 * @property string $label
 * @property float $balance
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property WalletBalance[] $balances
 */

class Wallet extends Model
{
    use SoftDeletes, AsSource, Filterable;

    protected $fillable = ['address', 'label', 'balance'];

    protected $allowedSorts = [
        'id',
        'balance',
        'updated_at',
    ];


    public function balances(): HasMany
    {
        return $this->hasMany(WalletBalance::class);
    }
}
