<?php

namespace App\Models\Funding;

use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property float $funding_rate
 * @property float $max_funding_rate
 * @property float $min_funding_rate
 * @property float $collect_cycle
 * @property float $next_settle_time
 * @property int $timestamp
 *
 * @property string $next_settle_time_utc
 * @property string $next_settle_time_msk
 * @property string $remaining
 */
class FundingRate extends Model
{
    protected $fillable = [
        'currency_id',
        'funding_rate',
        'max_funding_rate',
        'min_funding_rate',
        'collect_cycle',
        'next_settle_time',
        'timestamp',
        'diff_4h',
        'diff_8h',
        'diff_12h',
        'diff_24h'
    ];

    protected $casts = [
//        'timestamp' => 'datetime'
    ];


    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * next_settle_time_utc
     */
    public function getNextSettleTimeUtcAttribute(): string
    {
        $timestamp = $this->next_settle_time / 1000;
        $nextSettleTime = Carbon::createFromTimestamp($timestamp);

        return $nextSettleTime->timezone('UTC')
            ->format('Y-m-d H:i:s');
    }

    /**
     * next_settle_time_msk
     */
    public function getNextSettleTimeMskAttribute(): string
    {
        $timestamp = $this->next_settle_time / 1000;
        $nextSettleTime = Carbon::createFromTimestamp($timestamp);

        return $nextSettleTime->timezone('Europe/Moscow')
            ->format('H:i:s');
    }

    /**
     * remaining
     */
    public function getRemainingAttribute(): string
    {
        $timestamp = $this->next_settle_time / 1000;
        $nextSettleTime = Carbon::createFromTimestamp($timestamp);

        $remainingTime = now()->diff($nextSettleTime);
        $totalHours = $remainingTime->h + ($remainingTime->d * 24);
        return sprintf(
            '%02dч %02dм',
            $totalHours,
            $remainingTime->i
        );
    }

}
