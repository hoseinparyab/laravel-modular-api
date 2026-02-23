<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Enums\ContactType;
use Modules\Auth\Enums\VerificationActionType;
use Modules\Auth\Services\VerificationCodeService;
use Modules\User\Models\User;

class ForgotPasswordRequest extends FormRequest
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
            'contact' => [
                'required',
                'string',
                ...$this->getContactValidationRules(),
            ],
            'token' => [
                'required',
                'string',
            ],
            'password' => [
                'required',
                'string',
                'min:8', // Minimum length requirement
                'max:255', // Maximum length requirement
            ],
        ];
    }
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $validatedData = $this->validated();
                $tokenData = (new VerificationCodeService)->getVerificationToken(
                    $validatedData['token'],
                    [
                        'email' => $validatedData['contact'],
                        'phone_number' => $validatedData['contact'],
                    ],
                    VerificationActionType::FORGOT_PASSWORD,
                    hash('sha256', $this->userAgent() . ':'  . $this->ip()),
                );
                if (!$tokenData) {
                    $validator->errors()->add('token', __('auth::messages.invalid_or_expired_token'));
                    return;
                }
                $user = User::where($this->contactType->value, $validatedData['contact'])->first();
                if (!$user) {
                    $validator->errors()->add('contact', __('auth::messages.user_not_found'));
                    return;
                }
            },
        ];
        $user->verfiedContact($this->contactType);
        Auth::oneUsingId($user->id);
    }
    public function messages(): array
    {
        return [
            'contact.required' => __('auth::messages.contact_required'),
            'contact.exists' => __('auth::messages.user_not_found'),
            'token.required' => __('auth::messages.token_required'),
            'password.required' => __('auth::messages.password_required'),
            'password.min' => __('auth::messages.password_min_8'),
        ];
    }
    public function getContactValidationRules(): array
    {


        if ($this->contactType === ContactType::EMAIL) {
            return [
                'email',
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
