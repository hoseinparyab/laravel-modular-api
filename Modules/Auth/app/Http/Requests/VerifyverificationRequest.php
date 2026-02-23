<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;
use Modules\Auth\base\BaseAuthRequest;
use Modules\Auth\Enums\ContactType;
use Modules\Auth\Enums\VerificationActionType;
use Modules\Auth\Services\VerificationCodeService;

class VerifyverificationRequest extends BaseAuthRequest
{


    public function prepareForValidation(): void
    {
        $this->prepareContactType();
        $this->PrepareAction();
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



    /**
     * Determine if the user is authorized to make this request.
     */

}
