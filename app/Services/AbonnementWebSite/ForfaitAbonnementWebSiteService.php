<?php

namespace App\Services\AbonnementWebSite;

use App\Models\AbonnesWebModels\CategoriesAbonnesWebModels;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AbonnementsWebModels\AbonnementWebForfaitsModels;



class ForfaitAbonnementWebSiteService
{
    public function __construct(
        private readonly AbonnementWebForfaitsModels $model,
        private readonly CategoriesAbonnesWebModels $categoryModel
    )
    {
    }

    public function srv_getForfaitAbonnementWebSite(Request $request)
    {
        try {
            // pagination variables
            $perpage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            $forfaitData = $this->model->paginate($perpage, ['*'], 'page', $page);

            Log::info("ForfaitData: ". $forfaitData);

            $forfaitDataFormated = $forfaitData->getCollection()->map(function ($forfait) {
                return [
                    'forfait_name' => $forfait->forfait,
                    'category_name' => $forfait->categories->categorie,
                    'category_code' => $forfait->category_code,
                    'montant' => $forfait->montant,
                    'duree' => $forfait->duree,
                    'status' => $forfait->status,
                    'slug' => $forfait->slug,
                    'created_at' => $forfait->created_at,
                ];
            });

            return response()->json([
                'status' => 'success',
                'forfaitData' => $forfaitDataFormated,
                'pagination' => [
                    'total' => $forfaitData->total(),
                    'per_page' => $forfaitData->perPage(),
                    'current_page' => $forfaitData->currentPage(),
                    'last_page' => $forfaitData->lastPage(),
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

    
    public function srv_getForfaitAbonnementFormData()
    {
        try {
            $categoryData = $this->categoryModel->all();

            return response()->json([
                'status' => 'success',
                'categoryData' => $categoryData,
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des categories: ". $th->getMessage(), [
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
    
    public function srv_getForfaitAbonnementFormData2()
    {
        try {
            $categoryData = $this->model->limit(5)->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'forfait_name' => $item->forfait,
                    'montant' => $item->montant,
                    'duree' => $item->duree,
                    'status' => $item->status,
                    'category_code' => $item->category_code,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'slug' => $item->slug,
                    
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $categoryData,
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des categories: ". $th->getMessage(), [
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

    public function srv_createForfaitAbonnementWebSite(Request $request): JsonResponse
    {
        try {
            $forfaitData = $this->model->create([
                'category_code' => $request->category_code,
                'forfait' => $request->forfait,
                'montant' => $request->montant,
                'duree' => $request->duree,
                'status' => $request->status,
                'slug' => Str::uuid()->toString(),
            ]);

            return response()->json([
                'status' => 'success',
                'forfaitData' => $forfaitData,
                'message' => 'Le forfait a été créé avec succès.',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la création du forfait: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Erreur lors de la création du forfait.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function srv_updateForfaitAbonnementWebSite(Request $request, string $slug): JsonResponse
    {
        try {
            $forfaitData = $this->model->where('slug', $slug)->firstOrFail();

            $forfaitData->update([
                'category_code' => $request->category_code,
                'forfait' => $request->forfait,
                'montant' => $request->montant,
                'duree' => $request->duree,
                'status' => $request->status,
            ]);

            return response()->json([
                'status' => 'success',
                'forfaitData' => $forfaitData,
                'message' => 'Le forfait a été modifié avec succès.',
            ], Response::HTTP_OK);
        } catch(ModelNotFoundException $e) {
            return response()->json([
                'status' => 'erreur',
                'code' => 404,
                'message' => 'Le forfait avec le slug ' . $slug . ' n\'existe pas.',
            ], Response::HTTP_NOT_FOUND);
        }
        catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la modification du forfait: " . $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Erreur lors de la modification du forfait.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function srv_deleteForfaitAbonnementWebSite(string $slug): JsonResponse
    {
        try {
            $forfaitData = $this->model->where('slug', $slug)->firstOrFail();

            $forfaitData->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Le forfait a été supprimé avec succès.',
            ], Response::HTTP_OK);
        } catch(ModelNotFoundException $e) {
            return response()->json([
                'status' => 'erreur',
                'code' => 404,
                'message' => 'Le forfait avec le slug ' . $slug . ' n\'existe pas.',
            ], Response::HTTP_NOT_FOUND);
        }
        catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la suppression du forfait: " . $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Erreur lors de la suppression du forfait.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

