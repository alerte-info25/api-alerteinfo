<?php

namespace App\Services\SessionServices;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Sessions\SessionModel;
use App\Models\Sessions\TypeSessionModel;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;


class SessionService
{
    /**
     * @var JsonResponseService
     */
    private $jsonResponseService;

    /**
     * @var SessionModel
     */
    private $sessionModel;

    /**
     * @var TypeSessionModel
     */
    private $typeSessionModel;

    /**
     * @var CustomLogError
     */
    private $customLogError;

    /**
     * @var CodeGeneratorService
     */
    private $codeGeneratorService;
    public function  __construct(
        JsonResponseService $jsonResponseService,
        SessionModel $sessionModel,
        TypeSessionModel $typeSessionModel,
        CustomLogError $customLogError,
        CodeGeneratorService $codeGeneratorService,
    ) {
        $this->jsonResponseService = $jsonResponseService;
        $this->sessionModel = $sessionModel;
        $this->typeSessionModel = $typeSessionModel;
        $this->customLogError = $customLogError;
        $this->codeGeneratorService = $codeGeneratorService;
    }


    public function srv_getSession(Request $request): JsonResponse
    {
        try {

           // pagination parameters
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);


            $sessions = $this->sessionModel->with('typeSession')
            ->paginate($limit, ['*'], 'page', $page);

            $sessionsFormatted = $sessions->getCollection()->map(function ($session) {
                return [
                    'id' => $session->id,
                    'session_code_unique' => $session->session_code_unique,
                    'type_session_code_unique' => $session->typeSession->type_session_code_unique,
                    'type_session' => $session->typeSession->type_session,
                    'session_started_at' => $session->session_started_at,
                    'session_ended_at' => $session->session_ended_at,
                    'description' => $session->description,
                    'slug' => $session->slug,
                    'created_at' => $session->created_at,
                ];
            });

            return $this->jsonResponseService->successResponseWithData(
                'Sessions récupérées avec succès',
                [
                    'sessionsList' => $sessionsFormatted,
                    'paginations' => [
                        'total' => $sessions->total(),
                        'per_page' => $sessions->perPage(),
                        'current_page' => $sessions->currentPage(),
                        'last_page' => $sessions->lastPage(),
                        'from' => $sessions->firstItem(),
                        'to' => $sessions->lastItem()
                    ]
                ],
                Response::HTTP_OK
            );
        } catch (Exception $q) {
            // Journalisation sécurisée de l'erreur session
            $this->customLogError->logError("Erreur lors de la récupération des sessions : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la récupération des sessions.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    public function getSessionFormData(): JsonResponse
    {
        try {
            $typeSessions = $this->typeSessionModel->all();
            return $this->jsonResponseService->successResponseWithData(
                'Type sessions récupérées avec succès',
                $typeSessions,
                Response::HTTP_OK
            );
        } catch (Exception $q) {
            // Journalisation sécurisée de l'erreur session
            $this->customLogError->logError("Erreur lors de la récupération des type sessions : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la récupération des type sessions.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    public function srv_createSession(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $sessionCodeUnique = $this->codeGeneratorService->generateDefaultCodeUnique(
                'session_models',
                'session_code_unique',
                'SESS'
            );

            $session = $this->sessionModel::create([
                'session_code_unique' => $sessionCodeUnique,
                'type_session_code_unique' => $request->type_session_code_unique,
                'session_started_at' => Carbon::parse($request->session_started_at)->format('Y-m-d'),
                'session_ended_at' => Carbon::parse($request->session_ended_at)->format('Y-m-d'),
                'description' => $request->description,
                'slug' => Str::uuid(),
            ]);
            DB::commit();
            return $this->jsonResponseService->successResponseWithData(
                'Session créée avec succès',
                $session,
                Response::HTTP_CREATED
            );
        } catch (Exception $q) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur session
            $this->customLogError->logError("Erreur lors de la création de la session : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la création de la session.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    public function srv_updateSession(Request $request, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $session = $this->sessionModel::where('slug', $slug)->firstOrFail();
            $session->update([
                'type_session_code_unique' => $request->type_session_code_unique,
                'session_started_at' => Carbon::parse($request->session_started_at)->format('Y-m-d'),
                'session_ended_at' => Carbon::parse($request->session_ended_at)->format('Y-m-d'),
                'description' => $request->description,
            ]);
            DB::commit();
            return $this->jsonResponseService->successResponseWithData(
                'Session mise à jour avec succès',
                $session,
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $q) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur session
            $this->customLogError->logError("Erreur lors de la mise à jour de la session : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la mise à jour de la session.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
        catch (Exception $q) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur session
            $this->customLogError->logError("Erreur lors de la mise à jour de la session : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la mise à jour de la session.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    public function srv_destroySession($slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $session = $this->sessionModel::where('slug', $slug)->firstOrFail();
            $session->delete();
            DB::commit();
            return $this->jsonResponseService->successResponse(
                'Session supprimée avec succès',
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $q) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur session
            $this->customLogError->logError("Erreur lors de la suppression de la session : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la suppression de la session.", // Message générique
                Response::HTTP_NOT_FOUND, // Code HTTP d'erreur interne
            );
        }
        catch (Exception $q) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur session
            $this->customLogError->logError("Erreur lors de la suppression de la session : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la suppression de la session.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }
}

