<?php

namespace App\Models\AlerteInfoWebSite;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OurBlogsModels extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'lead',
        'contents',
        'media_path',
        'slug',
        'created_at',
        'updated_at',
    ];
}
