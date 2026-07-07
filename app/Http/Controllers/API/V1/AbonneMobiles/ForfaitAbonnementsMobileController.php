<?php

namespace App\Http\Controllers\API\V1\AbonneMobiles;

use Illuminate\Http\Request;
use App\Services\CodeGenerator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AbonnementsMobileModels\ForfaitsAbonnementsMobileModels;

class ForfaitAbonnementsMobileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return ForfaitsAbonnementsMobileModels::all();
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



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            if (empty($request->forfait_libelle)) :
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "Le forfait est obligatoire"
                    ]
                );
            endif;

            if (empty($request->montant_forfait)) :
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "Le montant du forfait est obligatoire"
                    ]
                );
            endif;
            if (empty($request->duree_forfait)) :
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "La durée du forfait est obligatoire"
                    ]
                );
            endif;

            $add_forfait = new ForfaitsAbonnementsMobileModels();
            $add_forfait->forfait_libelle = $request->forfait_libelle;
            $add_forfait->montant_forfait = $request->montant_forfait;
            $add_forfait->duree_forfait = $request->duree_forfait;
            $add_forfait->slug = CodeGenerator::generateSlugCode();

            if($add_forfait->save()) :
                return response()->json(
                    [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Ok ! le forfait a été enregisté avec succès'
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de l'enregistrement du forfait, veuillez réessayer!"
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


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $slug)
    {
        try {

            if (empty($request->forfait_libelle)) :
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "Le forfait est obligatoire"
                    ]
                );
            endif;

            if (empty($request->montant_forfait)) :
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "Le montant du forfait est obligatoire"
                    ]
                );
            endif;
            if (empty($request->duree_forfait)) :
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "La durée du forfait est obligatoire"
                    ]
                );
            endif;

            $update_forfait = ForfaitsAbonnementsMobileModels::where('slug',$slug)->first();
            $update_forfait->forfait_libelle = $request->forfait_libelle;
            $update_forfait->montant_forfait = $request->montant_forfait;
            $update_forfait->duree_forfait = $request->duree_forfait;

            if($update_forfait->save()) :
                return response()->json(
                    [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Ok ! Le forfait a été modifié avec succès'
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modification du forfait, veuillez réessayer!"
                    ]
                );
            endif;
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 302,
                    'message' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $slug)
    {

        try {
            if (!$slug) :
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Erreur!, Aucun element trouvé"
                    ]
                );
            else :

                DB::table('forfaits_abonnements_mobile_models')->where('slug', $slug)->delete();

                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => "Ok!, Suppression éffectuée"
                    ]
                );

            endif;
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 302,
                    'message' => $e->getMessage()
                ]
            );
        }
    }
}
