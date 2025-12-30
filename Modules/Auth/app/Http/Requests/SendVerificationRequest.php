<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Auth\Enums\ContactType;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;
use Modules\Auth\Enums\VerificationActionType;
use Modules\Auth\Services\VerificationCodeService;

class SendVerificationRequest extends FormRequest
{

    public ContactType $contactType;

    public function prepareForValidation(): void
    {
        $this->contactType = ContactType::detectContactTypes($this->input('contact')?? '');
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
                $action = VerificationActionType::tryFrom($this->input('action'));
                if (!$action) {
                    return;
                }
                $retryTime = (new VerificationCodeService)->getRetryTime($contact, $action, $this->contactType);
                if ($retryTime) {
                    $validator->errors()->add('contact', __('auth::messages.verification_code_retry_after', ['seconds' => abs($retryTime)]));
                }
            }
        ];
    }

    private function getContactValidationRule(): array
    {
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
                Rule::when($verificationAction->isContactNeedToExist(), 'exists:users,email'),
            ];
        }

        return [
            'phone:mobile',
            Rule::when($verificationAction->isContactNeedToBeUnique(), 'unique:users,phone_number'),
            Rule::when($verificationAction->isContactNeedToExist(), 'exists:users,phone_number'),
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
