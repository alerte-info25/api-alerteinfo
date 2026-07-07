<?php

namespace App\Models\Redactions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepecheModels extends Model
{
    use HasFactory;

    protected $fileable = [
        'rubrique_libelle',
        'pays_id',
        'titre',
        'lead',
        'author',
        'media_url',
        'contenus',
        'legende',
        'like_counter',
        'dislike_counter',
        'counter',
        'status',
        'slug',
    ];
}
