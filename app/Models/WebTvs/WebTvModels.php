<?php

namespace App\Models\WebTvs;

use Illuminate\Database\Eloquent\Model;

class WebTvModels extends Model
{
    protected $table = "web_tv_models";
    protected $fillable = [
        "title",
        "description",
        "video_keys",
        "published_at",
        "slug",
    ];
}
