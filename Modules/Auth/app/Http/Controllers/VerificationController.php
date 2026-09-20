<?php
use Illuminate\Support\Facades\Log;
use Modules\Auth\Enums\ContactType;
use Modules\Auth\Http\Requests\SendVerificationRequest;
use Modules\Auth\Http\Requests\VerifyverificationRequest;
use Modules\Auth\Services\VerificationCodeService;
use Modules\Base\Http\Controllers\ApiController;

class VerificationController extends ApiController
{

    /**
     * Send a verification code to the user's contact (email or phone).
     */
    public function __construct(private VerificationCodeService $verificationCodeService)
    {

    }
    public function sendCode(SendVerificationRequest $request)
    {
        // Generate a random verification code
        $code = $this->verificationCodeService->generateCode(
            contact: $request->input('contact'),
            action: $request->action,
            contactType: $request->contactType,
        );

        if (! $this->SendCodeByContactType($request, $code)) {
            return $this->errorResponse(__('auth::messages.failed_to_send_verification_code'), 422);
        }

        Log::info('Verification OTP code', [
            'contact'      => $request->input('contact'),
            'action'       => $request->action?->value,
            'contact_type' => $request->contactType->value,
            'code'         => $code,
        ]);

        return response()->json([
            'message' => 'Verification code sent successfully',
        ], 200);
    }

    public function verifyCode(VerifyverificationRequest $request)
    {
        // next step is to verify the code
        $token = $this->verificationCodeService->createVerificationToken(
            contact: $request->input('contact'),
            action: $request->action,
            contactType: $request->contactType
        );

        return $this->successResponse(null, [
            'token' => $token,
        ]);
    }
    private function SendCodeByContactType(SendVerificationCodeRequest $request, string $code): bool
    {
        return match ($request->contactType) {
            ContactType::EMAIL => $this->verificationCodeService->sendCodeAsEmail(
                request: $request,
                contact: $request->input('contact'),
                code: $code,
            ),
            ContactType::PHONE => $this->verificationCodeService->sendCodeAsSMS(
                request: $request,
                contact: $request->input('contact'),
                code: $code,
            ),
            default            => false,
        };
    }
}
