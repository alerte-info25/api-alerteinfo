<?php

namespace App\Models\PartnersNews;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiPartnerNewsLogModel extends Model
{
    use HasFactory;

    protected $table = 'api_partner_news_log_models';

    protected $fillable = [
        'partner_code_unique',
        'endpoint',
        'http_method',
        'response_status',
        'ip_address',
        'user_agent',
        'query_params',
        'error_message',
        'requested_at',
    ];

    protected $casts = [
        'query_params' => 'array',
        'requested_at' => 'datetime',
    ];



    // Relation : un log appartient à un partenaire (optionnel)
    public function partner()
    {
        return $this->belongsTo(ApiPartnerNewsModel::class, 'partner_code_unique', 'partner_code_unique');
    }
}
