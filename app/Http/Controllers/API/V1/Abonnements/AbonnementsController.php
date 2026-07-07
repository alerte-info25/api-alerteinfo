<?php

namespace App\Http\Controllers\API\V1\Abonnements;

use DateTime;
use DateInterval;
use Illuminate\Http\Request;
use App\Services\CodeGenerator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Abonnements\AbonnementsModels;
use App\Services\AbonnementMobile\AbonnementMobileService;
use App\Models\AbonnementsMobileModels\AbonnementsMobileModels;

class AbonnementsController extends Controller
{

    
    public function __construct(
        private AbonnementMobileService $abonnementMobileService,
    ){}

    public function ctrl_getAbonnementMobile(Request $request)
    {
        return $this->abonnementMobileService->srv_getAbonnementMobile($request);
    }

    public function ctrl_getAbonnementMobileFormData()
    {
        return $this->abonnementMobileService->srv_getAbonnementMobileFormData();
    }

    public function ctrl_createLocalAbonnementMobile(Request $request)
    {
        if(empty($request->abonne_id)):
            return response()->json(
                [
                    'code' => 302,
                    'status' => 'erreur',
                    'message' => "Erreur! Le l'identifiant de l'abonné est obligatoire"
                ]
            );
        endif;

        if(empty($request->abonne_forfait_id)):
            return response()->json(
                [
                    'code' => 302,
                    'status' => 'erreur',
                    'message' => "Erreur! Le forfait de l'abonné est obligatoire"
                ]
            );
        endif;
        if(empty($request->pays_id)):
            return response()->json(
                [
                    'code' => 302,
                    'status' => 'erreur',
                    'message' => "Erreur! Aucun pays sélectionné"
                ]
            );
        endif;

        return $this->abonnementMobileService->srv_createLocalAbonnementMobile($request);
    }

    public function ctrl_updateAbonnementMobileValidityDate(Request $request)
    {
        // check abonnement_code
        if(empty($request->abonnement_code)){
            return response()->json([
                'code' => 302,
                'status' => 'erreur',
                'message' => 'Le code d\'abonnement est obligatoire'
            ]);
        }
        // check validation_value
        if(empty($request->validation_value)){
            return response()->json([
                'code' => 302,
                'status' => 'erreur',
                'message' => 'La valeur de validation est obligatoire'
            ]);
        }
        return $this->abonnementMobileService->srv_updateAbonnementMobileValidityDate($request);
    }

