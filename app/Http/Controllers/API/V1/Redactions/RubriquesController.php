<?php

namespace App\Http\Controllers\API\V1\Redactions;

use App\Http\Controllers\Controller;
use App\Models\Redactions\RubriqueModels;
use App\Services\CodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RubriquesController extends Controller
{
    public function __construct()
    {
        //$this->middleware('auth:api', ['except' => ['index']]);
    }

    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return RubriqueModels::all();
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
            if (empty($request->rubrique)) :
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "La rubrique est obligatoire"
                    ]
                );
            endif;

            $add_rubrique = new RubriqueModels();
            $add_rubrique->rubrique = $request->rubrique;
            $add_rubrique->slug = CodeGenerator::generateRfk();

            if($add_rubrique->save()) :
                return response()->json(
                    [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Ok!, La rubrique a été enregistrer avec succès'
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de l'enregistrement de la rubrique, veuillez réessayer!"
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

            if (empty($request->rubrique)) :
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "La rubrique est obligatoire"
                    ]
                );
            endif;

            $update_rubrique = RubriqueModels::where('slug',$slug)->first();
            $update_rubrique->rubrique = $request->rubrique;

            if($update_rubrique->save()) :
                return response()->json(
                    [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Ok!, La rubrique a été modifiée avec succès'
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modification du compte, veuillez réessayer!"
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

                DB::table('rubriques_models')->where('slug', $slug)->delete();
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
