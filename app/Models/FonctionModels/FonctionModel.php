<?php

namespace App\Models\FonctionModels;

use Illuminate\Database\Eloquent\Model;
use App\Models\CategorieGeneraleModels\CategorieGeneraleModel;

class FonctionModel extends Model
{
    protected $table = 'fonction_models';
    protected $fillable = [
        'fonction_code_unique',
        'category_code_unique',
        'fonction_name',
        'slug'
    ];

    public function category()
    {
        return $this->belongsTo(CategorieGeneraleModel::class, 'category_code_unique', 'categorie_code_unique');
    }
}
