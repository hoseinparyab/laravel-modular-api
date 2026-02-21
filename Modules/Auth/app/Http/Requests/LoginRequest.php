<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;
use Modules\Auth\Enums\ContactType;

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
                    $this->validatePasswordLogin($validator, $validatedData);
                } else {
                    $this->validateTokenLogin($validator, $validatedData);
                }
            },
        ];
    }
    public function validatePasswordLogin(Validator $validator, array $validatedData)
    {

        if (empty($validatedData['password'])) {
            $validator->errors()->add('password', 'The password field is required.');
            return;
        }
        $credentials = [
            //email or phone
            $this -> contactType->value => $validatedData['contact'],
            'password' => $validatedData['password'],
        ];
        if (!Auth::once($credentials)){
            $validator ->errors()->add('content', 'The password field is required.');
        }
    }

    public function validateTokenLogin(Validator $validator, array $validatedData)
    {
        if (empty($validatedData['token'])) {
            $validator->errors()->add('token', 'The token field is required.');
            return;
        }
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
