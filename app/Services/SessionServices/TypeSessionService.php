<?php

namespace App\Services\SessionServices;

use Exception;
use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Sessions\TypeSessionModel;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;

class TypeSessionService
{
    public function __construct(
        private readonly JsonResponseService $jsonResponseService,
        private readonly TypeSessionModel $typeSessionModel,
        private readonly CustomLogError $customLogError,
        private readonly CodeGeneratorService $codeGeneratorService,
    ) {}

    public function srv_getTypeSession(Request $request): JsonResponse
    {
        try {
            // pagination parameters
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);

            $typeSessions = $this->typeSessionModel->paginate($limit, ['*'], 'page', $page);

            $typeSessionsFormatted = $typeSessions->getCollection()->map(function ($typeSession) {
                return [
                    'id' => $typeSession->id,
                    'type_session_code_unique' => $typeSession->type_session_code_unique,
                    'type_session' => $typeSession->type_session,
                    'slug' => $typeSession->slug,
                    'created_at' => $typeSession->created_at,
                ];
            });
            return $this->jsonResponseService->successResponseWithData(
                'Type de sessions récupérées avec succès',
                [
                    'typeSessions' => $typeSessionsFormatted,
                    'paginations' => [
                        'total' => $typeSessions->total(),
                        'per_page' => $typeSessions->perPage(),
                        'current_page' => $typeSessions->currentPage(),
                        'last_page' => $typeSessions->lastPage(),
                        'from' => $typeSessions->firstItem(),
                        'to' => $typeSessions->lastItem()
                    ]
                ],
                Response::HTTP_OK
            );
        } catch (Exception $q) {
            // Journalisation sécurisée de l'erreur type session
            $this->customLogError->logError("Erreur lors de la récupération des types de sessions : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la récupération des types de sessions.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    public function srv_createTypeSession(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $typeSessionCodeUnique = $this->codeGeneratorService->generateDefaultCodeUnique(
                'type_session_models',
                'type_session_code_unique',
                'TYPE'
            );
            $typeSession = $this->typeSessionModel->create([
                'type_session_code_unique' => $typeSessionCodeUnique,
                'type_session' => $request->type_session,
                'slug' => Str::uuid(),
            ]);
            DB::commit();
            return $this->jsonResponseService->successResponseWithData(
                'Type de session créé avec succès',
                $typeSession,
                Response::HTTP_CREATED
            );
        } catch (Exception $q) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur type session
            $this->customLogError->logError("Erreur lors de la création d'un type de session : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la création d'un type de session.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    public function srv_updateTypeSession(Request $request, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $typeSession = $this->typeSessionModel->where('slug', $slug)->firstOrFail();
            $typeSession->update([
                'type_session' => $request->type_session,
            ]);
            DB::commit();
            return $this->jsonResponseService->successResponseWithData(
                'Type de session mis à jour avec succès',
                $typeSession,
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $q) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur type session
            $this->customLogError->logError("Erreur lors de la mise à jour d'un type de session : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la mise à jour d'un type de session.", // Message générique
                Response::HTTP_NOT_FOUND, // Code HTTP d'erreur interne
            );
        }
        catch (Exception $q) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur type session
            $this->customLogError->logError("Erreur lors de la mise à jour d'un type de session : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la mise à jour d'un type de session.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    public function srv_destroyTypeSession($slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $typeSession = $this->typeSessionModel->where('slug', $slug)->firstOrFail();
            $typeSession->delete();
            DB::commit();
            return $this->jsonResponseService->successResponse(
                'Type de session supprimé avec succès',
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $q) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur type session
            $this->customLogError->logError("Erreur lors de la suppression d'un type de session : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la suppression d'un type de session.", // Message générique
                Response::HTTP_NOT_FOUND, // Code HTTP d'erreur interne
            );
        }
        catch (Exception $q) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur type session
            $this->customLogError->logError("Erreur lors de la suppression d'un type de session : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la suppression d'un type de session.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }
}
