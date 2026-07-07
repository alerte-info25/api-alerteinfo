<?php

namespace App\Models\DocumentsRequisModels;

use Illuminate\Database\Eloquent\Model;

class DocumentsRequisModel extends Model
{
    protected $table = 'documents_requis_models';
    protected $fillable = [
        'document_code_unique',
        'document_name',
        'slug',
    ];
}
