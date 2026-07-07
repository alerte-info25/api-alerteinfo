<?php

namespace App\Http\Controllers\API\V1\AlerteInfoWebSite;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\AlerteInfoWebSite\OurServicesModels;

class OurServicesController extends Controller
{
    public function getServicesData()
    {
        try {
            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'data' => OurServicesModels::all(),
                'message' => "Données récupérées avec succès"
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Erreur lors de la récupération des donnés:'  , [$th->getMessage()]);

            return response()->json([
                'status' => 'Erreur',
                'code' => 500,
                'message' => "Erreur lors de la récupération des donnés"
            ]);
        }
    }

    //getServicesDetails
    public function getServicesDetails($slug)
    {
        try {
            $blogsData = OurServicesModels::where('slug', $slug)->first();
            if(!$blogsData){
                return response()->json(
                    [
                        'status' => 'Erreur',
                        'code' => 400,
                        'message' => "Aucune donnée trouvée"
                    ]
                );
            }
            return response()->json([
                'status' => 'Succès',
                'data' => $blogsData,
                'message' => "Données récupérées avec succès"
            ]);
        } catch (\Throwable $th) {
            Log::error('Erreur lors de la récupération des donnés:'  , [$th->getMessage()]);

            return response()->json([
                'status' => 'Erreur',
                'code' => 500,
                'message' => "Erreur lors de la récupération des donnés"
            ], 500);
        }
    }


    public function storeServicesData(Request $request)
    {
        try {
            if(empty($request->title)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le libllé est obligatoire"
                ],400);
            }
            if(empty($request->contents)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le contenu est obligatoire"
                ],400);
            }


            $newServicesData = OurServicesModels:: create([
                'title' => $request->title,
                'contents' => $request->contents,
                'media_path' => $request->media_path,
                'slug' => Str::uuid()
            ]);

            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'slug' => $newServicesData->slug,
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


    public function updateServicesData(Request $request, $slug)
    {
        try {
            if(empty($request->contents)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le contenu est obligatoire"
                ],400);
            }
            if(empty($request->title)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le libllé est obligatoire"
                ],400);
            }
            if(empty($request->slug)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le slug est obligatoire"
                ],400);
            }

            $servicesData = OurServicesModels::where('slug', $slug)->first();
            if(!$servicesData){
                return response()->json(
                    [
                        'status' => 'Erreur',
                        'code' => 400,
                        'message' => "Aucune donnée trouvée"
                    ]
                );
            }

            $servicesData->update([
                'title' => $request->title,
                'contents' => $request->contents,
                'media_path' => $request->media_path,
            ]);

            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                 'slug' => $servicesData->slug,
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


    public function deleteServicesData($slug)
    {
        try {
            if($slug){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le slug est obligatoire"
                ],400);
            }

            $ServicesData = OurServicesModels::where('slug', $slug)->first();
            if(!$ServicesData){
                return response()->json(
                    [
                        'status' => 'Erreur',
                        'code' => 400,
                        'message' => "Aucune donnée trouvée"
                    ]
                );
            }

            $ServicesData->delete();
            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'data' => $ServicesData,
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
