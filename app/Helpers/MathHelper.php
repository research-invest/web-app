<?php

namespace App\Helpers;

class MathHelper
{

    /**
     * Округление в меньшую строну с учетом кол-ва знаков после запятой
     * @param $num
     * @param int $base - количество знаков после запятой
     * @return float|int
     */
    public static function roundingDown($num, int $base = 2): float|int
    {
        return floor($num * (10 ** $base)) / (10 ** $base);
    }

    /**
     * Округление в большую строну с учетом кол-ва знаков после запятой
     * @param $num
     * @param int $base - количество знаков после запятой
     * @return float|int
     */
    public static function roundingUp($num, int $base = 2): float|int
    {
        return ceil($num * (10 ** $base)) / (10 ** $base);
    }

    /**
     * @param ?float $num
     * @param int $decimal
     * @return string
     */
    public static function format($num, int $decimal = 2): string
    {
        if (!$num) {
            return '';
        }

        $formatted = number_format($num, $decimal, '.', ' ');

        $pos = strrpos($formatted, '.');
        if ($pos !== false) {
            $integerPart = substr($formatted, 0, $pos);
            $decimalPart = rtrim(substr($formatted, $pos + 1), '0');
            $formatted = $decimalPart ? $integerPart . '.' . $decimalPart : $integerPart;
        }

        return $formatted;

        return number_format($num, $decimal, '.', ' ');
    }

    /**
     * @param $s
     * @return int
     */
    public static function toInt($s): int
    {
        return (int)preg_replace('/[^\-\d]*(\-?\d*).*/', '$1', $s);
    }

    /**
     * @param $s
     * @return float
     */
    public static function toFloat($s): float
    {
        return (double)filter_var($s, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Округлить значения массива и вложенных массивов
     * @param $arr
     * @param int $precision
     * @return mixed
     */
    public static function roundArrayValues($arr, int $precision = 2): mixed
    {
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $arr[$key] = self::roundArrayValues($val);
            } else {
                $arr[$key] = round($val, $precision);
            }
        }
        return $arr;
    }

    /**
     * @param float $min
     * @param float $max
     * @param int $round
     * @return float
     */
    public static function randomFloat(float $min = 0, float $max = 1, int $round = 2): float
    {
        $num = $min + ($max - $min) * (mt_rand() / mt_getrandmax());
        return round($num, $round);
    }

    /**
     * @param float $number
     * @param float $percent
     * @return float
     */
    public static function getPercent(float $number, float $percent): float
    {
        return ($number / 100) * $percent;
    }

    /**
     * @param float $numberFrom
     * @param float $numberTo
     * @return float
     */
    public static function getPercentOfNumber(float $numberFrom, float $numberTo): float
    {
        if(!$numberTo){
            return 0;
        }

        return ($numberFrom - $numberTo) / $numberTo * 100;
    }

    /**
     * @param float $number
     * @param float $percent
     * @param int $round
     * @return float
     */
    public static function addPercent(float $number, float $percent, int $round = 6): float
    {
        $number += self::getPercent($number, $percent);
        return $number;
        return self::roundingDown($number, $round);
    }

    /**
     * @param float $number
     * @param float $percent
     * @param int $round
     * @return float
     */
    public static function subPercent(float $number, float $percent, int $round = 6): float
    {
        return self::addPercent($number, -$percent, $round);
    }

    /**
     * @param $value
     * @param $total
     * @return float|int
     */
    public static function calculatePercent($value, $total): float|int
    {
        if ($total === 0) {
            return 0;
        }

        return (($value - $total) / $total) * 100;
    }


    /**
     * Проверка на кратность числа
     * @param mixed $amount
     * @param int $multiplier
     * @return bool
     */
    public static function isMultiplier(mixed $amount, int $multiplier = 100): bool
    {
        return self::toFloat($amount) % $multiplier === 0;
    }

    /**
     * Форматирование числа с автоматическим округлением
     * @param float $number Число для форматирования
     * @param int $round
     * @return string Отформатированное число
     */
    public static function formatNumber(float $number, int $round = 2): string
    {
        if ($number == 0) {
            return '0.00';
        }

        $sign = $number < 0 ? '-' : '';
        $number = abs($number);

        if ($number >= 1) {
            return $sign . number_format($number, 2, '.', ' ');
        }

        if ($number >= 0.0001) {
            return $sign . number_format($number, 4, '.', '');
        }

        $significantDigits = -floor(log10($number)) + $round;
        return $sign . sprintf("%." . $significantDigits . "f", $number);
    }

    /**
     * @param $number
     * @param int $decimals
     * @return string
     */
    public static function humanNumber($number, int $decimals = 1): string
    {
        if ($number >= 1_000_000_000) {
            return number_format($number / 1_000_000_000, $decimals, ',', '') . ' млрд';
        }

        if ($number >= 1_000_000) {
            return number_format($number / 1_000_000, $decimals, ',', '') . ' млн';
        }

        if ($number >= 1_000) {
            return number_format($number / 1_000, $decimals, ',', '') . ' тыс';
        }

        return number_format($number, $decimals, ',', '');
    }
}
