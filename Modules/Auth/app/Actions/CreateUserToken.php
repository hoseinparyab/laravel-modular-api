<?php
namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Crypt;
use Modules\User\Models\User;

class CreateUserToken
{
    public function handle(User $user, string $tokenName = 'x-web-token', bool $isEncrypted = false): string
    {
        $token = $user->createToken(name: $tokenName, expiresAt: now()->addDays(30))->plainTextToken;
        if ($isEncrypted) {
            $token = Crypt::encryptString($token);
        }
        return $token;
    }
}
