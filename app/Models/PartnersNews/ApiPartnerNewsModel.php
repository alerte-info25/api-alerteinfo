<?php

namespace App\Models\PartnersNews;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiPartnerNewsModel extends Model
{
    use HasFactory;

    protected $table = 'api_partner_news_models';

    protected $fillable = [
        'partner_code_unique',
        'name', // ex: "PresseInfo Ltd"
        'email', // contact
        'api_token', // token unique
        'rate_limit', // requêtes/minute
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $dates = [
        'last_used_at',
    ];


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
