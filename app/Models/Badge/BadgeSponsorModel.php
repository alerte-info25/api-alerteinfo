<?php

namespace App\Models\Badge;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BadgeSponsorModel extends Model
{
    use SoftDeletes;
    protected $table = 'badge_sponsor_models';

    protected $fillable = [
        'uuid',
        'badge_code',
        'sponsor_name',
        'sponsor_logo_path',
        'sponsor_logo_b64',
    ];

    protected $appends = [
        'sponsor_logo_path_url',
    ];

    public function getSponsorLogoPathUrlAttribute()
    {
        return $this->sponsor_logo_path ? asset('storage/' . $this->sponsor_logo_path) : null;
    }

    public function badge()
    {
        return $this->belongsTo(BadgeModel::class, 'badge_code', 'badge_code');
    }

    public function scByBadgeCode($badge_code)
    {
        return $this->where('badge_code', $badge_code)->get();
    }
}
