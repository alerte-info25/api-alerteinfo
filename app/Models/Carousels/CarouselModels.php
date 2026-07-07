<?php

namespace App\Models\Carousels;

use Illuminate\Database\Eloquent\Model;

class CarouselModels extends Model
{
    protected $table = "carousel_models";

    protected $fillable = [
        "uuid",
        "title",
        "media_path",
        "active",
        "created_at",
        "updated_at",
    ];

    protected $appends = [
        "media_path_url",
    ];

    public function getMediaPathUrlAttribute()
    {
        return $this->media_path ? asset("storage/" . $this->media_path) : null;
    }

    public function scopeActive($query)
    {
        return $query->where("active", true);
    }
}
