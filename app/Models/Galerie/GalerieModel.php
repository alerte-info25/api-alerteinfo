<?php

namespace App\Models\Galerie;

use Illuminate\Database\Eloquent\Model;

class GalerieModel extends Model
{
    protected $fillable = [
        'galerie_code_unique',
        'title',
        'media_path',
        'slug',
    ];

    protected $appends = ['media_path_url'];

    public function getMediaPathUrlAttribute()
    {
        if (isset($this->media_path)) {
            return asset('storage/' . $this->media_path);
        }
        return null;
    }
}
