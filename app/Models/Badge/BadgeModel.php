<?php

namespace App\Models\Badge;

use App\Models\Badge\BadgeSponsorModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BadgeModel extends Model
{
    use SoftDeletes;
    protected $table = 'badge_models';

    protected $fillable = [
        'uuid',
        'badge_code',
        'event_name',
        'event_date_start',
        'event_date_end',
        'right_logo_path',
        'right_logo_b64',
        'left_logo_path',
        'left_logo_b64',
        'description',
    ];

    protected $casts = [
        'event_date_start' => 'date',
        'event_date_end' => 'date',
    ];

    protected $appends = [
        'right_logo_path_url',
        'left_logo_path_url',
    ];

    
    public function getRightLogoPathUrlAttribute()
    {
        return $this->right_logo_path ? asset('storage/' . $this->right_logo_path) : null;
    }

    public function getLeftLogoPathUrlAttribute()
    {
        return $this->left_logo_path ? asset('storage/' . $this->left_logo_path) : null;
    }

    public function badgeData()
    {
        return $this->belongsTo(BadgeDataModel::class, 'badge_code', 'badge_code');
    }

    public function badgeSponsor()
    {
        return $this->hasMany(BadgeSponsorModel::class, 'badge_code', 'badge_code');
    }

    public function scopeBadgeAvailable()
    {
        return $this->where('event_date_end', '>=', today());
    }
}

