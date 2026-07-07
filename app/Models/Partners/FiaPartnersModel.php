<?php

namespace App\Models\Partners;

use Illuminate\Database\Eloquent\Model;

class FiaPartnersModel extends Model
{
    protected $table = "fia_partners_models";

    protected $fillable = [
        "uuid",
        "media_path",
        "web_site_url",
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
