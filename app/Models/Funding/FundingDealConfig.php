<?php

namespace App\Models\Funding;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

/**
 * @property string $name
 * @property string $notes
 * @property string $exchange
 * @property integer $user_id
 * @property float $min_funding_rate
 * @property float $position_size
 * @property int $leverage
 * @property int $is_active
 * @property array $currencies
 * @property array $ignore_currencies
 * @property int $is_testnet
 *
 * @property User $user
 * @property FundingDeal[] $deals
 */
class FundingDealConfig extends BaseModel
{
    use AsSource, Filterable, SoftDeletes;

    protected $table = 'funding_deals_config';

    protected $fillable = [
        'name',
        'exchange',
        'notes',
        'user_id',
        'min_funding_rate',
        'position_size',
        'leverage',
        'currencies',
        'ignore_currencies',
        'is_active',
        'is_testnet',
    ];

    protected $casts = [
        'currencies' => 'array',
        'ignore_currencies' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deals()
    {
        return $this->hasMany(FundingDeal::class)->latest();
    }
}
