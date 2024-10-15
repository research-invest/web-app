<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{

    /**
     * @param string $date
     * @return Carbon|null
     */
    public static function utcToTz(string $date): ?Carbon
    {
        if (!$date) {
            return null;
        }
        return Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($date), 'UTC')
            ->setTimezone(config('app.timezone'));
    }
}
