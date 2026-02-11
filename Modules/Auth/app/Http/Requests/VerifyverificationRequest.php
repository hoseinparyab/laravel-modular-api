<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Auth\Enums\ContactType;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Enums\VerificationActionType;
use Modules\Auth\Services\VerificationCodeService;

class VerifyverificationRequest extends FormRequest
{

    public ContactType $contactType;
    public VerificationActionType $action;

    public function prepareForValidation(): void
    {
        $this->contactType = ContactType::detectContactTypes($this->input('contact') ?? '');
        $this->action = VerificationActionType::tryFrom($this->input('action') ?? '');
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'action' => [
                'bail',
                'required',
                'string',
                new Enum(VerificationActionType::class),
            ],
            'contact' => [
                'bail',
                'required',
                'string',
                ...$this->getContactValidationRule(),
            ],
            'code' => [
                'bail',
                'required',
                'string',
                "digits:6"
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                // Additional custom validation logic can be added here if needed
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $contact = $this->input('contact');
                $action = $this->action;
                $contactType = $this->contactType;
                if (!(new VerificationCodeService)->verifyCode($contact, $action, $this->contactType ?? ContactType::EMAIL, $this->input('code'))) {
                    $validator->errors()->add('code', __('auth::messages.invalid_verification_code'));
                }

            }
        ];
    }

    private function getContactValidationRule(): array
    {
        $verificationAction = $this->action;
        $contact = $this->input('contact');
        $action = $this->input('action');

        if (empty($contact) || empty($action)) {
            return [];
        }

        $contactType = ContactType::detectContactTypes($this->input('contact', ''));
        $verificationAction = VerificationActionType::tryFrom($this->input('action'));

        if (!$verificationAction) {
            return [];
        }

        if ($this->contactType === ContactType::EMAIL) {
            return [
                'email:rfc,dns',
                Rule::when($verificationAction->isContactNeedToBeUnique(), 'unique:users,email'),
            ];
        }

        return [
            'phone:mobile',
            Rule::when($verificationAction->isContactNeedToBeUnique(), 'unique:users,phone_number'),
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
