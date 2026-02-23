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

class SendVerificationRequest extends BaseAuthRequest
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



    /**
     * Determine if the user is authorized to make this request.
     */
}
