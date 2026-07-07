<?php

namespace App\Models\UsersAccountManager;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use App\Models\UsersRoleManager\RoleManagerModels;
use App\Models\UsersRoleManager\UserRoleManagerModels;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminAccountManagerModels extends Authenticatable implements JWTSubject
{

    use HasFactory, Notifiable;
    protected $fillable = [
        'account_code_unique',
        'role_id',
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
        if (isset($this->photo)) {
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

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }



    // Relation avec UserRoleManagerModels
    public function role()
    {
        return $this->belongsTo(RoleManagerModels::class, 'role_id', 'id');
    }




}
