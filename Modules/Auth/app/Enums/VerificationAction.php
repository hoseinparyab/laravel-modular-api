<?php

namespace Modules\Auth\Enums;

enum VerificationAction: string
{
    case REGISTER = 'register';
    case LOGIN = 'login';
    public function isContactNeedToBeUnique(): bool
    {
        return $this === self::REGISTER;
    }

    public function isContactNeedToExist(): bool
    {
        return $this === self::LOGIN;
    }
}
