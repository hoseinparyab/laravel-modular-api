<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        return response()->json([
            'message' => 'User registered successfully',
            'data' => $user,
        ], 201);
    }
}
