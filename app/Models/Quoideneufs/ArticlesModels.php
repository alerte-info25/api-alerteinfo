<?php

namespace App\Models\Quoideneufs;

use Illuminate\Database\Eloquent\Model;
use App\Models\Redactions\CountriesModels;
use App\Models\Quoideneufs\GenreJournalistiqueModels;
use App\Models\Quoideneufs\RubriquesQuoideneufModels;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ArticlesModels extends Model
{
    use HasFactory;

    protected $fileable = [
        'rubrique_id',
        'genre_id',
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

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function rubrique()
    {
        return $this->belongsTo(RubriquesQuoideneufModels::class, 'rubrique_id', 'id');
    }

    public function genre()
    {
        return $this->belongsTo(GenreJournalistiqueModels::class, 'genre_id', 'id');
    }

    public function country()
    {
        return $this->belongsTo(CountriesModels::class, 'pays_id', 'id');
    }
}
