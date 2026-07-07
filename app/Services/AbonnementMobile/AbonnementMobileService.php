<?php

namespace App\Services\AbonnementMobile;

use DateTime;
use DateInterval;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Redactions\CountriesModels;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AbonnesMobileModels\AbonnesMobileModels;
use App\Models\AbonnementsMobileModels\AbonnementsMobileModels;
use App\Models\AbonnementsMobileModels\ForfaitsAbonnementsMobileModels;

class AbonnementMobileService
{
    public function __construct(
        private readonly AbonnementsMobileModels $abonnementMobileModel,
        private readonly AbonnesMobileModels $abonnesMobileModel,
        private readonly ForfaitsAbonnementsMobileModels $forfaitsAbonnementsMobileModel,
        private readonly CountriesModels $countriesModel,
    ){}

    public function srv_getAbonnementMobile(Request $request): JsonResponse
    {
        try {

            // pagination variables
            $perpage = $request->input('perpage', 20);
            $page = $request->input('page', 1);

            $abonnements = DB::table('abonnements_mobile_models')
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
            ->paginate($perpage, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'abonnementData' => $abonnements->items(),
                'pagination' => [
                    'total' => $abonnements->total(),
                    'per_page' => $abonnements->perPage(),
                    'current_page' => $abonnements->currentPage(),
                    'last_page' => $abonnements->lastPage(),
                ],
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des données: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des données de la page d\'accueil. Veuillez réessayer plus tard.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
    public function srv_getAbonnementMobileFormData()
    {
        try {
            $abonnes = $this->abonnesMobileModel->orderBy('id','desc')->get();
            $forfaits = $this->forfaitsAbonnementsMobileModel->orderBy('id','desc')->get();
            $pays = $this->countriesModel->orderBy('id','desc')->get();

            return response()->json(
                [
                    'abonnes' => $abonnes,
                    'forfaits' => $forfaits,
                    'pays' => $pays,
                ]);
        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des données: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des données de la page d\'accueil. Veuillez réessayer plus tard.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function srv_createLocalAbonnementMobile(Request $request): JsonResponse
    {
        try {

            return DB::transaction(function () use ($request): JsonResponse {

                $forfait_info = DB::table('forfaits_abonnements_mobile_models')->where('id',$request->abonne_forfait_id)->first();

                $sizeOfCountry = sizeof($request->pays_id);
                $dateline = 'P'.$forfait_info->duree_forfait.'D';

                $date_fin = new DateTime();
                $date_fin->add(new DateInterval($dateline));
                $date_fin->format('Y-m-d H:i:s');

                $abonnement_code = CodeGenerator::generateAbonnementCodeUnique();


                $add_abonnement = $this->abonnementMobileModel->create([
                    'abonnement_code' => $abonnement_code,
                    'abonne_id' => $request->abonne_id,
                    'abonne_forfait_id' => $request->abonne_forfait_id,
                    'abonne_country_id' => implode(',', $request->pays_id),
                    'montant_abonnements' => (int) $sizeOfCountry * $forfait_info->montant_forfait,
                    'date_debut' => date('Y-m-d H:i:s', strtotime((now()))),
                    'date_fin' => $date_fin,
                    'payments' => 1,
                    'slug' => Str::uuid(),
                ]);

                DB::table('abonnes_mobile_models')->where('id', $request->abonne_id)->update(['status_abonnement' => 1]);
                DB::table('abonnements_mobile_models')->where('id', $add_abonnement->id)->update(['payments' => 1]);

                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'message' => " Ok  !  L'abonnement a été enregistré avec succès"
                ]);
            });
        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des données: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des données de la page d\'accueil. Veuillez réessayer plus tard.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function srv_updateAbonnementMobile(Request $request, $slug): JsonResponse
    {
        try {

            return DB::transaction(function () use ($request, $slug): JsonResponse {

                $forfait_info = DB::table('forfaits_abonnements_mobile_models')->where('id',$request->abonne_forfait_id)->first();

                //$sizeOfCountry = sizeof($request->pays);
                $dateline = 'P'.$forfait_info->duree_forfait.'D';

                $date_fin = new DateTime();
                $date_fin->add(new DateInterval($dateline));
                $date_fin->format('Y-m-d H:i:s');

                $abonnement = $this->abonnementMobileModel->where('slug', $slug)->first();

                if (!$abonnement) {
                    return response()->json([
                        'status' => 'error',
                        'code' => 404,
                        'message' => 'Abonnement non trouvé'
                    ], Response::HTTP_NOT_FOUND);
                }

                $abonnement->abonne_id = $request->abonne_id;
                $abonnement->abonne_forfait_id = $request->abonne_forfait_id;
                $abonnement->abonne_country_id = implode(',', $request->pays_id);
                $abonnement->montant_abonnements = (int) $request->montant_abonnements;
                $abonnement->date_fin = $request->date_fin;

                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'message' => " Ok ! La modification de l'abonnement a été effectuée avec succès."
                ]);

            });

        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des données: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des données de la page d\'accueil. Veuillez réessayer plus tard.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function  srv_updateAbonnementMobileValidityDate(Request $request): JsonResponse
    {
        try {

            return DB::transaction(function () use ($request): JsonResponse {

                $dateline = 'P'. $request->validation_value.'D';

                $date_fin = new DateTime($request->abonnement_duration);
                $date_fin->add(new DateInterval($dateline));
                $date_fin->format('Y-m-d H:i:s');

                DB::table('abonnements_mobile_models')->where('abonnement_code', $request->abonnement_code)
                ->update([
                    'date_fin' => $date_fin,
                    'abonne_forfait_id' => $request->abonne_forfait_id,
                    'payments' => 1,
                ]);

                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'message' => " Ok ! La modification de l'abonnement a été effectuée avec succès."
                ]);
            });

        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la modification de la date de validité de l'abonnement: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la modification de la date de validité de l\'abonnement. Veuillez réessayer plus tard.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function srv_deleteAbonnementMobile($slug): JsonResponse
    {
        try {
            return DB::transaction(function () use ($slug): JsonResponse {
                $abonnement = $this->abonnementMobileModel->where('slug', $slug)->first();
                if (!$abonnement) {
                    return response()->json([
                        'status' => 'error',
                        'code' => 404,
                        'message' => 'Abonnement non trouvé'
                    ], Response::HTTP_NOT_FOUND);
                }
                $abonnement->delete();
                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'message' => " Ok ! L'abonnement a été supprimé avec succès."
                ]);
            });

        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la suppression de l'abonnement: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la suppression de l\'abonnement. Veuillez réessayer plus tard.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}

