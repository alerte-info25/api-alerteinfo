<?php

namespace App\Models\UsersRoleManager;

use Illuminate\Database\Eloquent\Model;
use App\Models\UsersAccountManager\AdminAccountManagerModels;

class RoleManagerModels extends Model
{
    protected $fillable = ['role', 'slug'];

}
