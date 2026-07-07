<?php

namespace App\Models\Redactions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashesModels extends Model
{
    use HasFactory;

    protected $fileable = [
        'media_url',
        'rubrique_libelle',
        'pays_id',
        'contenus',
        'author',
        'status',
        'slug',
    ];

    public function pays()
    {
        return $this->belongsTo(CountriesModels::class, 'pays_id');
    }
}
