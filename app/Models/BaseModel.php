<?php

namespace App\Models;

use App\Helpers\UserHelper;
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

    public function scopeByCreator($query)
    {
        return $query->where('user_id', UserHelper::getId());
    }

}
