<?php

namespace App\Models\Quoideneufs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaModels extends Model
{
    use HasFactory;


    protected $fileable = [
        'video_url',
        'audio_url',
        'description',
        'author',
        'type_media',
        'slug'
    ];
}
