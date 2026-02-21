<?php
namespace Modules\User\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Auth\Enums\ContactType;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable ,HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /**
     * Allow accessing `phone` attribute mapped to `phone_number` column.
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['phone_number'] ?? null;
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone_number'] = $value;
    }

    public function verifiedContact(ContactType $contactType): void
    {
        $dataVerification= [];
        if($contactType === ContactType::EMAIL &&  is_null($this->email_verified_at)){
            $dataVerification['email_verified_at'] = now();
        }else{
            if($contactType === ContactType::PHONE &&  is_null($this->phone_verified_at)){
                $dataVerification['phone_verified_at'] = now();
            }
        }
        $this->forceFill($dataVerification)->save();
    }
}
