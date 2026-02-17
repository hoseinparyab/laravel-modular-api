<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
// use Modules\Auth\Mail\VerificationCodeMail;
use Modules\Auth\Emails\VerificationCodeEmail;
use Modules\Auth\Enums\ContactType;
use Modules\Auth\Enums\VerificationActionType;
use Modules\Auth\Http\Requests\SendVerificationRequest;
use Modules\Auth\Http\Requests\VerifyverificationRequest;
use Modules\Auth\Services\MelipayamakService;

class VerificationCodeService
{
    public function createVerificationToken(string $contact, VerificationActionType $action, ContactType $contactType): string
    {
        do {
            $token = Str::random(100);
        } while (Cache::has("verification_after:token:{$token}"));

        Cache::put("verification_after:token:{$token}", [
            'contact' => $contact,
            'action' => $action,
            'contact_type' => $contactType,
            'identifier' => hash('sha256', request()->userAgent() . request()->ip()),
        ], now()->addMinutes(10));

        return $token;
    }
    public function getVerificationToken(string $token, array $contactList, VerificationActionType $action): ?array
    {
        $identifier = hash('sha256', request()->userAgent() . request()->ip());
        $cacheKey = "verification_after:token:{$token}";
        $tokenData = Cache::pull($cacheKey);

        if (!$tokenData) {
            return null;
        }

        $contact = $tokenData['contact_type'] === ContactType::EMAIL ? $contactList['email'] : $contactList['phone'];

        if (
            $tokenData['contact'] === $contact &&
            $tokenData['action'] === $action &&
            $tokenData['identifier'] === $identifier
            ) {
            return $tokenData;
        }

        return null;
    }


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
    public function forgetCode(string $contact, VerificationActionType $action, ContactType $contactType): void
    {
        $cacheKey = $this->getCachekey($contact, $action, $contactType);
        Cache::forget($cacheKey);
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

    public function sendCodeAsSMS( SendVerificationRequest $request, string $contact, int $code): array|bool
    {
        try {
            $service = new MelipayamakService();

            // Note: Simplified text to avoid "Invalid Content" (Code 11) filters
            $text = $request->input('text', "کد تایید شما به وبسایت ناجینو خوش امدید: {$code}");
            $from = $request->input('from');
            $isFlash = $request->input('isflash', true) ? true : false;

            $result = $service->send($contact, $text, $from, $isFlash);

            if (isset($result['success']) && !$result['success']) {
                $this->forgetCode(
                    contact: $contact,
                    action: $request->action,
                    contactType: $request->contactType,
                );
            }

            return $result;


        } catch (\Throwable $th) {
            $this->forgetCode(
                contact: $contact,
                action: $request->action,
                contactType: $request->contactType,
            );
            return false;
        }
    }
    public function sendCodeAsEmail(SendVerificationRequest $request, string $contact, int $code): array|bool
    {
        try {
                Log::info("OTP Code for email $contact is: $code");
                Mail::to($contact)->send(new VerificationCodeEmail($code));
            return true;
        } catch (\Throwable $th) {
            Log::error("Failed to send OTP email to $contact: " . $th->getMessage());
            $this->forgetCode(
                contact: $contact,
                action: $request->action,
                contactType: $request->contactType,
            );
            return false;
        }
    }
    public function verifyCode(string $contact, VerificationActionType $action, ContactType $contactType, int $code): bool
    {
        $cacheKey = $this->getCachekey($contact, $action, $contactType);
        $cacheValue = Cache::get($cacheKey);
        if($cacheValue && $cacheValue['code'] === $code ){
            $this-> forgetCode($contact, $action, $contactType);
            return true;
        }
        return false;
    }

    public function handle() {}
}
