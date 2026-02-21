<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Modules\Auth\Actions\CreateUserToken;
use Modules\Auth\Actions\RegisterUser;
use Modules\Auth\Http\Requests\CheckUserRequest;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\User\Models\User;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function checkUser(CheckUserRequest $request)
    {
        $contact = $request->validated()['contact'];

        $exists = User::where('email', $contact)
            ->orWhere('phone_number', $contact)
            ->exists();

        return response()->json([
            'exists' => (bool) $exists,
            'message' => $exists ? __('auth::messages.user_exists') : __('auth::messages.user_not_found'),
        ], $exists ? 200 : 404);
    }
    public function login(LoginRequest $request)
    {
        /** @var User */
        $user = Auth::user();
        $token = (new CreateUserToken)->handle($user, isEncrypted: true);

        return $this->successResponse(
            __('auth::auth.login_success'),
            data: [
                'token' => $token,
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                ]
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
                )
            ]
        );
    }

    public function register( RegisterRequest $request)
    {
          $user = (new RegisterUser())->handle($request);
          $token = (new CreateUserToken)->handle($user, isEncrypted: true);

          return $this->successResponse(
               __('auth::auth.registration_success'), // Assuming this translation exists
               data: [
                    'token' => $token,
               ],
               cookies: [
                    cookie(
                        'x-web_token',
                        $token,
                        60 * 24 * 30, // 30 days
                        '/',
                        config('session.domain'),
                        config('session.secure', true),
                        true,
                    )
               ]
          );
    }
}
