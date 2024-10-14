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
class Setting extends BaseModel
{
    use HasFactory, AsSource, Filterable;

    private static array $data = [];

    const string COUNT_ENERGY_SEND_TR = 'count_energy_by_send_tr';

    public const string TRX_AMOUNT = 'trx_amount';

    public const string AML_SUM_CHECK = 'aml_sum_check';

    public const string ALERT_ENERGY_MIN_SUM_CHECK = 'alert_energy_min_sum_check';
    public const string IS_USE_OUR_TRON_NODE = 'is_use_our_tron_node';

    protected $guarded = [];

    public static function getTrxAmount(): float
    {
        return (float)self::getValueByKey(self::TRX_AMOUNT, 30);
    }

    public static function getCountEnergy(): float
    {
        return (float)self::getValueByKey(self::COUNT_ENERGY_SEND_TR, 65000);
    }

    public static function getAmlSumCheck(): float
    {
        return (float)self::getValueByKey(self::AML_SUM_CHECK, 31);
    }

    public static function getAlertEnergyMinSumCheck(): float
    {
        return (float)self::getValueByKey(self::ALERT_ENERGY_MIN_SUM_CHECK, 20);
    }

    public static function isUseOurTronNode(): bool
    {
        return (bool)self::getValueByKey(self::IS_USE_OUR_TRON_NODE, true) * 1;
    }

    private static function getValueByKey(string $key, mixed $defaultValue = null)
    {
        if (isset(self::$data[$key])) {
            return self::$data[$key];
        }

        $data = self::query()->where('key', $key)->first();
        return self::$data[$key] = ($data->value ?? $defaultValue);
    }

}
