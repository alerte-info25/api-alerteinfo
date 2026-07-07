<?php

namespace App\Models\AlerteInfoWebSite;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OurServicesModels extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'contents',
        'media_path',
        'slug'
    ];
}