    public function ctrl_deleteAbonnementMobile(Request $request)
    {
        return $this->abonnementMobileService->srv_deleteAbonnementMobile($request);
    }

    
    public function index()
    {
        try {
            return  DB::table('abonnements_mobile_models')
            ->join('abonnes_mobile_models', 'abonnements_mobile_models.abonne_id', '=', 'abonnes_mobile_models.id')
            ->join('forfaits_abonnements_mobile_models', 'abonnements_mobile_models.abonne_forfait_id','=', 'forfaits_abonnements_mobile_models.id')
            
            ->leftJoin('countries_models', function($join) {
                $join->on(DB::raw("FIND_IN_SET(countries_models.id, abonnements_mobile_models.abonne_country_id)"), '>', DB::raw('0'));
            })

            ->select(
                'abonnes_mobile_models.abonne_phone_number',
                'abonnes_mobile_models.abonne_fname',
                'abonnes_mobile_models.abonne_lname',
                'abonnes_mobile_models.abonne_email',
                'forfaits_abonnements_mobile_models.forfait_libelle',
                'forfaits_abonnements_mobile_models.montant_forfait',
                'abonnements_mobile_models.*',
                DB::raw('GROUP_CONCAT(countries_models.pays SEPARATOR ", ") as pays')
            )
            ->groupBy('abonnements_mobile_models.id')
            ->orderBy('abonnements_mobile_models.id', 'desc')
            ->limit(100)
            ->get();


        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }



    public function create_local_abonnements_mobile(Request $request)
    {
        try {

            if(empty($request->abonne_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le l'identifiant de l'abonné est obligatoire"
                    ]
                );
            endif;

            if(empty($request->abonne_forfait_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le forfait de l'abonné est obligatoire"
                    ]
                );
            endif;
            if(empty($request->pays_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Aucun pays sélectionné"
                    ]
                );
            endif;


            $forfait_info = DB::table('forfaits_abonnements_mobile_models')->where('id',$request->abonne_forfait_id)->first();

            $sizeOfCountry = sizeof($request->pays_id);
            $dateline = 'P'.$forfait_info->duree_forfait.'D';

            $date_fin = new DateTime();
            $date_fin->add(new DateInterval($dateline));
            $date_fin->format('Y-m-d H:i:s');


            $add_abonnement = new AbonnementsMobileModels();
            $add_abonnement->abonnement_code = CodeGenerator::generateAbonnementCodeUnique();
            $add_abonnement->abonne_id = $request->abonne_id;
            $add_abonnement->abonne_forfait_id = $request->abonne_forfait_id;
            $add_abonnement->abonne_country_id = implode(',', $request->pays_id);
            $add_abonnement->montant_abonnements = (int) $sizeOfCountry * $forfait_info->montant_forfait;
            $add_abonnement->date_debut = date('Y-m-d H:i:s', strtotime((now())));
            $add_abonnement->date_fin = $date_fin;
            $add_abonnement->payments = 1;
            $add_abonnement->slug = CodeGenerator::generateSlugCode();

            if($add_abonnement->save()):

                DB::table('abonnes_mobile_models')->where('id', $request->abonne_id)->update(['status_abonnement' => 1]);
                DB::table('abonnements_mobile_models')->where('id', $add_abonnement->id)->update(['payments' => 1]);

                return response()->json(
                    [
                        'status' => 'success',
                        'code' => 200,
                        'message' => " Ok  !  L'abonnement a été enregistré avec succès"
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de l'enregistrement de l'abonnement, veuillez réessayer!"
                    ]
                );
            endif;
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 302,
                    'message' => $e->getMessage()
                ]
            );
        }

    }





    public function update(Request $request,$slug)
    {

        try {

            if(empty($request->abonne_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le l'identifiant de l'abonné est obligatoire"
                    ]
                );
            endif;

            if(empty($request->abonne_forfait_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le forfait de l'abonné est obligatoire"
                    ]
                );
            endif;


            $forfait_info = DB::table('forfaits_abonnements_mobile_models')->where('id',$request->abonne_forfait_id)->first();

            //$sizeOfCountry = sizeof($request->pays);
            $dateline = 'P'.$forfait_info->duree_forfait.'D';

            $date_fin = new DateTime();
            $date_fin->add(new DateInterval($dateline));
            $date_fin->format('Y-m-d H:i:s');

            $update_abonnement = AbonnementsModels::where('abonne_id',$request->abonne_id)->first();

            $update_abonnement->abonne_id = $request->abonne_id;
            $update_abonnement->abonne_forfait_id = $request->abonne_forfait_id;
            $update_abonnement->date_fin = $date_fin;

            if($update_abonnement->save()):

                return response()->json(
                    [
                        'status' => 'success',
                        'code' => 200,
                        'message' => " Ok ! La modification de l'abonnement a été effectuée avec succès."
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modification de l'abonnement, veuillez réessayer!"
                    ]
                );
            endif;
        } catch (\Throwable $e) {

            return response()->json(
                [
                    'status' => 'error',
                    'code' => 302,
                    'message' => $e->getMessage()
                ]
            );
        }

    }


    public function reabonnement(Request $request)
    {
        try {


            if(empty($request->abonne_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le l'identifiant de l'abonné est obligatoire"
                    ]
                );
            endif;

            if(empty($request->abonne_forfait_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le forfait de l'abonné est obligatoire"
                    ]
                );
            endif;


            $forfait_info = DB::table('forfaits_abonnements_mobile_models')->where('id',$request->abonne_forfait_id)->first();

            //$sizeOfCountry = sizeof($request->pays);
            $dateline = 'P'.$forfait_info->duree_forfait.'D';

            $date_fin = new DateTime();
            $date_fin->add(new DateInterval($dateline));
            $date_fin->format('Y-m-d H:i:s');

            $reabonnement = AbonnementsModels::where('abonne_id',$request->abonne_id)->first();

            $reabonnement->abonne_id = $request->abonne_id;
            $reabonnement->abonne_forfait_id = $request->abonne_forfait_id;
            $reabonnement->date_fin = $date_fin;

            if($reabonnement->save()):

                return response()->json(
                    [
                        'code' => 200,
                        'status' => 'succès',
                        'message' => "Réabonnement éffectué avec succès 💚"
                    ]
                );
            else:
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur!  La mise à jour de l'abonnement a échouée, veuillez réessayer!"
                    ]
                );
            endif;
        } catch (\Throwable $e)
        {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 302,
                    'message' => $e->getMessage()
                ]
            );
        }

    }

    public function __update_abonnements_validation_date(Request $request){
        try {
            
            // check abonnement_code
            if(empty($request->abonnement_code)){
                return response()->json([
                    'code' => 302,
                    'status' => 'erreur',
                    'message' => 'Le code d\'abonnement est obligatoire'
                ]);
            }
            // check validation_value
            if(empty($request->validation_value)){
                return response()->json([
                    'code' => 302,
                    'status' => 'erreur',
                    'message' => 'La valeur de validation est obligatoire'
                ]);
            }

            $currentAbonnement = DB::table('abonnements_mobile_models')->where('abonnement_code', $request->abonnement_code)->first();

            $dateline = 'P'. $request->validation_value.'D';

            $date_fin = new DateTime($request->abonnement_duration);
            $date_fin->add(new DateInterval($dateline));
            $date_fin->format('Y-m-d H:i:s');

            $abonnements_validation_date = DB::table('abonnements_mobile_models')->where('abonnement_code', $request->abonnement_code)
            ->update([
                'date_fin' => $date_fin,
                'abonne_forfait_id' => $request->abonne_forfait_id,
                'payments' => 1,
            ]);
            if($abonnements_validation_date){
                return response()->json([
                    'code' => 200,
                    'status' =>'success',
                    'message' => 'La date de validation de l\'abonnement a été mise à jour avec succès'
                ]);
            }else{
                return response()->json([
                    'code' => 500,
                    'status' => 'erreur',
                    'message' => 'Erreur! La mise à jour de la date de validation de l\'abonnement a échouée, veuillez réessayer!'
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'status' => 'erreur',
                'message' => $th->getMessage()
            ]);
        }
    }

    // filter_by_abonnements_payment
    public function filter_by_abonnements_payment($payment_status){
        try {

            if($payment_status == "all"):
                return   DB::table('abonnements_mobile_models')
                ->join('abonnes_mobile_models', 'abonnements_mobile_models.abonne_id', '=', 'abonnes_mobile_models.id')
                ->join('forfaits_abonnements_mobile_models', 'abonnements_mobile_models.abonne_forfait_id','=', 'forfaits_abonnements_mobile_models.id')
                ->select(
                    'abonnes_mobile_models.abonne_phone_number',
                    'abonnes_mobile_models.abonne_fname',
                    'abonnes_mobile_models.abonne_lname',
                    'abonnes_mobile_models.abonne_email',
                    'forfaits_abonnements_mobile_models.forfait_libelle',
                    'forfaits_abonnements_mobile_models.montant_forfait',
                    'abonnements_mobile_models.*'
                )
                ->orderBy('abonnements_mobile_models.id', 'desc')
                ->get();
            elseif ($payment_status == "paid")  :
                return  DB::table('abonnements_mobile_models')
                ->join('abonnes_mobile_models', 'abonnements_mobile_models.abonne_id', '=', 'abonnes_mobile_models.id')
                ->join('forfaits_abonnements_mobile_models', 'abonnements_mobile_models.abonne_forfait_id','=', 'forfaits_abonnements_mobile_models.id')
                ->select(
                    'abonnes_mobile_models.abonne_phone_number',
                    'abonnes_mobile_models.abonne_fname',
                    'abonnes_mobile_models.abonne_lname',
                    'abonnes_mobile_models.abonne_email',
                    'forfaits_abonnements_mobile_models.forfait_libelle',
                    'forfaits_abonnements_mobile_models.montant_forfait',
                    'abonnements_mobile_models.*'
                )
                ->orderBy('abonnements_mobile_models.id', 'desc')
                ->where('abonnements_mobile_models.payments', 1)
                ->get();
            elseif ($payment_status == "unpaid")  :
                return DB::table('abonnements_mobile_models')
                ->join('abonnes_mobile_models', 'abonnements_mobile_models.abonne_id', '=', 'abonnes_mobile_models.id')
                ->join('forfaits_abonnements_mobile_models', 'abonnements_mobile_models.abonne_forfait_id','=', 'forfaits_abonnements_mobile_models.id')
                ->select(
                    'abonnes_mobile_models.abonne_phone_number',
                    'abonnes_mobile_models.abonne_fname',
                    'abonnes_mobile_models.abonne_lname',
                    'abonnes_mobile_models.abonne_email',
                    'forfaits_abonnements_mobile_models.forfait_libelle',
                    'forfaits_abonnements_mobile_models.montant_forfait',
                    'abonnements_mobile_models.*'
                )
                ->orderBy('abonnements_mobile_models.id', 'desc')
                ->where('abonnements_mobile_models.payments', 0)
                ->get();
            else:
                return response()->json([
                    'code' => 302,
                    'status' => 'erreur',
                    'message' => 'Statut de paiement inconnu'
                ]);
            endif;
            
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'status' => 'erreur',
                'message' => $th->getMessage()
            ]);
        }
    }

    //destroy_abonnement by slug
    public function destroy(string $slug){
        try {
            $abonnements_mobile_model = AbonnementsMobileModels::where('slug', $slug)->first();
            if($abonnements_mobile_model){
                $abonnements_mobile_model->delete();
                return response()->json([
                    'code' => 200,
                    'status' =>'succès',
                    'message' => 'Abonnement supprimé avec succès'
                ]);
            }else{
                return response()->json([
                    'code' => 404,
                    'status' => 'erreur',
                    'message' => 'Abonnement introuvable'
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'status' => 'erreur',
                'message' => $th->getMessage()
            ]);
        }
    }
    
}
