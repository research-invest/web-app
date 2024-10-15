<?php

namespace App\Helpers;

class FileHelper
{
    public static function getRelativeUrl(string $url): ?string
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return parse_url($url, PHP_URL_PATH);
    }
}
