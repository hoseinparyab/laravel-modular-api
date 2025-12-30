<?php

namespace Modules\Auth\Services;

use Modules\Auth\Enums\ContactType;
use Modules\Auth\Enums\VerificationActionType;
use Illuminate\Support\Facades\Cache;

class VerificationCodeService
{

    public function getCachekey( string $contact, VerificationActionType $action, ContactType $contactType): string
    {
        return "verification:{$action->value}:{$contactType->value}:{$contact}";

    }
    public function generateCode(string $contact, VerificationActionType $action, ContactType $contactType, ?int $expiresminutes = null)
    {
        if($expiresminutes === null){
            $expiresminutes = $contactType === ContactType::EMAIL ? 5 : 1;

        }
        $code =  random_int(100000, 999999);
        $cacheKey = $this->getCachekey($contact, $action, $contactType);
        $expiredAt = now()->addMinutes($expiresminutes);
        Cache::put($cacheKey, [
            'code' => $code,
            'expires_at' => $expiredAt,
        ]);
      return $code;
    }
    public function handle() {}
}
