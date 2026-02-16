<?php

namespace Modules\Auth\Actions;

use Modules\Auth\Enums\ContactType;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\User\Models\User;

class RegisterUser
{
    public function handle(RegisterRequest $request)
    {
        $userData = $request->validate();
        if ($request->contactType === ContactType::EMAIL) {
            $userData['email_verified_at'] = now();
        }else{
            $userData['phone_verified_at'] = now();
        }
        $user = User::create($userData);
        //most create token for user after register and return it in response for auto login
        return $user;
    }
}
