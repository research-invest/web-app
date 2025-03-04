<?php

namespace App\Models\Funding;

use App\Models\BaseModel;
use App\Models\Currency;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Screen\AsSource;

/**
 * @property integer $funding_config_deal_id
 * @property integer $user_id
 * @property integer $currency_id
 * @property integer $funding_time
 * @property float $funding_rate
 * @property float $entry_price
 * @property float $exit_price
 * @property float $profit_loss
 * @property float $position_size
 * @property float $contract_quantity
 * @property float $initial_margin
 * @property float $funding_fee
 * @property float $pnl_before_funding
 * @property float $total_pnl
 * @property float $roi_percent
 * @property int $leverage
 * @property int $status
 * @property array $price_history
 *
 * @property User $user
 * @property Currency $currency
 * @property FundingDealConfig $dealConfig
 */
class FundingDeal extends BaseModel
{
    use AsSource, SoftDeletes;

    public const int STATUS_NEW = 1;



    protected $fillable = [
        'funding_config_deal_id',
        'user_id',
        'currency_id',
        'funding_time',
        'funding_rate',
        'entry_price',
        'exit_price',
        'profit_loss',
        'position_size',
        'contract_quantity',
        'leverage',
        'initial_margin',
        'funding_fee',
        'pnl_before_funding',
        'total_pnl',
        'roi_percent',
        'price_history',
        'status',
    ];

    protected $casts = [
        'price_history' => 'array',
        'funding_time' => 'datetime',
    ];

    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function dealConfig()
    {
        return $this->belongsTo(FundingDealConfig::class);
    }
}
