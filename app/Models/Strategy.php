<?php

namespace App\Models;

use Orchid\Screen\AsSource;

/**
 * @property string $name
 * @property string $description
 * @property integer $user_id
 *
 * @property User $user
 * @property Trade $trades
 */
class Strategy extends BaseModel
{
    use AsSource;

    protected $table = 'strategies';

    protected $fillable = [
        'name',
        'description',
        'user_id',
    ];

    protected $casts = [
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trades()
    {
        return $this->hasMany(Trade::class);
    }
}
