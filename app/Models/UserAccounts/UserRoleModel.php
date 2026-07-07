<?php

namespace App\Models\UserAccounts;

use Illuminate\Database\Eloquent\Model;

class UserRoleModel extends Model
{
    protected $fillable = [
        'role_code_unique',
        'role_name',
        'slug',
    ];

    
}
