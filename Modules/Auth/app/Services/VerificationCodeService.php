<?php

namespace Modules\Auth\Services;

use Modules\Auth\Enums\ContactType;
use Modules\Auth\Enums\VerificationActionType;
use Illuminate\Support\Facades\Cache;

class VerificationCodeService
{

    public function getCachekey( string $contact, VerificationActionType $action, ContactType $contactType): string
    {
        $contact = hash('sha256', "{$action->value}:{$contactType->value}:{$contact}");
        return "verification:{$contact}";

    }
    public function getRetryTime(string $contact, VerificationActionType $action, ContactType $contactType): ?int
    {
        $cacheKey = $this->getCachekey($contact, $action, $contactType);
        $data = Cache::get($cacheKey);
        if ($data && isset($data['expires_at'])) {
            $expiresAt = $data['expires_at'];
            if ($expiresAt->isFuture()) {
                return now()->diffInSeconds($expiresAt);
            }
        }
        return null;
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
