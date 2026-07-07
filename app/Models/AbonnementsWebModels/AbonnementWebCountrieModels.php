<?php

namespace App\Models\AbonnementsWebModels;

use Illuminate\Database\Eloquent\Model;
use App\Models\Redactions\CountriesModels;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AbonnementWebCountrieModels extends Model
{
    use HasFactory;
    protected $fillable = [
        'abonnement_web_code',
        'country_id',
    ];
    public function abonnement()
    {
        return $this->belongsTo(AbonnementWebModels::class, 'abonnement_web_code', 'abonnement_web_code');
    }
    public function country()
    {
        return $this->belongsTo(CountriesModels::class, 'country_id');
    }

}
