<?php

namespace App\Models\LicenceModels;

use App\Models\ClubModels\ClubModels;
use App\Models\LigueModels\LigueModels;
use Illuminate\Database\Eloquent\Model;
use App\Models\TrancheAges\TrancheAgeModel;
use App\Models\FonctionModels\FonctionModel;
use App\Models\FederationModels\FederationModels;
use App\Models\CategorieGeneraleModels\CategorieGeneraleModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class LicenceModel extends Model
{
    use SoftDeletes;
    protected $table = 'licence_models';

    protected $fillable = [
        'licence_code_unique', // code unique de la licence // LCE25-0913103540-HGDO
        'operation_code', // code unique de l'operation // OP25-0913103540-HGDOUY
        'categorie_code_unique', // code unique de la catégorie
        'fonction_code_unique', // code unique de la fonction
        'tranche_code_unique', // code unique de la tranche
        'organisation_code_unique', // code unique de l'organisation

        'num_licence', // numéro de licence // FIA25-000001


        'first_name', // prénom
        'last_name', // nom
        'genre', // genre

        'date_naissance', // date de naissance
        'lieu_naissance', // lieu de naissance

        'email', // email
        'telephone', // numéro de téléphone


        'nationalite', // nationalité
        'groupe_sanguin', // groupe sanguin

        'demandeur_full_name', // nom complet du demandeur
        'medecin_full_name', // nom complet du médecin

        'info_state', // état de l'information [pending,finished]

        'professional_state', // état de l'information professionnelle [pending,finished]
        'documents_state', // état des documents requis [pending,finished]
        'payment', // paiement [pending,paid,cancelled]

        'operation_type', // type d'operation [new,renewal]
        'organisation_type', // valeurs: 'club', 'ligue', 'federation'
        'request_date', // date de la demande

        'slug', // slug

    ];



    public function categorie()
    {
        return $this->belongsTo(CategorieGeneraleModel::class, 'categorie_code_unique', 'categorie_code_unique');
    }

    public function fonction()
    {
        return $this->belongsTo(FonctionModel::class, 'fonction_code_unique', 'fonction_code_unique');
    }

    public function tranche()
    {
        return $this->belongsTo(TrancheAgeModel::class, 'tranche_code_unique', 'tranche_code_unique');
    }

    public function documents()
    {
        return $this->hasMany(LicenceDocumentsModel::class, 'licence_code_unique', 'licence_code_unique');
    }

    public function club()
    {
        return $this->belongsTo(ClubModels::class, 'organisation_code_unique', 'club_code_unique');
    }

    public function ligue()
    {
        return $this->belongsTo(LigueModels::class, 'organisation_code_unique', 'ligue_code_unique');
    }

    public function federation()
    {
        return $this->belongsTo(FederationModels::class, 'organisation_code_unique', 'federation_code_unique');
    }

    public function getOrganisationAttribute()
    {
        $code = $this->organisation_code_unique;
        return match ($this->organisation_type) {
            'club' => ClubModels::where('club_code_unique', $code)
            ->select('club_code_unique as organisation_code_unique',
                //'code_validity',
                'club_name as organisation_name',
                'club_abreviation as organisation_abreviation'
            )
            //->where('code_validity', '>=', now())
            ->first(),
            'ligue' => LigueModels::where('ligue_code_unique', $code)
            ->select('ligue_code_unique as organisation_code_unique',
                //'code_validity',
                'ligue_name as organisation_name',
                'ligue_abreviation as organisation_abreviation'
        ),
            //->where('code_validity', '>=', now())->first(),
            'federation' => FederationModels::where('federation_code_unique', $code)
            ->select('federation_code_unique as organisation_code_unique',
                //'code_validity',
                'federation_name as organisation_name',
                'federation_abreviation as organisation_abreviation'
            )
            //->where('code_validity', '>=', now())
            ->first(),
            default => null,
        };
    }



    /**
     * Summary of getOrganisationCodeUnique
     * @param mixed $organisationCodeUnique
     * @return array {data:ClubModels|LigueModels|FederationModels|null, type:string}|null
     */
    public function getOrganisationCodeUnique($organisationCodeUnique)
    {
        $isClub = ClubModels::where('club_code', $organisationCodeUnique)
        ->select('club_code_unique as organisation_code_unique','code_validity')
        ->where('code_validity', '>=', now())
        ->first();


        $isLigue = LigueModels::where('ligue_code', $organisationCodeUnique)
        ->select('ligue_code_unique as organisation_code_unique','code_validity')
        ->where('code_validity', '>=', now())->first();


        $isFederation = FederationModels::where('federation_code', $organisationCodeUnique)
        ->select('federation_code_unique as organisation_code_unique','code_validity')
        ->where('code_validity', '>=', now())
        ->first();

        if ($isClub) {
            return [
                'data' => $isClub,
                'type' => 'club'
            ];
        } elseif ($isLigue) {
            return [
                'data' => $isLigue,
                'type' => 'ligue'
            ];
        } elseif ($isFederation) {
            return [
                'data' => $isFederation,
                'type' => 'federation'
            ];
        }
        return null;
    }

    /**
     * Check if the organisation code is valid
     *
     * @param string $organisationCodeUnique
     * @return array {state:bool,type:string}
     */
    public function checkOrganisationCodeValidity($organisationCodeUnique,$categoryCodeUnique): array
    {
        $isClub = ClubModels::where('club_code', $organisationCodeUnique)
        ->select('club_code_unique as organisation_code_unique','code_validity')
        ->where('categorie_code_unique', $categoryCodeUnique)
        ->where('code_validity', '>=', now())
        ->first();


        $isLigue = LigueModels::where('ligue_code', $organisationCodeUnique)
        ->select('ligue_code_unique as organisation_code_unique','code_validity')
        ->where('categorie_code_unique', $categoryCodeUnique)
        ->where('code_validity', '>=', now())->first();


        $isFederation = FederationModels::where('federation_code', $organisationCodeUnique)
        ->select('federation_code_unique as organisation_code_unique','code_validity')
        ->where('categorie_code_unique', $categoryCodeUnique)
        ->where('code_validity', '>=', now())
        ->first();

        if ($isClub) {
            return [
                'state' => true,
                'type' => 'club',
                'message' => 'Le code fourni est valide'
            ];
        } elseif ($isLigue) {
            return [
                'state' => true,
                'type' => 'ligue',
                'message' => 'Le code fourni est valide'
            ];
        } elseif ($isFederation) {
            return [
                'state' => true,
                'type' => 'federation',
                'message' => 'Le code fourni est valide'
            ];
        }
        return [
            'state' => false,
            'type' => null,
            'message' => 'Le code fourni est invalide ou expiré ou non trouvé'
        ];
    }

    public function updateOperationCodeAttribute($slug,$operationCode)
    {
        $this->where('slug', $slug)->update(['operation_code' => $operationCode]);
    }

    public function updateOperationTypeAttribute($slug,$operationType)
    {
        $this->where('slug', $slug)->update(['operation_type' => $operationType]);
    }

    public function updateLicencePaymentAttribute($slug)
    {
        $licencePayment = $this->where('slug', $slug)->first();

        if($licencePayment->operation_type == 'renewal' && $licencePayment->payment == 'paid'){
            $this->where('slug', $slug)->update(['payment' => 'pending']);
        }
    }
}
