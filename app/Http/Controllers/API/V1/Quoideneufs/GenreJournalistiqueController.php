<?php

namespace App\Http\Controllers\API\V1\Quoideneufs;

use App\Http\Controllers\Controller;
use App\Models\Quoideneufs\GenreJournalistiqueModels;
use App\Services\CodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GenreJournalistiqueController extends Controller
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
            return GenreJournalistiqueModels::all();
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
            if (empty($request->genre)) :
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "La rubrique est obligatoire"
                    ]
                );
            endif;

            $add_genre = new GenreJournalistiqueModels();
            $add_genre->genre = $request->genre;
            $add_genre->slug = CodeGenerator::generateRfk();
            if($add_genre->save()) :
                return response()->json(
                    [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Ok ! Le genre a été enregistré avec succès'
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de l'enregistrement du genre, veuillez réessayer!"
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
            if (empty($request->genre)) :
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "La rubrique est obligatoire"
                    ]
                );
            endif;

            $add_genre =  GenreJournalistiqueModels::where('slug',$slug)->first();
            $add_genre->genre = $request->genre;

            if($add_genre->save()) :
                return response()->json(
                    [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Ok ! Le genre a été modifié avec succès'
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modification du genre, veuillez réessayer!"
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

                DB::table('genre_journalistique_models')->where('slug', $slug)->delete();
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
                    'status' => 'error',
                    'code' => 302,
                    'message' => $e->getMessage()
                ]
            );
        }
    }
}
