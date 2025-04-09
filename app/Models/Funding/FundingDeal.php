<?php

namespace App\Models\Funding;

use App\Models\BaseModel;
use App\Models\Currency;
use App\Models\Trade;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Screen\AsSource;

/**
 * @property integer $funding_config_deal_id
 * @property integer $user_id
 * @property integer $currency_id
 * @property Carbon $funding_time
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
 * @property Carbon $run_time
 * @property string $comment
 * @property string $error
 *
 * @property User $user
 * @property Currency $currency
 * @property FundingDealConfig $dealConfig
 * @property string $statusName
 */
class FundingDeal extends BaseModel
{
    use AsSource, SoftDeletes;

    public const int STATUS_NEW = 1;
    public const int STATUS_PROCESS = 2;
    public const int STATUS_DONE = 3;


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
        'run_time',
        'comment',
        'error',
    ];

    protected $casts = [
        'price_history' => 'array',
        'funding_time' => 'datetime',
        'run_time' => 'datetime',
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
        return $this->belongsTo(FundingDealConfig::class, 'funding_deal_config_id');
    }



    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW => 'new',
            self::STATUS_PROCESS => 'process',
            self::STATUS_DONE => 'done',
        ];
    }
    public function getStatusNameAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? '-';
    }
}
