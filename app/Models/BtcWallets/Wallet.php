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
 * @property float $last_price
 * @property float $last_volume
 * @property integer $visible_type
 * @property integer $label_type
 * @property array $diff_percent_history
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
        'last_price',
        'last_volume',
        'address',
        'label',
        'balance',
        'diff_percent',
        'diff_percent_history',
        'visible_type',
        'label_type',
    ];

    protected $allowedSorts = [
        'id',
        'balance',
        'updated_at',
        'diff_percent',
        'visible_type',
        'label_type',
    ];

    protected $casts = [
        'diff_percent_history' => 'array',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(WalletBalance::class);
    }

    public function getExplorerLink(): string
    {
        return 'https://www.blockchain.com/explorer/addresses/btc/' . $this->address;
    }
}
