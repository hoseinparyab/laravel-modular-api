<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Modules\Auth\Actions\RegisterUser;
use Modules\Auth\Http\Requests\CheckUserRequest;
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
    public function register( RegisterRequest $request)
    {
          $user =(new RegisterUser())->handle($request);
          $token = $user->createToken('x-web-token',expiresAt: now()->addDays(30))->plainTextToken;
          $encryptedToken = Crypt::encryptString($token);
          return response()->json([
                'token' => $encryptedToken
          ])->withCookie(cookie('x-web-token', $encryptedToken, 60 * 24 * 30, //30 days
            '/',
            config('session.domain'),
            config('session.secure', true), // secure
            true, // httpOnly
        ));
    }
}
