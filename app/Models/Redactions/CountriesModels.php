<?php

namespace App\Models\Redactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\AbonnementsWebModels\AbonnementWebCountrieModels;

class CountriesModels extends Model
{
    use HasFactory;

    protected $fileable = [
        'pays',
        'flag',
        'phone_code',
        'currency',
        'iso_code',
        'slug'
    ];

    public function abonnement()
    {
        return $this->hasMany(AbonnementWebCountrieModels::class, 'country_id');
    }
}


