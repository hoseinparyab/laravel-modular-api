<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Crypt;
use Modules\User\Models\User;

class CreateUserToken
{
    /**
     * Handle creating a new token for the user.
     *
     * @param User $user
     * @param string $tokenName
     * @param bool $isEncrypted
     * @return string
     */
    public function handle(User $user, string $tokenName = 'x-web-token', bool $isEncrypted = false): string
    {
        $token = $user->createToken($tokenName, expiresAt: now()->addDays(30))->plainTextToken;

        return $isEncrypted ? Crypt::encryptString($token) : $token;
    }
}
