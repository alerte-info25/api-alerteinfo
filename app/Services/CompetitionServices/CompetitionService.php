<?php

namespace App\Services\CompetitionServices;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Competition\CompetitionModel;
use Symfony\Component\HttpFoundation\Response;
use App\Services\UserLogServices\UserLogService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;
use App\Models\CodeValidity\OrganisationCodeValidityModel;

class CompetitionService
{
    public function __construct(
        private readonly JsonResponseService $jsonResponse,
        private readonly CustomLogError $logError,
        private readonly CodeGeneratorService $codeGenerator,
        private readonly CompetitionModel $model,
        private readonly UserLogService $userLog,
        private readonly OrganisationCodeValidityModel $validityModel
    ){}

    public function srv_getCompetitionList(Request $request): JsonResponse
    {
        try {
            // pagination parameters
            $perPage = $request->input('per_page', 1);
            $page = $request->input('page', 20);

            // get competition list
            $competitionList = $this->model
            ->withTrashed()
            ->with('epreuves')
            ->paginate($perPage, ['*'], 'page', $page);
            $competitionListFormatted = $competitionList->getCollection()->map(function ($competition) {
                return [
                    'uuid' => $competition->uuid,
                    'epreuves' => $competition->epreuves,
                    'competition_code_unique' => $competition->competition_code_unique,
                    'competition_name' => $competition->competition_name,
                    'competition_code' => $competition->competition_code,
                    'code_validity' => $competition->code_validity,
                    'created_at' => $competition->created_at,
                    'updated_at' => $competition->updated_at,
                    'deleted_at' => $competition->deleted_at
                ];
            });

            return $this->jsonResponse->successResponseWithData(
                'Liste des compétitions récupérée avec succès',
                [
                    'competitionList' => $competitionListFormatted,
                    'paginations' => [
                        'total' => $competitionList->total(),
                        'per_page' => $competitionList->perPage(),
                        'current_page' => $competitionList->currentPage(),
                        'last_page' => $competitionList->lastPage(),
                        'from' => $competitionList->firstItem(),
                        'to' => $competitionList->lastItem()
                    ]
                ],
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la récupération de la liste des compétitions', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la récupération de la liste des compétitions',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createCompetition(Request $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $competitionCodeUnique = $this->codeGenerator->generateDefaultCodeUnique(
                    'competition_models',
                    'competition_code_unique',
                    'CP'
                );

                $competitionCreated = $this->model->create([
                    'uuid' => Str::uuid(),
                    'competition_code_unique' => $competitionCodeUnique,
                    'competition_name' => $request->competition_name,
                    'competition_code' => strtoupper(Str::random(6)),
                    'code_validity' => now()->addDays(7)->format('Y-m-d H:i:s'),
                ]);

                // log competition creation
                $this->userLog->srv_createUserLog(
                    'create',
                    sprintf(
                        "Competition %s créé avec succès par %s à %s",
                        $competitionCreated->competition_name,
                        auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                        now()->format('Y-m-d H:i:s')
                    )
                );

                return $this->jsonResponse->successResponseWithData(
                    'Competition créé avec succès',
                    $competitionCreated,
                    Response::HTTP_CREATED
                );
            });


        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la création de la compétition', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la création de la compétition',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateCompetition(Request $request, string $uuid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $uuid) {
            $competition = $this->model->where('uuid', $uuid)->firstOrFail();

            $competition->update([
                'competition_name' => $request->competition_name,
            ]);

            // log competition update
            $this->userLog->srv_createUserLog(
                'update',
                sprintf(
                    "Competition %s modifié avec succès par %s à %s",
                    $competition->competition_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponse->successResponseWithData(
                'Competition modifié avec succès',
                $competition,
                Response::HTTP_OK
            );
        });
        } catch (ModelNotFoundException $e) {
            return $this->jsonResponse->errorResponse(
                'Competition non trouvée',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la modification de la compétition', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la modification de la compétition',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteCompetition(string $uuid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($uuid) {
            $competition = $this->model->where('uuid', $uuid)->firstOrFail();

            $competition->delete();

            // log competition delete
            $this->userLog->srv_createUserLog(
                'delete',
                sprintf(
                    "Competition %s supprimé avec succès par %s à %s",
                    $competition->competition_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponse->successResponseWithData(
                'Competition supprimé avec succès',
                $competition,
                Response::HTTP_OK
            );
        });
        } catch (ModelNotFoundException $e) {
            return $this->jsonResponse->errorResponse(
                'Competition non trouvée',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la suppression de la compétition', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la suppression de la compétition',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_restoreCompetition(string $uuid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($uuid) {
            $competition = $this->model->withTrashed()->where('uuid', $uuid)->firstOrFail();

            $competition->restore();

            // log competition restore
            $this->userLog->srv_createUserLog(
                'restore',
                sprintf(
                    "Competition %s restauré avec succès par %s à %s",
                    $competition->competition_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponse->successResponseWithData(
                'Competition restauré avec succès',
                $competition,
                Response::HTTP_OK
            );
        });
        } catch (ModelNotFoundException $e) {
            return $this->jsonResponse->errorResponse(
                'Competition non trouvée',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la restauration de la compétition', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la restauration de la compétition',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_forceDeleteCompetition(string $uuid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($uuid) {
            $competition = $this->model->withTrashed()->where('uuid', $uuid)->firstOrFail();

            $competition->forceDelete();

            // log competition force delete
            $this->userLog->srv_createUserLog(
                'force_delete',
                sprintf(
                    "Competition %s supprimé avec succès par %s à %s",
                    $competition->competition_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponse->successResponseWithData(
                'Competition supprimé avec succès',
                $competition,
                Response::HTTP_OK
            );
        });
        } catch (ModelNotFoundException $e) {
            return $this->jsonResponse->errorResponse(
                'Competition non trouvée',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la suppression de la compétition', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la suppression de la compétition',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_refreshCompetitionCodeValidity(string $uuid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($uuid) {

                $validity = $this->validityModel->firstOrFail();

                // add validity to a now() + validity
                $validityDate = now()->addDays($validity->validity)->format('Y-m-d H:i:s');

                $competition = $this->model->withTrashed()->where('uuid', $uuid)->firstOrFail();

                $competition->update([
                    'code_validity' => $validityDate,
                ]);

                // log competition refresh code validity
                $this->userLog->srv_createUserLog(
                'refresh_code_validity',
                sprintf(
                    "Competition %s code validity refreshé avec succès par %s à %s",
                    $competition->competition_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponse->successResponseWithData(
                'Competition code validity refreshé avec succès',
                $competition,
                Response::HTTP_OK
            );
        });
        } catch (ModelNotFoundException $e) {
            return $this->jsonResponse->errorResponse(
                'Competition non trouvée',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la refresh de la compétition', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la refresh de la compétition',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
