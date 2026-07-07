<?php

namespace App\Models\UsersAccounts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedactionsModels extends Model
{
    use HasFactory;


    protected $fileable = [
        'user_id',
        'role_id',
        'first_name',
        'last_name',
        'photo',
        'service',
        'fonction',
        'matricule',
        'slug'
    ];
}
