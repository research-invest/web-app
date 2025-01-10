<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class TradeCheckListItem extends Model
{
    use SoftDeletes, AsSource, Attachable, Filterable;

    protected $fillable = [
        'trade_id',
        'check_list_item_id',
        'is_completed',
        'notes',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    public function checkListItem()
    {
        return $this->belongsTo(CheckListItem::class);
    }
}
