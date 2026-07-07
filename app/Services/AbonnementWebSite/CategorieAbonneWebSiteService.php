<?php

namespace App\Services\AbonnementWebSite;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\AbonnesWebModels\CategoriesAbonnesWebModels;


class CategorieAbonneWebSiteService
{
    public function __construct(
        private readonly CategoriesAbonnesWebModels $model
    )
    {
    }

    public function srv_getCategorieAbonneWebSite(Request $request)
    {
        try {
            // pagination variables
            $perpage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            $categorieData = $this->model->paginate($perpage, ['*'], 'page', $page);

            $categorieDataFormated = $categorieData->getCollection()->map(function ($categorie) {
                return [
                    'categorie_name' => $categorie->categorie,
                    'can_copy' => $categorie->can_copy,
                    'can_share' => $categorie->can_share,
                    'can_read' => $categorie->can_read,
                    'can_download' => $categorie->can_download,
                    'slug' => $categorie->slug,
                    'created_at' => $categorie->created_at,
                ];
            });

            return response()->json([
                'status' => 'success',
                'categorieData' => $categorieDataFormated,
                'pagination' => [
                    'total' => $categorieData->total(),
                    'per_page' => $categorieData->perPage(),
                    'current_page' => $categorieData->currentPage(),
                    'last_page' => $categorieData->lastPage(),
                ],
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des forfaits: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Erreur lors de la récupération des forfaits.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function srv_createCategorieAbonneWebSite(Request $request): JsonResponse
    {
        try {
            $categorieData = $this->model->create([
                'category_code' => Carbon::now()->format('YmdHis') . '-' .Str::upper(Str::random(6)),
                'categorie' => $request->categorie,
                'can_copy' => $request->can_copy,
                'can_share' => $request->can_share,
                'can_read' => $request->can_read,
                'can_download' => $request->can_download,
                'slug' => Str::uuid()->toString(),
            ]);

            return response()->json([
                'status' => 'success',
                'categorieData' => $categorieData,
                'message' => 'La catégorie a été créé avec succès.',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la création de la catégorie: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Erreur lors de la création de la catégorie.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function srv_updateCategorieAbonneWebSite(Request $request, string $slug): JsonResponse
    {
        try {
            $categorieData = $this->model->where('slug', $slug)->firstOrFail();

            $categorieData->update([
                'categorie' => $request->categorie,
                'can_copy' => $request->can_copy,
                'can_share' => $request->can_share,
                'can_read' => $request->can_read,
                'can_download' => $request->can_download,
            ]);

            return response()->json([
                'status' => 'success',
                'categorieData' => $categorieData,
                'message' => 'La catégorie a été modifiée avec succès.',
            ], Response::HTTP_OK);
        } catch(ModelNotFoundException $e) {
            return response()->json([
                'status' => 'erreur',
                'code' => 404,
                'message' => 'La catégorie avec le slug ' . $slug . ' n\'existe pas.',
            ], Response::HTTP_NOT_FOUND);
        }
        catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la modification de la catégorie: " . $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Erreur lors de la modification de la catégorie.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function srv_deleteCategorieAbonneWebSite(string $slug): JsonResponse
    {
        try {
            $categorieData = $this->model->where('slug', $slug)->firstOrFail();

            $categorieData->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'La catégorie a été supprimée avec succès.',
            ], Response::HTTP_OK);
        } catch(ModelNotFoundException $e) {
            return response()->json([
                'status' => 'erreur',
                'code' => 404,
                'message' => 'La catégorie avec le slug ' . $slug . ' n\'existe pas.',
            ], Response::HTTP_NOT_FOUND);
        }
        catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la suppression de la catégorie: " . $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Erreur lors de la suppression de la catégorie.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}

