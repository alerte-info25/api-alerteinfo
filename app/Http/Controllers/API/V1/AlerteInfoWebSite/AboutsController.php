<?php

namespace App\Http\Controllers\API\V1\AlerteInfoWebSite;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\AlerteInfoWebSite\AboutsModels;

class AboutsController extends Controller
{
    public function getAboutsData()
    {
        try {
            return response()->json([
                'status' => 'Succès',
                'data' => AboutsModels::first(),
                'message' => "Données récuprées avec succès"
            ]);
        } catch (\Throwable $th) {
            Log::error('Erreur lors de la récupération des donnés:'  , [$th->getMessage()]);

            return response()->json([
                'status' => 'Erreur',
                'code' => 500,
                'message' => "Erreur lors de la récupération des donnés"
            ]);
        }
    }

    public function storeAboutData(Request $request)
    {
        //return $request->all();
        try {
            if(empty($request->contents)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le contenu est obligatoire"
                ],400);
            }

            $newAboutData = AboutsModels:: create([
                'contents' => $request->contents,
                'slug' => Str::uuid()
            ]);

            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'data' => $newAboutData,
                'message' => 'Données enregistrées avec succès'
            ]);
        } catch (\Throwable $th) {
            Log::error('Erreur lors de la création des donnés:'  , [$th->getMessage()]);

            return response()->json([
                'status' => 'Erreur',
                'code' => 500,
                'message' => "Erreur lors de la création des donnés"
            ], 500);
        }
    }

    public function updateAboutData(Request $request, $slug)
    {
        try {
            if(empty($request->contents)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le contenu est obligatoire"
                ],400);
            }

            if(empty($request->slug)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le slug est obligatoire"
                ],400);
            }

            $aboutData = AboutsModels::where('slug', $slug)->first();
            if(!$aboutData){
                return response()->json(
                    [
                        'status' => 'Erreur',
                        'code' => 400,
                        'message' => "Aucune donnée trouvée"
                    ]
                );
            }

            $aboutData->update([
                'contents' => $request->contents
            ]);

            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'data' => $aboutData,
                'message' => 'Données modifiées avec succès'
            ]);
        } catch (\Throwable $th) {
            Log::error('Erreur lors de la modification des donnés:'  , [$th->getMessage()]);

            return response()->json([
                'status' => 'Erreur',
                'code' => 500,
                'message' => "Erreur lors de la modification des donnés"
            ], 500);
        }
    }


    public function deleteAboutData($slug)
    {
        try {
            if($slug){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le slug est obligatoire"
                ],400);
            }

            $aboutData = AboutsModels::where('slug', $slug)->first();
            if(!$aboutData){
                return response()->json(
                    [
                        'status' => 'Erreur',
                        'code' => 400,
                        'message' => "Aucune donnée trouvée"
                    ]
                );
            }

            $aboutData->delete();
            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'data' => $aboutData,
                'message' => 'Données supprimée avec succès'
            ]);
        } catch (\Throwable $th) {
            Log::error('Erreur lors de la modification des donnés:'  , [$th->getMessage()]);

            return response()->json([
                'status' => 'Erreur',
                'code' => 500,
                'message' => "Erreur lors de la modification des donnés"
            ], 500);
        }
    }


}
