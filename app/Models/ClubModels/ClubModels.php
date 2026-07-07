<?php

namespace App\Models\ClubModels;

use App\Models\LicenceModels\LicenceModel;
use Illuminate\Database\Eloquent\Model;

class ClubModels extends Model
{
    protected $table = 'club_models';
    protected $fillable = [
        'club_code_unique',
        'categorie_code_unique',
        'club_name',
        'club_abreviation',
        'club_code',
        'code_validity',
        'club_logo',
        'slug'
    ];


    public function licences()
    {
        return $this->hasMany(LicenceModel::class, 'organisation_code_unique', 'club_code_unique')
            ->where('organisation_type', 'club');
    }
}
