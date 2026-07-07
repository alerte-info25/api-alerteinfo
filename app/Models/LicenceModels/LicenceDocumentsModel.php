<?php

namespace App\Models\LicenceModels;

use Illuminate\Database\Eloquent\Model;
use App\Models\DocumentsRequisModels\DocumentsRequisModel;

class LicenceDocumentsModel extends Model
{
    protected $table = 'licence_documents_models';

    protected $fillable = [
        'licence_code_unique',
        'document_code_unique',
        'document_path',
        'type',
        'slug',
    ];

    public $appends = ['document_path_url'];

    public function getDocumentPathUrlAttribute()
    {
        if($this->document_path){
            return asset('storage/' . $this->document_path);
        }
        return null;
    }

    public function licence()
    {
        return $this->belongsTo(LicenceModel::class, 'licence_code_unique', 'licence_code_unique');
    }

    public function document()
    {
        return $this->belongsTo(DocumentsRequisModel::class, 'document_code_unique', 'document_code_unique');
    }
}
