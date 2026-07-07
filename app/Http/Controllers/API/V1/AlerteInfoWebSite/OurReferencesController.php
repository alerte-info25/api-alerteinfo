<?php

namespace App\Http\Controllers\API\V1\AlerteInfoWebSite;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\AlerteInfoWebSite\OurReferencesModels;

class OurReferencesController extends Controller
{
    public function getReferencesData()
    {
        try {
            return response()->json([
                'status' => 'Succès',
                'data' => OurReferencesModels::first(),
                'message' => "Données récupérées avec succès"
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

    public function storeReferencesData(Request $request)
    {
        try {
            if(empty($request->contents)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le contenu est obligatoire"
                ],400);
            }
            

            $newReferencesData = OurReferencesModels:: create([
                'contents' => $request->contents,
                'country_id' => $request->country_id,
                'slug' => Str::uuid()
            ]);

            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'data' => $newReferencesData,
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


    public function updateReferencesData(Request $request, $slug)
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

            $referencesData = OurReferencesModels::where('slug', $slug)->first();
            if(!$referencesData){
                return response()->json(
                    [
                        'status' => 'Erreur',
                        'code' => 400,
                        'message' => "Aucune donnée trouvée"
                    ]
                );
            }

            $referencesData->update([
                'contents' => $request->contents,
            ]);

            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'data' => $referencesData,
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


    public function deleteReferencesData($slug)
    {
        try {
            if($slug){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le slug est obligatoire"
                ],400);
            }

            $referencesData = OurReferencesModels::where('slug', $slug)->first();
            if(!$referencesData){
                return response()->json(
                    [
                        'status' => 'Erreur',
                        'code' => 400,
                        'message' => "Aucune donnée trouvée"
                    ]
                );
            }

            $referencesData->delete();
            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'data' => $referencesData,
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
