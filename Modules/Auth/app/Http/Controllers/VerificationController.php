<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Auth\Http\Requests\SendVerificationRequest;

class VerificationController extends Controller {

    /**
     * Send a verification code to the user's contact (email or phone).
     */
    public function sendCode(SendVerificationRequest $request)
    {
        // Implementation for sending verification code goes here.
        return response()->json([
            'message' => 'Verification code sent successfully.',
        ], 200);
    }
}
