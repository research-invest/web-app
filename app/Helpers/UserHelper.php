<?php

namespace App\Helpers;

use App\Models\Setting;
use App\Models\User;

class UserHelper
{

    /**
     * @return \Illuminate\Contracts\Auth\Authenticatable|null|User
     */
    public static function get()
    {
        return auth()->user();
    }

    /**
     * @return int|null
     */
    public static function getId(): ?int
    {
        return self::get() ? self::get()->id : null;
    }

    /**
     * @return bool
     */
    public static function isAdmin(): bool
    {
        return self::get() ? self::get()->isRoleAdmin() : false;
    }

    /**
     * @return bool
     */
    public static function isOperator(): bool
    {
        return self::get() ? self::get()->isOperator() : false;
    }

    /**
     * @return bool
     */
    public static function isManager(): bool
    {
        return self::get() ? self::get()->isManager() : false;
    }

    /**
     * @return bool
     */
    public static function isManagers(): bool
    {
        return self::isManager() || self::isAdmin();
    }

    public static function generateName(): string
    {
        return 'user_#' . random_int(3000, 99999);
    }

    public static function isGuest(): bool
    {
        return self::get() === null;
    }


}
