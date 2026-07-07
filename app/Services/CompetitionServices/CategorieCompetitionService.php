<?php

namespace App\Services\CompetitionServices;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\TrancheAges\TrancheAgeModel;
use Symfony\Component\HttpFoundation\Response;
use App\Services\UserLogServices\UserLogService;
use App\Models\Competition\CategorieCompetitionModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\CodeValidity\OrganisationCodeValidityModel;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;

class CategorieCompetitionService
{
    public function __construct(
        private readonly JsonResponseService $jsonResponse,
        private readonly CustomLogError $logError,
        private readonly CodeGeneratorService $codeGenerator,
        private readonly CategorieCompetitionModel $model,
        private readonly TrancheAgeModel $trancheAgeModel,
        private readonly UserLogService $userLog,
        private readonly OrganisationCodeValidityModel $validityModel
    ){}

    public function srv_getCategorieCompetitionList(Request $request): JsonResponse
    {
        try {
            // pagination parameters
            $perPage = $request->input('per_page', 1);
            $page = $request->input('page', 20);

            // get categorie competition list
            $categorieCompetitionList = $this->model
            ->withTrashed()
            ->with('tranche')
            ->paginate($perPage, ['*'], 'page', $page);
            $categorieCompetitionListFormatted = $categorieCompetitionList->getCollection()->map(function ($categorieCompetition) {
                return [
                    'uuid' => $categorieCompetition->uuid,
                    'tranche_age' => $categorieCompetition->tranche->tranche_age,
                    'tranche_name' => $categorieCompetition->tranche->tranche_name,
                    'tranche_code_unique' => $categorieCompetition->tranche_code_unique,
                    'categorie_code_unique' => $categorieCompetition->categorie_code_unique,
                    'categorie_name' => $categorieCompetition->categorie_name,
                    'categorie_code' => $categorieCompetition->categorie_code,
                    'code_validity' => $categorieCompetition->code_validity,
                    'created_at' => $categorieCompetition->created_at,
                    'updated_at' => $categorieCompetition->updated_at,
                    'deleted_at' => $categorieCompetition->deleted_at
                ];
            });

            return $this->jsonResponse->successResponseWithData(
                'Liste des catégories de compétition récupérée avec succès',
                [
                    'categorieCompetitionList' => $categorieCompetitionListFormatted,
                    'paginations' => [
                        'total' => $categorieCompetitionList->total(),
                        'per_page' => $categorieCompetitionList->perPage(),
                        'current_page' => $categorieCompetitionList->currentPage(),
                        'last_page' => $categorieCompetitionList->lastPage(),
                        'from' => $categorieCompetitionList->firstItem(),
                        'to' => $categorieCompetitionList->lastItem()
                    ]
                ],
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la récupération de la liste des catégories de compétition', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la récupération de la liste des catégories de compétition',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_getCategorieCompetitionFormData(): JsonResponse
    {
        try {
            $trancheAgeList = $this->trancheAgeModel->get();
            return $this->jsonResponse->successResponseWithData(
                'Liste des catégories de compétition récupérée avec succès',
                $trancheAgeList,
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la récupération de la liste des catégories de compétition', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la récupération de la liste des catégories de compétition',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createCategorieCompetition(Request $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $categorieCompetitionCodeUnique = $this->codeGenerator->generateDefaultCodeUnique(
                    'categorie_competition_models',
                    'categorie_code_unique',
                    'CCP'
                );

                $categorieCompetitionCreated = $this->model->create([
                    'uuid' => Str::uuid(),
                    'categorie_code_unique' => $categorieCompetitionCodeUnique,
                    'tranche_code_unique' => $request->tranche_code_unique,
                    'categorie_name' => $request->categorie_name,
                    'categorie_code' => strtoupper(Str::random(6)),
                    'code_validity' => now()->addDays(7)->format('Y-m-d H:i:s'),
                ]);

                // log categorie competition creation
                $this->userLog->srv_createUserLog(
                    'create',
                    sprintf(
                        "Categorie competition %s créé avec succès par %s à %s",
                        $categorieCompetitionCreated->categorie_name,
                        auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                        now()->format('Y-m-d H:i:s')
                    )
                );

                return $this->jsonResponse->successResponseWithData(
                    'Categorie competition créé avec succès',
                    $categorieCompetitionCreated,
                    Response::HTTP_CREATED
                );
            });
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la création de la catégorie de compétition', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la création de la catégorie de compétition',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateCategorieCompetition(Request $request, string $uuid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $uuid) {
            $categorieCompetition = $this->model->where('uuid', $uuid)->firstOrFail();

            $categorieCompetition->update([
                'categorie_name' => $request->categorie_name,
                'tranche_code_unique' => $request->tranche_code_unique,
            ]);

            // log categorie competition update
            $this->userLog->srv_createUserLog(
                'update',
                sprintf(
                    "Categorie competition %s modifié avec succès par %s à %s",
                    $categorieCompetition->categorie_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponse->successResponseWithData(
                'Categorie competition modifié avec succès',
                $categorieCompetition,
                Response::HTTP_OK
            );
        });
        } catch (ModelNotFoundException $e) {
            return $this->jsonResponse->errorResponse(
                'Categorie competition non trouvée',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la modification de la catégorie de compétition', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la modification de la catégorie de compétition',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteCategorieCompetition(string $uuid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($uuid) {
            $categorieCompetition = $this->model->where('uuid', $uuid)->firstOrFail();

            $categorieCompetition->delete();

            // log categorie competition delete
            $this->userLog->srv_createUserLog(
                'delete',
                sprintf(
                    "Categorie competition %s supprimé avec succès par %s à %s",
                    $categorieCompetition->categorie_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponse->successResponseWithData(
                'Categorie competition supprimé avec succès',
                $categorieCompetition,
                Response::HTTP_OK
            );
        });
        } catch (ModelNotFoundException $e) {
            return $this->jsonResponse->errorResponse(
                'Categorie competition non trouvée',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la suppression de la catégorie de compétition', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la suppression de la catégorie de compétition',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_restoreCategorieCompetition(string $uuid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($uuid) {
            $categorieCompetition = $this->model->withTrashed()->where('uuid', $uuid)->firstOrFail();

            $categorieCompetition->restore();

            // log categorie competition restore
            $this->userLog->srv_createUserLog(
                'restore',
                sprintf(
                    "Categorie competition %s restauré avec succès par %s à %s",
                    $categorieCompetition->categorie_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponse->successResponseWithData(
                'Categorie competition restauré avec succès',
                $categorieCompetition,
                Response::HTTP_OK
            );
        });
        } catch (ModelNotFoundException $e) {
            return $this->jsonResponse->errorResponse(
                'Categorie competition non trouvée',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la restauration de la catégorie de compétition', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la restauration de la catégorie de compétition',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_forceDeleteCategorieCompetition(string $uuid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($uuid) {
            $categorieCompetition = $this->model->withTrashed()->where('uuid', $uuid)->firstOrFail();

            $categorieCompetition->forceDelete();

            // log categorie competition force delete
            $this->userLog->srv_createUserLog(
                'force_delete',
                sprintf(
                    "Categorie competition %s supprimé avec succès par %s à %s",
                    $categorieCompetition->categorie_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponse->successResponseWithData(
                'Categorie competition supprimé avec succès',
                $categorieCompetition,
                Response::HTTP_OK
            );
        });
        } catch (ModelNotFoundException $e) {
            return $this->jsonResponse->errorResponse(
                'Categorie competition non trouvée',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la suppression de la catégorie de compétition', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la suppression de la catégorie de compétition',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_refreshCategorieCompetitionCodeValidity(string $uuid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($uuid) {

                $validity = $this->validityModel->firstOrFail();

                // add validity to a now() + validity
                $validityDate = now()->addDays($validity->validity)->format('Y-m-d H:i:s');

                $categorieCompetition = $this->model->withTrashed()->where('uuid', $uuid)->firstOrFail();

                $categorieCompetition->update([
                    'code_validity' => $validityDate,
                ]);

                // log categorie competition refresh code validity
                $this->userLog->srv_createUserLog(
                'refresh_code_validity',
                sprintf(
                    "Categorie competition %s code validity refreshé avec succès par %s à %s",
                    $categorieCompetition->categorie_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponse->successResponseWithData(
                'Categorie competition code validity refreshé avec succès',
                $categorieCompetition,
                Response::HTTP_OK
            );
        });
        } catch (ModelNotFoundException $e) {
            return $this->jsonResponse->errorResponse(
                'Categorie competition non trouvée',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la suppression de la catégorie de compétition', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la suppression de la catégorie de compétition',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
