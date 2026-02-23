<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Hash;
use Modules\Auth\Http\Requests\ForgotPasswordRequest;
use Modules\User\Models\User;

class ForgotPassword
{
    public function handle(ForgotPasswordRequest $request)
    {
        $validated = $request->validated();
        $contact = $validated['contact'];

        $user = User::where('email', $contact)
            ->orWhere('phone_number', $contact)
            ->first();

        if (!$user) {
            throw new \Exception(__('auth::messages.user_not_found'));
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return $user;
    }
}
