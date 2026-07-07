<?php

namespace App\Models\Badge;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BadgeDataModel extends Model
{
    use SoftDeletes;
    protected $table = 'badge_data_models';

    protected $fillable = [
        'uuid',
        'badge_code',
        'first_name',
        'last_name',
        'function',
        'owner_photo_path',
        'owner_photo_b64',

        'owner_country_name',
        'owner_country_code',
        'owner_country_flag',

        'zone_access_1',
        'zone_access_2',
        'zone_access_3',
        'zone_access_4',
        'zone_access_5',
        'zone_access_6',
        'zone_access_7',
        'zone_access_8',
        'zone_access_9',
        'zone_access_10',

        'zone_access_11',
        'zone_access_12',
        'zone_access_13',
        'zone_access_14',
        'zone_access_15',
        'zone_access_16'
    ];

    protected $appends = [
        'owner_photo_path_url',
    ];

    public function getOwnerPhotoPathUrlAttribute()
    {
        return $this->owner_photo_path ? asset('storage/' . $this->owner_photo_path) : null;
    }
    
    public function badge()
    {
        return $this->belongsTo(BadgeModel::class, 'badge_code', 'badge_code');
    }
}
