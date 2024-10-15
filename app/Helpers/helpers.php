<?php

if (!function_exists('isLocalEnv')) {
    function isLocalEnv(): bool
    {
        return \App\Helpers\Development::isLocal();
    }
}

if (!function_exists('isProductionEnv')) {
    function isProductionEnv(): bool
    {
        return \App\Helpers\Development::isProduction();
    }
}

if (!function_exists('getDateTimeFormat')) {
    function getDateTimeFormat(): string
    {
        return 'd.m.Y - H:i';
    }
}

if (!function_exists('getDateFormat')) {
    function getDateFormat(): string
    {
        return 'd.m.Y';
    }
}

