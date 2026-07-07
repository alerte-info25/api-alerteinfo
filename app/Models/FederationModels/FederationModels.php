<?php

namespace App\Models\FederationModels;

use App\Models\LicenceModels\LicenceModel;
use Illuminate\Database\Eloquent\Model;

class FederationModels extends Model
{
    protected $table = 'federation_models';
    protected $fillable = [
        'federation_code_unique',
        'categorie_code_unique',
        'federation_name',
        'federation_abreviation',
        'federation_code',
        'code_validity',
        'federation_logo',
        'slug'
    ];


    public function licences()
    {
        return $this->hasMany(LicenceModel::class, 'organisation_code_unique', 'federation_code_unique')
            ->where('organisation_type', 'federation');
    }
}
