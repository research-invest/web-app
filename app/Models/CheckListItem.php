<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class CheckListItem extends Model
{
    use SoftDeletes, AsSource, Filterable;

    protected $fillable = [
        'trade_strategy_id',
        'title',
        'description',
        'priority',
        'sort_order',
        'user_id',
    ];

    public function strategy()
    {
        return $this->belongsTo(Strategy::class, 'trade_strategy_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tradeCheckListItems()
    {
        return $this->hasMany(TradeCheckListItem::class);
    }
}
