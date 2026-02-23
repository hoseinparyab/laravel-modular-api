<?

namespace Modules\Auth\Traits;

use Modules\Auth\Enums\ContactType;

trait HasContactValidation
{
    public function getContactValidationRules(): array
    {
        if ($this->contactType === ContactType::EMAIL) {
            return $this->getEmailValidationRules();
        }
        if ($this->contactType === ContactType::PHONE) {
            return $this->getPhoneValidationRules();
        }
        return [];
    }
    public function getEmailValidationRules(): array
    {
     $rules = [ 'email' => ['email::rfc','dns'],];
        if ($this->action->isContactNeededToBeUnique()) {
            $rules[]= [
                'email',
                'unique:users,email',
            ];
        }
        if ($this->action->isContactNeededToExist()) {
            $rules[]= [
                'email',
                'exists:users,email',
            ];
        }
        return $rules;
    }
    public function getPhoneValidationRules(): array
    {
        $rules = ['phone:mobile'];
        if ($this->action->isContactNeededToBeUnique()) {
            $rules[]= [
                'phone',
                'unique:users,phone_number',
            ];
        }
        if ($this->action->isContactNeededToExist()) {
            $rules[]= [
                'phone',
                'exists:users,phone_number',
            ];
        }
        return $rules;
    }
}
