<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Enums\ContactType;
use Modules\Auth\Enums\VerificationActionType;
use Modules\Auth\Services\VerificationCodeService;

class RegisterRequest extends FormRequest
{
    public ContactType $contactType;
    public string $contact;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Define your registration validation rules here
            // For example:
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|phone:mobile|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'token' => 'required|string', // This will be the verification token from the previous step
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422));
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $validatedData = $this->validated();
                $tokenData = (new VerificationCodeService())->getVerificationToken(
                    $validatedData['token'],
                    [
                        'email' => $validatedData['email'],
                        'phone' => $validatedData['phone_number'],
                    ],
                    VerificationActionType::REGISTER
                );

                if (!$tokenData) {
                    $validator->errors()->add('token', __('auth::messages.invalid_verification_code'));
                    return;
                }

                $this-> contactType = $tokenData ['contact_type'];
                $this->contact=$tokenData['contact'];
            }
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
