<?php
namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class CheckUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'contact' => [
                'bail',
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        // It's a valid email address - further validate via Laravel's email rule
                        $validator = Validator(['email' => $value], [
                            'email' => 'email:rfc,dns',
                        ]);

                        if ($validator->fails()) {
                            $fail(__('auth::messages.invalid_contact', ['attribute' => $attribute]));
                        }
                        return;
                    }

                    // Add additional contact validations (e.g., phone) here if needed
                    $validator = validator([["phone" => $value], ['phone' => 'phone:mobile']]);
                    if ($validator->fails()) {
                        $fail('The ' . $attribute . ' must be a valid email address or mobile phone number.');
                    }

                    return;
                },

            ],
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
