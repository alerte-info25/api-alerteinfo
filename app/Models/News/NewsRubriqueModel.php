<?php

namespace App\Models\News;

use Illuminate\Database\Eloquent\Model;

class NewsRubriqueModel extends Model
{
    protected $table = 'news_rubrique_models';
    protected $fillable = [
        'rubrique_code_unique',
        'rubrique_name',
        'slug',
    ];

    public function news()
    {
        return $this->hasMany(NewsModel::class, 'rubrique_code_unique', 'rubrique_code_unique');
    }
}
