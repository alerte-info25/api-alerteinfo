<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FooterSetting extends Model
{
    use HasFactory;
    protected $table = 'footer_settings';

    protected $fillable = [
        'description_1',
        'description_2',
        'description_3',
        'phones',
        'email_direction',
        'email_redaction',
        'address_abidjan_city',
        'address_abidjan_detail',
        'address_ouaga_city',
        'address_ouaga_detail',
        'facebook_url',
        'youtube_url',
    ];

    /**
     * Cast automatique du JSON phones en tableau PHP
     */
    protected $casts = [
        'phones' => 'array',
    ];

    /**
     * Récupère l'unique ligne de configuration (singleton).
     * La crée si elle n'existe pas encore.
     */
    public static function getInstance(): static
    {
        return static::firstOrCreate(['id' => 1]);
    }
}

