<?php

namespace App\Services\EpeuveServices;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Epreuve\EpreuveModel;
use App\Models\Competition\CompetitionModel;
use Symfony\Component\HttpFoundation\Response;
use App\Services\UserLogServices\UserLogService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;

class EpeuveService
{
    public function __construct(
        private readonly JsonResponseService $jsonResponse,
        private readonly CustomLogError $logError,
        private readonly CodeGeneratorService $codeGenerator,
        private readonly EpreuveModel $model,
        private readonly CompetitionModel $competitionModel,
        private readonly UserLogService $userLog
    ){}


    public function srv_getEpeuveList(Request $request): JsonResponse
    {
        try {
            // pagination parameters
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            // get epeuve list
            $epreuveList = $this->model->withTrashed()
            ->with('competition')
            ->paginate($perPage, ['*'], 'page', $page);
            $epreuveListFormatted = $epreuveList->getCollection()->map(function ($epreuve) {
                return [
                    'epreuve_code_unique' => $epreuve->epreuve_code_unique,
                    'competition_code_unique' => $epreuve->competition_code_unique,
                    'epreuve_name' => $epreuve->epreuve_name,
                    'competition' => $epreuve->competition->competition_name,
                    'uuid' => $epreuve->uuid
                ];
            });

            return $this->jsonResponse->successResponseWithData(
                'Liste des epeuves récupérée avec succès',
                [
                    'epreuveList' => $epreuveListFormatted,
                    'paginations' => [
                        'total' => $epreuveList->total(),
                        'per_page' => $epreuveList->perPage(),
                        'current_page' => $epreuveList->currentPage(),
                        'last_page' => $epreuveList->lastPage(),
                        'from' => $epreuveList->firstItem(),
                        'to' => $epreuveList->lastItem()
                    ]
                ],
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la récupération de la liste des epeuves', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la récupération de la liste des epeuves',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_getEpeuveFormData(): JsonResponse
    {
        try {
            // get epeuve form data
            $epeuveFormData = $this->competitionModel->get();
            return $this->jsonResponse->successResponseWithData(
                'Données de l\'epeuve récupérées avec succès',
                $epeuveFormData,
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la récupération des données de l\'epeuve', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la récupération des données de l\'epeuve',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createEpeuve(Request $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $epreuveCodeUnique = $this->codeGenerator->generateDefaultCodeUnique(
                    'epreuve_models',
                    'epreuve_code_unique',
                    'EP'
                );

                $epreuveCreated = $this->model->create([
                    'uuid' => Str::uuid(),
                    'epreuve_code_unique' => $epreuveCodeUnique,
                    'epreuve_name' => $request->epreuve_name,
                    'competition_code_unique' => $request->competition_code_unique,
                    //'epreuve_code' => strtoupper(Str::random(6)),
                    //'code_validity' => now()->addDays(7)->format('Y-m-d H:i:s'),
                ]);

                // log epeuve creation
                $this->userLog->srv_createUserLog(
                    'create',
                    sprintf(
                        "Epeuve %s créé avec succès par %s à %s",
                        $epreuveCreated->epreuve_name,
                        auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                        now()->format('Y-m-d H:i:s')
                    )
                );

                return $this->jsonResponse->successResponseWithData(
                    'Epeuve créé avec succès',
                    $epreuveCreated,
                    Response::HTTP_CREATED
                );
            });


        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la création de l\'epeuve', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la création de l\'epeuve',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateEpeuve(Request $request, string $uuid): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $uuid) {
            $epreuve = $this->model->where('uuid', $uuid)->firstOrFail();

            $epreuve->update([
                'epreuve_name' => $request->epreuve_name,
                'competition_code_unique' => $request->competition_code_unique,
                //'epeuve_code' => $request->epeuve_code,
                //'code_validity' => $request->code_validity,
            ]);

            // log epeuve update
            $this->userLog->srv_createUserLog(
                'update',
                sprintf(
                    "Epeuve %s modifié avec succès par %s à %s",
                    $epreuve->epreuve_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponse->successResponseWithData(
                'Epeuve modifié avec succès',
                $epreuve,
                Response::HTTP_OK
            );
        });
        } catch (ModelNotFoundException $e) {
            return $this->jsonResponse->errorResponse(
                'Epeuve non trouvée',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la modification de l\'epeuve', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la modification de l\'epeuve',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteEpeuve(string $uuid): JsonResponse
    {
        try {
            $epeuve = $this->model->where('uuid', $uuid)->firstOrFail();

            $epeuve->delete();

            // log epeuve delete
            $this->userLog->srv_createUserLog(
                'delete',
                sprintf(
                    "Epeuve %s supprimé avec succès par %s à %s",
                    $epeuve->epeuve_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponse->successResponse(
                'Epeuve supprimé avec succès',
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $e) {
            return $this->jsonResponse->errorResponse(
                'Epeuve non trouvée',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la suppression de l\'epeuve', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la suppression de l\'epeuve',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_restoreEpeuve(string $uuid): JsonResponse
    {
        try {
            $epeuve = $this->model->withTrashed()->where('uuid', $uuid)->firstOrFail();

            $epeuve->restore();

            // log epeuve restore
            $this->userLog->srv_createUserLog(
                'restore',
                sprintf(
                    "Epeuve %s restauré avec succès par %s à %s",
                    $epeuve->epeuve_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponse->successResponse(
                'Epeuve restauré avec succès',
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $e) {
            return $this->jsonResponse->errorResponse(
                'Epeuve non trouvée',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la restauration de l\'epeuve', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la restauration de l\'epeuve',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_forceDeleteEpeuve(string $uuid): JsonResponse
    {
        try {
            $epeuve = $this->model->withTrashed()->where('uuid', $uuid)->firstOrFail();

            $epeuve->forceDelete();

            // log epeuve force delete
            $this->userLog->srv_createUserLog(
                'force delete',
                sprintf(
                    "Epeuve %s forcément supprimé avec succès par %s à %s",
                    $epeuve->epeuve_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponse->successResponse(
                'Epeuve forcément supprimé avec succès',
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $e) {
            return $this->jsonResponse->errorResponse(
                'Epeuve non trouvée',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la suppression forcé de l\'epeuve', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la suppression forcé de l\'epeuve',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
