<?php

namespace App\Models\AbonnementsMobileModels;

use Illuminate\Database\Eloquent\Model;
use App\Models\Redactions\CountriesModels;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\AbonnesMobileModels\AbonnesMobileModels;

class AbonnementsMobileModels extends Model
{
    use HasFactory;
    protected $guarded = [
    ];

    // Relation avec le modèle Forfait
    public function forfait()
    {
        return $this->belongsTo(ForfaitsAbonnementsMobileModels::class, 'abonne_forfait_id');
    }

    // Relation avec le modèle Abonne (l'abonné)
    public function abonne()
    {
        return $this->belongsTo(AbonnesMobileModels::class, 'abonne_id');
    }

    // Relation avec le modèle Countries pour les pays
    public function countries()
    {
        return $this->belongsTo(CountriesModels::class, 'abonnement_country', 'abonnement_id', 'country_id');
    }
}
