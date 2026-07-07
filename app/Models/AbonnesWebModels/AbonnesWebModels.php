<?php

namespace App\Models\AbonnesWebModels;

use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\AbonnesWebModels\CategoriesAbonnesWebModels;

class AbonnesWebModels extends Authenticatable implements JWTSubject
{
    use HasFactory , Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     *
     */

    protected $fillable = [
        'account_code_unique',
        'category_code',
        'full_name',
        'phone',
        'email',
        'password',
        'two_factor_secret',
        'two_factor_code_sent_at',
        'email_verified_at',
        'device_browser',
        'device_type',
        'device_os',
        'last_login_at',
        'last_logout_at',
        'status',
        'slug',
        'created_at',
        'updated_at',
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

    public function categories()
    {
        return $this->belongsTo(CategoriesAbonnesWebModels::class, 'category_code', 'category_code');
    }
}
