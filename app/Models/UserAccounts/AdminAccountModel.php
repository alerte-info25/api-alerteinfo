<?php

namespace App\Models\UserAccounts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class AdminAccountModel extends Authenticatable implements  JWTSubject
{
    use HasFactory, Notifiable;
    protected $fillable = [
        'account_code_unique',
        'role_code_unique',
        'first_name',
        'last_name',
        'phone',
        'email',
        'password',
        'photo',
        'two_factor_secret',
        'two_factor_code_sent_at',
        'two_factor_enabled',
        'email_verified_at',
        'device_browser',
        'device_type',
        'device_os',
        'last_login_at',
        'last_logout_at',
        'connected',
        'status',
        'slug',
        'created_at',
        'updated_at',
    ];


    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute()
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }
        return null;
    }


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


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function role()
    {
        return $this->belongsTo(UserRoleModel::class, 'role_code_unique', 'role_code_unique');
    }
}
