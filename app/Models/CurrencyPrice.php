<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyPrice extends Model
{

    protected $table = 'currencies_prices';

    public const string SOURCE_COINGECKO = 'coingecko';

    protected $fillable = [
        'currency_id',
        'coin_id',
        'symbol',
        'name',
        'source',
        'current_price',
        'market_cap',
        'market_cap_rank',
        'total_volume',
        'price_change_24h',
        'price_change_percentage_24h',
        'circulating_supply',
        'total_supply',
        'max_supply',
        'ath',
        'atl',
        'price_btc',
        'price_eth',
        'volume_btc',
        'volume_eth',
        'price_change_vs_btc_24h',
        'price_change_vs_eth_24h',
        'price_change_vs_btc_12h',
        'price_change_vs_eth_12h',
        'price_change_vs_btc_4h',
        'price_change_vs_eth_4h',
        'volume_change_vs_btc_24h',
        'volume_change_vs_eth_24h',
        'created_at',
        'updated_at',
    ];


    public function currency()
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }
}
