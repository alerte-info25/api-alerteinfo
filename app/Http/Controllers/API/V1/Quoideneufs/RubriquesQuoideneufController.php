<?php

namespace App\Http\Controllers\API\V1\Quoideneufs;

use App\Http\Controllers\Controller;
use App\Models\Quoideneufs\RubriquesQuoideneufModels;
use App\Models\RubriqueQuoiDeNeuf;
use App\Services\CodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RubriquesQuoideneufController extends Controller
{

    public function __construct()
    {
        //$this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return RubriquesQuoideneufModels::all();
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

    public function get_news_rubriques()
    {
        $news_rubrique = RubriquesQuoideneufModels::whereIn('rubrique', ['INTERNATIONAL','SPORT','TRIBUNE LIBRE','SOCIETE','CULTURE'])
        ->orderBy('rubrique','asc')
        ->get();

        $other_news_rubrique = RubriquesQuoideneufModels::whereNotIn('rubrique', ['INTERNATIONAL','SPORT','TRIBUNE LIBRE','SOCIETE','CULTURE'])
        ->orderBy('rubrique','asc')
        ->get();


        return [
            'news_rubrique' => $news_rubrique,
            'other_news_rubriques' => $other_news_rubrique
        ];
    }

    public function get_other_rubrique()
    {
        $rubrique = RubriquesQuoideneufModels::whereNotIn('rubrique', ['POLITIQUE','ECONOMIE','SOCIETE'])
        ->orderBy('rubrique','asc')
        ->get();

        return $rubrique;
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

            $add_rubrique = new RubriquesQuoideneufModels();
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

            $update_rubrique = RubriquesQuoideneufModels::where('slug',$slug)->first();
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

                DB::table('rubriques_quoideneuf_models')->where('slug', $slug)->delete();
                return response()->json(
                    [
                        'status' => 'success',
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
