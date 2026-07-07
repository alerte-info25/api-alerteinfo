<?php

namespace App\Models\Banners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannerModels extends Model
{
    use HasFactory;

    protected $fileable = [
        'libelle',
        'banner_image',
        'img_url',
        'status',
        'plateform',
        'slug'
    ];
}
