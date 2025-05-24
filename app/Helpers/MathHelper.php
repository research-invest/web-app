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
     * @param float $initialValue
     * @param float $finalValue
     * @return float
     */
    public static function getPercentOfNumber($initialValue, $finalValue): float
    {
        if (!$initialValue || !$finalValue) {
            return 0;
        }

        return (($finalValue - $initialValue) / abs($initialValue)) * 100;
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
    public static function formatNumber(?float $number, int $round = 2): string
    {
        if ((float)$number === 0.0) {
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


    /**
     * Рассчитывает индекс волатильности на основе массива цен
     */
    public static function calculateVolatilityIndex(array $prices): float
    {
        if (empty($prices)) {
            return 0;
        }

        // Находим максимальную и минимальную цены
        $maxPrice = max($prices);
        $minPrice = min($prices);

        // Находим среднюю цену
        $avgPrice = array_sum($prices) / count($prices);

        // Рассчитываем процентный размах от средней цены
        $volatilityIndex = (($maxPrice - $minPrice) / $avgPrice) * 100;

        return round($volatilityIndex, 4);
    }

    /**
     * @param array $data
     * @return float
     */
    public static function calculateVolatility(array $data): float
    {
        if(empty($data)){
            return 0;
        }

        $mean = array_sum($data) / count($data);
        $squaredDiffs = array_map(fn($v) => ($v - $mean) ** 2, $data);
        return round(sqrt(array_sum($squaredDiffs) / count($data)), 2);
    }

    /**
     * @param array $data
     * @return string
     */
    public static function renderSparkline(array $data): string
    {
        if(empty($data)){
            return '';
        }

        $blocks = ['▁', '▂', '▃', '▄', '▅', '▆', '▇', '█'];
        $min = min($data);
        $max = max($data);
        $range = $max - $min ?: 1;

        return collect($data)
            ->map(function ($value) use ($min, $range, $blocks) {
                $index = (int)(($value - $min) / $range * (count($blocks) - 1));
                return $blocks[$index];
            })
            ->implode('');
    }
}
