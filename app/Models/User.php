<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use App\Models\User;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $appends = [
        'avatar_url',
        'id_card_url',
        'drive_licence_url'
    ];
    protected $fillable = [
        'name',
        'email',
        'password',
        'username', 
        'email_verified_at', 
        'number_phone', 
        'role', 
        'id_card', 
        'drive_licence',
        'address',
        'avatar'
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
            'password' => 'hashed',
        ];
    }

    public function getIdCardUrlAttribute()
    {
        return $this->id_card ? asset('storage/'.$this->id_card) : null;
    }

    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? asset('storage/'.$this->avatar) : null;
    }

    public function getDriveLicenceUrlAttribute()
    {
        return $this->drive_licence ? asset('storage/'.$this->drive_licence) : null;
    }
    
    public function otps()
    {
        return $this->hasMany(Otp::class);
    }
}
