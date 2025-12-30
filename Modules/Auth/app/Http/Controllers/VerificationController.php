<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Auth\Enums\ContactType;
use Modules\Auth\Enums\VerificationActionType;
use Modules\Auth\Services\VerificationCodeService;
use Modules\Auth\Http\Requests\SendVerificationRequest;

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
          action: VerificationActionType::from($request->input('action')),
          contactType: $request->contactType,
        );
        return response()->json(['code' => $code], 200);    }
}
