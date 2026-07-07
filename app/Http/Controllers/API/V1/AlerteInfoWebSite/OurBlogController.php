<?php

namespace App\Http\Controllers\API\V1\AlerteInfoWebSite;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\AlerteInfoWebSite\OurBlogsModels;

class OurBlogController extends Controller
{
    public function getBlogsData()
    {
        try {
            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'data' => OurBlogsModels::all(),
                'message' => "Données récupérées avec succès"
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Erreur lors de la récupération des donnés:'  , [$th->getMessage()]);

            return response()->json([
                'status' => 'Erreur',
                'code' => 500,
                'message' => "Erreur lors de la récupération des donnés"
            ], 500);
        }
    }

    //getBlogDetails 
    public function getBlogDetails($slug)
    {
        try {
            $blogsData = OurBlogsModels::where('slug', $slug)->first();
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
    


    public function storeBlogsData(Request $request)
    {
        $dateFormated = '';


        try {
            if(empty($request->title)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le titre est obligatoire"
                ],400);
            }
            if(empty($request->contents)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le contenu est obligatoire"
                ],400);
            }

            if($request->date_publication){
                $dateFormated = date('Y-m-d',strtotime($request->date_publication));
            }


            $newBlogsData = OurBlogsModels:: create([
                'title' => $request->title,
                'lead' => $request->lead,
                'contents' => $request->contents,
                'media_path' => $request->media_path,
                'slug' => date('His',strtotime(now())) . '-' . Str::slug($request->title),
                'created_at' => $dateFormated ?? now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'slug' => $newBlogsData->slug,
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


    public function updateBlogsData(Request $request, $slug)
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

            $blogsData = OurBlogsModels::where('slug', $slug)->first();
            if(!$blogsData){
                return response()->json(
                    [
                        'status' => 'Erreur',
                        'code' => 400,
                        'message' => "Aucune donnée trouvée"
                    ]
                );
            }

            
            $blogsData->update([
                'title' => $request->title,
                'lead' => $request->lead,
                'contents' => $request->contents,
                'media_path' => $request->media_path,
            ]);

            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'slug' => $blogsData->slug,
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


    public function deleteBlogsData($slug)
    {
        try {
            if(!$slug){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le slug est obligatoire"
                ],400);
            }

            $blogsData = OurBlogsModels::where('slug', $slug)->first();
            if(!$blogsData){
                return response()->json(
                    [
                        'status' => 'Erreur',
                        'code' => 400,
                        'message' => "Aucune donnée trouvée"
                    ]
                );
            }

            $blogsData->delete();
            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'data' => $blogsData,
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

