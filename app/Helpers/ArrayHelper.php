<?php

namespace App\Helpers;

class ArrayHelper
{

    /**
     * @param array $arr
     * @param string $delimetr
     * @return string
     */
    public static function implodeWithKeys(array $arr, string $delimetr = ','): string
    {
        array_walk($arr, static function (&$value, $key) {
            $value = "$key: $value";
        });

        return implode($delimetr, $arr);
    }


    /**
     *  Получить значения массива по нескольким ключам
     * @param array $arr
     * @param array $keys
     * @return array
     */
    public static function getValuesByKeys(array $arr, array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (isset($arr[$key])) {
                $result[] = $arr[$key];
            }
        }
        return $result;
    }

    /**
     * @param array $arr
     * @param string $columnName
     * @param string $minMaxType min|max
     * @return float
     */
    public static function getMinMaxValueByColumn(array $arr, string $columnName, string $minMaxType = 'min'): float
    {
        if (empty($arr)) {
            return 0;
        }

        $result = array_map(static function ($items) use ($columnName, $minMaxType) {
            return $items[$columnName] > 0 ? $items[$columnName] : ($minMaxType === 'min' ? PHP_INT_MAX : 0);
        }, $arr);

        return $minMaxType === 'min' ? min($result) : max($result);
    }

    /**
     * @param array $arr
     * @return string
     */
    public static function hash(array $arr): string
    {
        return md5(serialize($arr));
    }
}
