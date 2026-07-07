<?php

namespace App\Models\Galeries;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalerieModels extends Model
{
    use HasFactory;

    protected $fileable = [
        'galerie_libelle',
        'galerie_description',
        'galerie_img_url',
        'slug'
    ];
}
