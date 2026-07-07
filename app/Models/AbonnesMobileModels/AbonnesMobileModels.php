<?php

namespace App\Models\AbonnesMobileModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbonnesMobileModels extends Model
{
    use HasFactory;

    protected $fileable = [
        'user_id',
        'abonne_fname',
        'abonne_lname',
        'abonne_email',
        'abonne_phone_number',
        'slug'
    ];
}
