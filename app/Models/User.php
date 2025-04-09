<?php

namespace App\Models;

use App\Models\Funding\FundingDeal;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereDateStartEnd;
use Orchid\Platform\Models\User as Authenticatable;

/**
 * @property  string $name
 * @property string $email
 * @property string $telegram_chat_id
 * @property string $mexc_api_key
 * @property string $mexc_secret_key
 * @property string $gate_api_key
 * @property string $gate_secret_key
 * @property string $gate_testnet_api_key
 * @property string $gate_testnet_secret_key
 * @property string $bybit_testnet_api_key
 * @property string $bybit_testnet_secret_key
 * @property string $binance_testnet_api_key
 * @property string $binance_testnet_secret_key
 */
class User extends Authenticatable
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'api_token',
        'telegram_chat_id',

        'bybit_secret_key',
        'bybit_api_key',

        'mexc_secret_key',
        'mexc_api_key',

        'binance_secret_key',
        'binance_api_key',

        'bigx_secret_key',
        'bigx_api_key',

        'gate_secret_key',
        'gate_api_key',

        'kucoin_secret_key',
        'kucoin_api_key',
        'kucoin_api_passphrase',
        'gate_testnet_secret_key',
        'gate_testnet_api_key',
        'bybit_testnet_secret_key',
        'bybit_testnet_api_key',
        'binance_testnet_secret_key',
        'binance_testnet_api_key',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'permissions',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'permissions'          => 'array',
        'email_verified_at'    => 'datetime',
    ];

    /**
     * The attributes for which you can use filters in url.
     *
     * @var array
     */
    protected $allowedFilters = [
           'id'         => Where::class,
           'name'       => Like::class,
           'email'      => Like::class,
           'updated_at' => WhereDateStartEnd::class,
           'created_at' => WhereDateStartEnd::class,
    ];

    /**
     * The attributes for which can use sort in url.
     *
     * @var array
     */
    protected $allowedSorts = [
        'id',
        'name',
        'email',
        'updated_at',
        'created_at',
    ];

    public function favorites()
    {
        return $this->hasMany(CurrencyFavorite::class);
    }

    public function fundingDeals()
    {
        return $this->hasMany(FundingDeal::class)->latest();
    }
}
