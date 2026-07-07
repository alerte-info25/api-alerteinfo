<?php
namespace App\Services\CategorieGeneraleServices;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Services\UserLogServices\UserLogService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;
use App\Models\CategorieGeneraleModels\CategorieGeneraleModel;
use App\Models\DocumentsRequisModels\DocumentsRequisModel;

class CategorieGeneraleService
{

    public function __construct(
        private readonly CategorieGeneraleModel $categorieGeneraleModel,
        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogError $customLogError,
        private readonly CodeGeneratorService $codeGeneratorService,
        private readonly UserLogService $userLogService,
        private readonly DocumentsRequisModel $documentsRequisModel
    ) {}

    public function srv_getCategorieGeneraleList(Request $request): JsonResponse{
        try {
            // pagination parameters
            $per_page = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            // get categorie generale list
            $categorieGeneraleList = $this->categorieGeneraleModel
            ->paginate($per_page, ['*'], 'per_page', $page);
            $categorieGeneraleListFormatted = $categorieGeneraleList->getCollection()->map(function ($categorieGenerale) {
                return [
                    'categorie_code_unique' => $categorieGenerale->categorie_code_unique,
                    'categorie_name' => $categorieGenerale->categorie_name,
                    'categorie_montant' => $categorieGenerale->categorie_montant,
                    'type' => $categorieGenerale->type,
                    'document_code_unique' => $categorieGenerale->document_code_unique,
                    'slug' => $categorieGenerale->slug,
                    'created_at' => $categorieGenerale->created_at,
                ];
            });

            return $this->jsonResponseService->successResponseWithData(
                'Liste des categories generales récupérée avec succès',
                [
                    'categorieGeneraleList' => $categorieGeneraleListFormatted,
                    'paginations' => [
                        'total' => $categorieGeneraleList->total(),
                        'per_page' => $categorieGeneraleList->perPage(),
                        'current_page' => $categorieGeneraleList->currentPage(),
                        'last_page' => $categorieGeneraleList->lastPage(),
                        'from' => $categorieGeneraleList->firstItem(),
                        'to' => $categorieGeneraleList->lastItem()
                    ]
                ],
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->customLogError->logError('Erreur lors de la récupération de la liste des categories generales', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération de la liste des categories generales',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    // get categorie form data
    public function srv_getCategorieGeneraleFormData(): JsonResponse
    {
        try {
            $documentsRequisList = $this->documentsRequisModel->get();
            return $this->jsonResponseService->successResponseWithData(
                'Liste des documents requis récupérée avec succès',
                $documentsRequisList,
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError('Erreur lors de la récupération de la liste des documents requis', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération de la liste des documents requis',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    public function srv_createCategorieGenerale(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $categorieCodeUnique = $this->codeGeneratorService->generateDefaultCodeUnique(
                'categorie_generale_models',
                'categorie_code_unique',
                'CG'
            );

            $categorieGeneraleCreated = $this->categorieGeneraleModel->create([
                'categorie_code_unique' => $categorieCodeUnique,
                'categorie_name' => $request->categorie_name,
                'categorie_montant' => $request->categorie_montant,
                'type' => $request->type,
                'document_code_unique' => $request->document_code_unique,
                'slug' => Str::uuid()
            ]);

            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'create',
                sprintf(
                    "Categorie generale %s créé avec succès par %s à %s",
                    $categorieGeneraleCreated->categorie_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'Categorie generale créé avec succès',
                $categorieGeneraleCreated,
                Response::HTTP_CREATED
            );

        } catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la création de la categorie generale', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la création de la categorie generale',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateCategorieGenerale(Request $request, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $categorieGenerale = $this->categorieGeneraleModel->where('slug', $slug)->firstOrFail();

            $categorieGenerale->update([
                'categorie_name' => $request->categorie_name,
                'categorie_montant' => $request->categorie_montant,
                'type' => $request->type,
                'document_code_unique' => $request->document_code_unique,
            ]);

            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'update',
                sprintf(
                    "Categorie generale %s modifié avec succès par %s à %s",
                    $categorieGenerale->categorie_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'Categorie generale modifié avec succès',
                $categorieGenerale,
                Response::HTTP_OK
            );

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->jsonResponseService->errorResponse(
                'Categorie generale non trouvée',
                Response::HTTP_NOT_FOUND
            );
        }

        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la modification de la categorie generale', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la modification de la categorie generale',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteCategorieGenerale($slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $categorieGenerale = $this->categorieGeneraleModel->where('slug', $slug)->firstOrFail();

            $categorieGenerale->delete();

            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'delete',
                sprintf(
                    "Categorie generale %s supprimé avec succès par %s à %s",
                    $categorieGenerale->categorie_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'Categorie generale supprimée avec succès',
                $categorieGenerale,
                Response::HTTP_OK
            );

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->jsonResponseService->errorResponse(
                'Categorie generale non trouvée',
                Response::HTTP_NOT_FOUND
            );
        }

        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la suppression de la categorie generale', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la suppression de la categorie generale',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
