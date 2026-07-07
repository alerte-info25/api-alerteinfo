<?php

namespace App\Services\AbonnementWebSite;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AbonnementsWebModels\AbonnementWebModels;
use App\Models\Redactions\CountriesModels;
use App\Models\AbonnesWebModels\AbonnesWebModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DateInterval;
use DateTime;
class AbonnementWebSiteService
{
    
    private ForfaitAbonnementWebSiteService $forfaitAbonnementWebSiteService;
    private CountriesModels $countriesModels;
    private AbonnesWebModels $abonnesWebModels;
    public function __construct(
        AbonnementWebModels $abonnementWebModel,
        ForfaitAbonnementWebSiteService $forfaitAbonnementWebSiteService,
        CountriesModels $countriesModels,
        AbonnesWebModels $abonnesWebModel
    ) {
        $this->abonnementWebModel = $abonnementWebModel;
        $this->forfaitAbonnementWebSiteService = $forfaitAbonnementWebSiteService;
        $this->countriesModels = $countriesModels;
        $this->abonnesWebModel = $abonnesWebModel;
    }
    
    public function srv_getAbonnementWebSite(Request $request)
    {

        try {
            // pagination variables
            $perpage = $request->input('perpage', 20);
            $page = $request->input('page', 1);

            $abonnementData = $this->abonnementWebModel->with(['forfaits', 'countrie.country','abonnes'])
            ->orderByDesc('id')
            ->paginate($perpage, ['*'], 'page', $page);

            $abonnementDataFormated = $abonnementData->getCollection()->map(function ($abonnement) {
                return [
                    'abonnement_web_code' => $abonnement->abonnement_web_code,
                    'account_code_unique' => $abonnement->account_code_unique,
                    'full_name' => $abonnement->abonnes->full_name,
                    'email' => $abonnement->abonnes->email,
                    'forfait_id' => $abonnement->forfait_id,
                    'forfait_name' => $abonnement->forfaits->forfait,
                    'category' => $abonnement->abonnes->categories->categorie,
                    'montant' => $abonnement->montant,
                    'start_date' => $abonnement->start_date,
                    'end_date' => $abonnement->end_date,
                    'countrie' => $abonnement->countrie->map(function ($item) {
                        return $item->country->pays ?? null;
                    })->filter()->values(),
                    'customer_city' => $abonnement->customer_city,
                    'customer_address' => $abonnement->customer_address,
                    'payments' => $abonnement->payments,
                    'created_at' => $abonnement->created_at,
                    'slug' => $abonnement->slug,
                ];
            });


            
            return response()->json([
                'status' => 'success',
                'abonnementData' => $abonnementDataFormated,
                'pagination' => [
                    'total' => $abonnementData->total(),
                    'per_page' => $abonnementData->perPage(),
                    'current_page' => $abonnementData->currentPage(),
                    'last_page' => $abonnementData->lastPage(),
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
    
    // public function srv_getAbonnementWebFormData()
    // {
    //     try {
    //         $abonnesweb = $this->abonnementWebModel->getAbonnesWeb();
    //         $forfaits = $this->abonnementWebModel->getForfaits();
    //         $countries = $this->abonnementWebModel->getCountries();

    //         return response()->json([
    //             'status' => 'success',
    //             'forfaits' => $forfaits,
    //             'countries' => $countries,
    //             'abonnes' => $abonnesweb,
    //         ], Response::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         // Log
    //         Log::error("Erreur lors de la récupération des données du formulaire: ". $th->getMessage(), [
    //             'code' => $th->getCode(),
    //             'file' => $th->getFile(),
    //             'line' => $th->getLine(),
    //         ]);
    //         // return error response
    //         return response()->json([
    //             'status' => 'erreur',
    //             'code' => 500,
    //             'message' => 'Une erreur est survenue lors de la récupération des données du formulaire. Veuillez réessayer plus tard.',
    //         ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }
    
    public function srv_getAbonnementWebFormData()
    {
        try {
            // récupérer les abonnés via la relation
            // $abonnesweb = $this->abonnementWebModel
            //     ->with('abonnes')
            //     ->get()
            //     ->unique('account_code_unique')
            //     ->values();
            $abonnesweb = $this->abonnesWebModel->orderBy('id','desc')->get();

            // récupérer les forfaits
            $forfaits = $this->abonnementWebModel
                ->with('forfaits')
                ->get()
                ->pluck('forfaits')
                ->filter()
                ->unique('id')
                ->values();
    
            // récupérer les pays
            $countries = $this->countriesModels->all();
    
            return response()->json([
                'status' => 'success',
                'forfaits' => $forfaits,
                'pays' => $countries,
                'abonnes' => $abonnesweb,
            ], Response::HTTP_OK);
    
        } catch (\Throwable $th) {
            Log::error("Erreur: " . $th->getMessage());
    
            return response()->json([
                'status' => 'erreur',
                'message' => 'Erreur serveur',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    

    public function srv_storeAbonnementWebSite(Request $request)
    {
        try {
    
            return DB::transaction(function () use ($request) {
    
                $forfait = DB::table('abonnement_web_forfaits_models')
                    ->where('id', $request->abonne_forfait_id)
                    ->first();
    
                if (!$forfait) {
                    throw new \Exception("Forfait introuvable");
                }
    
                $nbPays = is_array($request->pays_id) ? count($request->pays_id) : 0;
    
                $interval = 'P' . $forfait->duree . 'D';
    
                $dateFin = new DateTime();
                $dateFin->add(new DateInterval($interval));
    
                $abonnementCode = uniqid('ABO_WEB_');
    
                $montant = $nbPays * $forfait->montant;
    
                $abonnement = $this->abonnementWebModel->create([
                    'abonnement_web_code' => $abonnementCode,
                    'account_code_unique' => $request->abonne_id,
                    'forfait_id' => $request->abonne_forfait_id,
                    'montant' => $montant,
                    'start_date' => now(),
                    'end_date' => $dateFin,
                    'customer_city' => $request->customer_city,
                    'customer_address' => $request->customer_address,
                    'customer_zip_code' => $request->customer_zip_code,
                    'customer_state' => $request->customer_state,
                    'payments' => 1,
                    'slug' => Str::uuid(),
                ]);
    
                if (!empty($request->pays_id)) {
                    foreach ($request->pays_id as $countryId) {
                        \App\Models\AbonnementsWebModels\AbonnementWebCountrieModels::create([
                            'abonnement_web_code' => $abonnementCode,
                            'country_id' => $countryId,
                        ]);
                    }
                }
    
                DB::table('abonnes_web_models')
                    ->where('account_code_unique', $request->abonne_id)
                    ->update(['status' => 1]);
    
                DB::table('abonnement_web_models')
                    ->where('id', $abonnement->id)
                    ->update(['payments' => 1]);
    
                return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'message' => "Abonnement web créé avec succès",
                    'data' => $abonnement
                ], Response::HTTP_CREATED);
            });
    
        } catch (\Throwable $th) {
    
            Log::error("Erreur création abonnement web: " . $th->getMessage(), [
                'line' => $th->getLine(),
            ]);
    
            return response()->json([
                'status' => 'erreur',
                'message' => 'Erreur serveur',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function srv_createAbonnementWebSite(Request $request)
    {
        // validate request
        $validatedData = $request->validate([
            'account_code_unique' => 'required|string|exists:abonnes_web_models,account_code_unique',
            'forfait_id' => 'required|integer|exists:abonnement_web_forfaits_models,id',
            'montant' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'country_code' => 'required|string|exists:countries_models,country_code',
            'customer_city' => 'nullable|string',
            'customer_address' => 'nullable|string',
            'customer_zip_code' => 'nullable|string',
            'customer_state' => 'nullable|string',
            'payments' => 'nullable|string',
        ]);

        // create abonnement web site
        $abonnementWebSite = $this->abonnementWebModel->create($validatedData);

        return $abonnementWebSite;
    }
    public function srv_updateAbonnementWebValidityDate(Request $request)
    {
        try {
    
            return DB::transaction(function () use ($request) {
    
                // récupérer abonnement
                $abonnement = $this->abonnementWebModel
                    ->where('abonnement_web_code', $request->abonnement_code)
                    ->first();
    
                if (!$abonnement) {
                    throw new \Exception("Abonnement introuvable");
                }
    
                // récupérer forfait
                $forfait = \App\Models\AbonnementsWebModels\AbonnementWebForfaitsModels::find($request->abonne_forfait_id);
    
                if (!$forfait) {
                    throw new \Exception("Forfait introuvable");
                }
    
                // calcul nouvelle date fin
                $dateFin = new DateTime($abonnement->end_date);
                $dateFin->add(new DateInterval('P' . $forfait->duree . 'D'));
    
                // update abonnement
                $abonnement->update([
                    'end_date' => $dateFin,
                    'forfait_id' => $request->abonne_forfait_id,
                    'payments' => 1,
                ]);
    
                return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'message' => "Abonnement web reconduit avec succès"
                ], Response::HTTP_OK);
    
            });
    
        } catch (\Throwable $th) {
    
            Log::error("Erreur reconduction abonnement web: " . $th->getMessage(), [
                'line' => $th->getLine(),
            ]);
    
            return response()->json([
                'status' => 'erreur',
                'message' => 'Erreur serveur'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

