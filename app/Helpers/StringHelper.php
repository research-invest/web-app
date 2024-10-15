<?php

namespace App\Helpers;

class StringHelper
{

    public static function uniqueIdGenerate(): string
    {
        $letters = str_shuffle('abcdefghijklmnopqrstuvwxyz');
        $randomLetters = substr($letters, 0, 2);
        $numbers = random_int(100000, 999999);

        return $randomLetters . $numbers;
    }

    public static function trim($value): string
    {
        $value = trim($value);
        $value = str_replace(['\r', '\t', '\xC2xA0'], ['', '', ' '], $value);
//        $value = preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $value);
        return preg_replace('/(\s){3,}/', '$1$1', $value);
    }

    /**
     * Удалить пробелы из строки
     * @param $value
     * @return string
     */
    public static function removeSpaces($value): string
    {
        $value = self::trim($value);
        return $value ? preg_replace('/\s+/', '', $value) : '';
    }

    /**
     * Удалить числа из строки
     * @param $string
     * @return string
     */
    public static function removeInteger($string)
    {
        return trim(str_replace(range(0, 9), '', $string));
    }

    /**
     * Удалить все не цифровые символы из строки
     * @param $string
     * @return float
     */
    public static function onlyInt($string): float
    {
        $string = preg_replace('/[^\d,.]/u', '', $string);
        return (float)str_replace(',', '.', $string);

        return preg_replace('/\D/', '', $string);
    }
}
