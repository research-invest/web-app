<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $created_by
 * @property int $updated_by
 */
class BaseModel extends Model
{


    public function scopeIsActive($query)
    {
        return $query->where('is_active', true);
    }

}
