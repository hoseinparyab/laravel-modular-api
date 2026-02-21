<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;
use Modules\Auth\Enums\ContactType;
use Modules\Auth\Enums\VerificationActionType;
use Modules\Auth\Services\VerificationCodeService;
use Modules\User\Models\User;

class LoginRequest extends FormRequest
{

    public ContactType $contactType;

    public function prepareForValidation(): void
    {
        $this->contactType = ContactType::detectContactTypes($this->input('contact') ?? '');
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'login_type' => ['required', 'string', 'in:password,token'],
            'contact' => ['required', 'string', ...$this->getContactValidationRules()],
            'password' => ['nullable', 'string', 'min:8,max:255'],
            'token' => ['nullable', 'string', 'max:255'],
        ];
    }
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $validatedData = $this->validated();
                if ($this->input('login_type') === 'password') {
                    $this->validatePasswordLogin($validatedData, $validator);
                } else {
                    $this->validateTokenLogin($validatedData, $validator);
                }
            },
        ];
    }
    public function validatePasswordLogin(array $validatedData, Validator $validator)
    {
        if (empty($validatedData['password'])) {
            $validator->errors()->add('password', 'The password field is required.');
            return;
        }

        $credentials = [
            $this->contactType->value => $validatedData['contact'],
            'password' => $validatedData['password'],
        ];

        if (!Auth::attempt($credentials)) {
            $validator->errors()->add('contact', 'The provided credentials are incorrect.');
        }
    }
    public function validateTokenLogin(array $validatedData, Validator $validator)
    {
        if (empty($validatedData['token'])) {
            $validator->errors()->add('token', 'The token field is required.');
            return;
        }
        $tokenData = (new VerificationCodeService())->getVerificationToken(
            $validatedData['token'],
            [
                'email' => $validatedData['contact'],
                'phone' => $validatedData['contact'],
            ],
            VerificationActionType::LOGIN,
            hash('sha256',$this->userAgent().''.$this->ip())
        );
        if(!$tokenData){
            $validator->errors()->add('token', 'The provided token is invalid or has expired.');
            return;
        }
        $user = User::where($this->contactType->value, $validatedData['contact'])
        ->first();

        if (!$user) {
            $validator->errors()->add('contact', 'No user found with the provided contact information.');
            return;
        }
        $user ->verifiedContact($this->contactType);


        Auth::onceUsingId($user->id);
    }
    public function getContactValidationRules(): array
    {


        if ($this->contactType === ContactType::EMAIL) {
            return [
                'email:rfc,dns',
                'exists:users,email',

            ];
        }

        return [
            'phone:mobile',
            'exists:users,phone_number',
        ];
    }
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
