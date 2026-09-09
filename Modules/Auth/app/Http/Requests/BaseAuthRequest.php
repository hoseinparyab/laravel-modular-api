<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Enums\ContactType;
use Modules\Auth\Enums\VerificationActionType;
use Modules\Auth\Http\Requests\Traits\HasUserAuthentication;
use Modules\Auth\Http\Requests\Traits\HasVerificationValidation;
use Modules\Auth\Traits\HasContactValidation;

class BaseAuthRequest extends FormRequest
{
    use HasContactValidation, HasUserAuthentication, HasVerificationValidation;

    public ContactType $contactType;
    public ?VerificationActionType $action = null;

    public function prepareContactType(): void
    {
        $this->contactType = ContactType::detectContactTypes($this->contact ?? '');
    }

    public function PrepareAction(): void
    {
        $this->action = VerificationActionType::tryFrom($this->input('action') ?? '');
    }

    public function authorize(): bool
    {
        return true;
    }
}
