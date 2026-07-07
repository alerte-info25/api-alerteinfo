<?php

namespace App\Models\LigueModels;

use App\Models\LicenceModels\LicenceModel;
use Illuminate\Database\Eloquent\Model;

class LigueModels extends Model
{
    protected $table = 'ligue_models';
    protected $fillable = [
        'ligue_code_unique',
        'categorie_code_unique',
        'ligue_name',
        'ligue_abreviation',
        'ligue_code',
        'code_validity',
        'ligue_logo',
        'slug'
    ];

    public function licences()
    {
        return $this->hasMany(LicenceModel::class, 'organisation_code_unique', 'ligue_code_unique')
            ->where('organisation_type', 'ligue');
    }
}
