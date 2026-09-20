<?php
namespace Modules\Auth\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Modules\Auth\Actions\CreateUserToken;
use Modules\Auth\Actions\ForgotPassword;
use Modules\Auth\Actions\RegisterUser;
use Modules\Auth\Http\Requests\CheckUserRequest;
use Modules\Auth\Http\Requests\ForgotPasswordRequest;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Base\Http\Controllers\ApiController;
use Modules\User\Models\User;

class AuthController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function checkUser(CheckUserRequest $request)
    {
        $contact = $request->validated()['contact'];

        $exists = User::where('email', $contact)
            ->orWhere('phone', $contact)
            ->exists();

        if ($exists) {
            return $this->successResponse(
                message: __('auth::messages.user_exists'),
            );
        }
        return $this->errorResponse(
            message: __('auth::messages.user_not_found'),
            code: 404,
        );
    }
    public function login(LoginRequest $request)
    {
        /** @var User */
        $user  = Auth::user();
        $token = (new CreateUserToken)->handle($user, isEncrypted: true);

        return $this->successResponse(
            message: __('auth::auth.login_success'),
            data: [
                'token' => $token,

            ],
            cookies: [
                cookie(
                    'x_web_token',
                    $token,
                    60 * 24 * 30, // 30 days
                    '/',
                    config('session.domain'),
                    true,
                    true,
                ),
            ]
        );
    }
    public function register(RegisterRequest $request)
    {
        $user  = (new RegisterUser)->handle($request);
        $token = (new CreateUserToken)->handle($user, isEncrypted: true);

        return $this->successResponse(
            message: __('auth::auth.registration_success'),
            data: [
                'token' => $token,
            ],
            cookies: [
                cookie(
                    'x_web_token',
                    $token,
                    60 * 24 * 30, // 30 days
                    '/',
                    config('session.domain'),
                    true,
                    true,
                ),
            ]
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            (new ForgotPassword)->handle($request);
        } catch (\Throwable $th) {
            return $this->errorResponse(
                message: __('auth::messages.password_reset_failed'),
                code: 503,
            );
        }
        return $this->successResponse(
            message: __('auth::messages.password_reset_success'),
        );
    }
}
