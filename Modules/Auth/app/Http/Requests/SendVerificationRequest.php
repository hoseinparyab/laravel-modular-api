<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Modules\Auth\Enums\ContactType;
use Modules\Auth\Enums\VerificationAction;

class SendVerificationRequest extends FormRequest
{
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
                new Enum(VerificationAction::class),
            ],
            'contact' => [
                'bail',
                'required',
                'string',
                ...$this->getContactValidationRule(),
            ],
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
        $verificationAction = VerificationAction::tryFrom($this->input('action'));

        if (!$verificationAction) {
            return [];
        }

        if ($contactType === ContactType::EMAIL) {
            return [
                'email:rfc,dns',
                Rule::when($verificationAction->isContactNeedToBeUnique(), 'unique:users,email'),
                Rule::when($verificationAction->isContactNeedToExist(), 'exists:users,email'),
            ];
        }

        return [
            'phone:mobile',
            Rule::when($verificationAction->isContactNeedToBeUnique(), 'unique:users,phone'),
            Rule::when($verificationAction->isContactNeedToExist(), 'exists:users,phone'),
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
