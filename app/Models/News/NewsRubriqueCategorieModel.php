<?php

namespace App\Models\News;

use Illuminate\Database\Eloquent\Model;

class NewsRubriqueCategorieModel extends Model
{
    protected $table = 'news_rubrique_categorie_models';
    protected $fillable = [
        'rubrique_categorie_code_unique',
        'rubrique_categorie_name',
        'slug',
    ];

    public function news()
    {
        return $this->hasMany(NewsModel::class, 'rubrique_categorie_code_unique', 'rubrique_categorie_code_unique');
    }
}
