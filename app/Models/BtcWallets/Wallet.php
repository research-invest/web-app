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
 * @property float $diff_percent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property WalletBalance[] $balances
 */

class Wallet extends Model
{
    use SoftDeletes, AsSource, Filterable;

    protected $fillable = [
        'address',
        'label',
        'balance',
        'diff_percent',
    ];

    protected $allowedSorts = [
        'id',
        'balance',
        'updated_at',
        'diff_percent',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(WalletBalance::class);
    }
}
