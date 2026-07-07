<?php

namespace App\Http\Controllers\API\V1\AlerteInfoWebSite;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\AlerteInfoWebSite\OurContactsModels;

class OurContactsController extends Controller
{
    public function getContactsData()
    {
        try {
            return response()->json([
                'status' => 'Succès',
                'data' => OurContactsModels::first(),
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

    
    public function storeContactsData(Request $request)
    {
        try {
            if(empty($request->contents)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le contenu est obligatoire"
                ],400);
            }

            $newContactsData = OurContactsModels:: create([
                'contents' => $request->contents,
                'slug' => Str::uuid()
            ]);

            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'data' => $newContactsData,
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


    public function updateContactsData(Request $request, $slug)
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

            $contactsData = OurContactsModels::where('slug', $slug)->first();
            if(!$contactsData){
                return response()->json(
                    [
                        'status' => 'Erreur',
                        'code' => 400,
                        'message' => "Aucune donnée trouvée"
                    ]
                );
            }

            $contactsData->update([
                'contents' => $request->contents
            ]);

            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'data' => $contactsData,
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


    public function deleteContactsData($slug)
    {
        try {
            if(!$slug){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le slug est obligatoire"
                ],400);
            }

            $acontactsData = OurContactsModels::where('slug', $slug)->first();
            if(!$acontactsData){
                return response()->json(
                    [
                        'status' => 'Erreur',
                        'code' => 400,
                        'message' => "Aucune donnée trouvée"
                    ]
                );
            }

            $acontactsData->delete();
            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'data' => $acontactsData,
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
