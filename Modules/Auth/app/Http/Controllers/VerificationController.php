<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Auth\Enums\ContactType;

use Modules\Auth\Services\VerificationCodeService;
use Modules\Auth\Http\Requests\SendVerificationRequest;
use Modules\Auth\Http\Requests\VerifyverificationRequest;

class VerificationController extends Controller {

    /**
     * Send a verification code to the user's contact (email or phone).
     */
    public function __construct(private VerificationCodeService $verificationCodeService) {

    }

    public function sendCode(SendVerificationRequest $request)
    {
        // Generate a random verification code
        $code = $this->verificationCodeService->generateCode(
          contact: $request->input('contact'),
          action: $request->action,
          contactType: $request->contactType,
        );

        $responseStatus = true;
        if ($request->contactType === ContactType::EMAIL)
        {
            // Send the code via email
            $responseStatus = $this->verificationCodeService->sendCodeAsEmail(
                request: $request,
                contact: $request->input('contact'),
                code: $code,
            );
        }

        if ($request->contactType === ContactType::PHONE)
        {
            // Send the code via SMS
            $responseStatus = $this->verificationCodeService->sendCodeAsSMS(
                request: $request,
                contact: $request->input('contact'),
                code: $code,
            );
        }

        if (is_array($responseStatus) && isset($responseStatus['success']) && !$responseStatus['success']) {
            return response()->json([
                'message' => $responseStatus['message'] ?? 'Failed to send verification code',
                'details' => $responseStatus
            ], 400);
        }

        if ($responseStatus === false) {
             return response()->json([
                'message' => 'Failed to send verification code'
            ], 400);
        }

        return response()->json(['code' => $code, 'message' => 'Verification code sent successfully'], 200);
    }

    public function verifyCode(VerifyverificationRequest $request)
    {
        // next step is to verify the code
        $token = $this->verificationCodeService->createVerificationToken(
            contact: $request->input('contact'),
            action: $request->action,
            contactType: $request->contactType
        );

        return response()->json([
            'token' => $token,
        ]);
    }
}

