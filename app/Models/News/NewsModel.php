<?php

namespace App\Models\News;

use Illuminate\Database\Eloquent\Model;
use App\Models\News\NewsRubriqueCategorieModel;

class NewsModel extends Model
{
    protected $table = 'news_models';
    protected $fillable = [
        'news_code_unique',
        'rubrique_code_unique',
        'rubrique_category_code_unique',
        'news_title',
        'news_lead',
        'news_content',
        'media_path',
        'media_legend',
        'news_author',
        'news_views',
        'published',
        'news_slug',
    ];

    
    public function rubrique()
    {
        return $this->belongsTo(NewsRubriqueModel::class, 'rubrique_code_unique', 'rubrique_code_unique');
    }

    public function rubriqueCategory()
    {
        return $this->belongsTo(NewsRubriqueCategorieModel::class, 'rubrique_category_code_unique', 'rubrique_categorie_code_unique');
    }
}
