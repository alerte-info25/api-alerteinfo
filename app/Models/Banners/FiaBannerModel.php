<?php

namespace App\Models\Banners;

use Illuminate\Database\Eloquent\Model;

class FiaBannerModel extends Model
{
    protected $table = "fia_banner_models";

    protected $fillable = [
        "uuid",
        "media_path",
        "active",
        "web_site_url",
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
