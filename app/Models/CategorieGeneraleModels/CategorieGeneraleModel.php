<?php

namespace App\Models\CategorieGeneraleModels;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\DocumentsRequisModels\DocumentsRequisModel;

class CategorieGeneraleModel extends Model
{
    protected $table = 'categorie_generale_models';
    protected $fillable = [
        'categorie_code_unique',
        'categorie_name',
        'categorie_montant',
        'type',
        'document_code_unique',
        'slug'
    ];

    protected $casts = [
        'document_code_unique' => 'array',
    ];

    public function getDocumentsRequisAttribute()
    {
        return empty($this->document_code_unique)
            ? collect()
            : DocumentsRequisModel::whereIn('document_code_unique', $this->document_code_unique)->get();
    }
}
