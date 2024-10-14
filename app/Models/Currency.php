<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

/**
 * @property int $id
 * @property string $key
 * @property string $value
 * @property string $description
 */
class Currency extends BaseModel
{
    use HasFactory, AsSource, Filterable;

    protected $guarded = [];


}
