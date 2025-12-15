<?php

namespace Modules\Auth\Http\Controllers;

use Modules\Auth\Http\Requests\CheckUserRequest;
use Illuminate\Http\Request;
use Modules\User\Models\User;
use App\Http\Controllers\Controller;

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
}
