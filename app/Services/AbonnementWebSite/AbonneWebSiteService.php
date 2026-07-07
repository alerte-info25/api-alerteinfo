<?php

namespace App\Services\AbonnementWebSite;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AbonnesWebModels\AbonnesWebModels;
use Illuminate\Support\Facades\DB;

class AbonneWebSiteService
{
    public function __construct(
        private readonly AbonnesWebModels $abonneWebSiteModel
    )
    {
    }

    public function srv_getAbonneWebSite(Request $request)
    {
        try {
            // pagination variables
            $perpage = $request->input('perpage', 20);
            $page = $request->input('page', 1);

            $abonneData = $this->abonneWebSiteModel->with('categories')
            ->orderByDesc('id')
            ->paginate($perpage, ['*'], 'page', $page);

            $abonneDataFormated = $abonneData->getCollection()->map(function ($abonne) {
                return [
                    'account_code_unique' => $abonne->account_code_unique,
                    'full_name' => $abonne->full_name,
                    'category' => $abonne->categories->categorie,
                    'phone' => $abonne->phone,
                    'email' => $abonne->email,
                    'status' => $abonne->status,
                    'last_login_at' => $abonne->last_login_at,
                    'last_logout_at' => $abonne->last_logout_at,
                    'created_at' => $abonne->created_at,
                    'updated_at' => $abonne->updated_at,
                    'slug' => $abonne->slug,
                ];
            });

            
            return response()->json([
                'status' => 'success',
                'abonneData' => $abonneDataFormated,
                'pagination' => [
                    'total' => $abonneData->total(),
                    'per_page' => $abonneData->perPage(),
                    'current_page' => $abonneData->currentPage(),
                    'last_page' => $abonneData->lastPage(),
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

    public function srv_getAbonneWebCategory()
    {
        try {
            $abonneCategoryData = DB::table('categories_abonnes_web_models')
                ->select('categorie', 'category_code', 'can_copy', 'can_share', 'can_read', 'can_download')
                ->get();


            return response()->json([
                'status' => 'success',
                'abonneCategoryData' => $abonneCategoryData,
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
}

