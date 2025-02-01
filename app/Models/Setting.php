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
 * @property int $type_field
 */
class Setting extends BaseModel
{
    use HasFactory, AsSource, Filterable;


    const int TYPE_FIELD_TEXT = 1;
    const int TYPE_FIELD_BOOL = 2;


    private static array $data = [];

    const string HUNTER_FUNDING_LESS_VALUE = 'hunter_funding_less_value';

    protected $guarded = [];


    public static function getHunterFundingLessValue(): float
    {
        return (float)self::getValueByKey(self::HUNTER_FUNDING_LESS_VALUE, -0.75);
    }


    private static function getValueByKey(string $key, mixed $defaultValue = null)
    {
        if (isset(self::$data[$key])) {
//            return self::$data[$key];
        }

        $data = self::query()->where('key', $key)->first();
        return self::$data[$key] = ($data->value ?? $defaultValue);
    }

    public function isTypeBool(): bool
    {
        return $this->type_field === self::TYPE_FIELD_BOOL;
    }


}
