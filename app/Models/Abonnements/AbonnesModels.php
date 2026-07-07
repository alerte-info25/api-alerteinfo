<?php

namespace App\Models\Abonnements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbonnesModels extends Model
{
    use HasFactory;

    protected $fileable = [
        'user_id',
        'abonne_name',
        'abonne_phone_number',
        'abonne_email',
        'slug'
    ];
}
