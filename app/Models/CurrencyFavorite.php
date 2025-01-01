<?php

namespace App\Models;


class CurrencyFavorite extends BaseModel
{
    protected $table = 'currencies_favorites';

    protected $fillable = ['user_id', 'currency_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
