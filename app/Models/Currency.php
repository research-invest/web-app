<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property integer $type
 * @property float $last_price
 * @property integer $is_favorite
 * @property string $exchange
 */
class Currency extends BaseModel
{
    use HasFactory, AsSource, Filterable;

    protected $guarded = [];

    public function getNamePriceAttribute(): string
    {
        return sprintf('%s (%s)', $this->name, $this->last_price);
    }

}
