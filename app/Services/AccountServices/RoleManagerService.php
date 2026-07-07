<?php

namespace App\Services\AccountServices;

use Exception;
use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\UserLogServices\UserLogService;
use App\Models\UsersRoleManager\RoleManagerModels;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;

class RoleManagerService
{

    public function __construct(
        private readonly JsonResponseService $jsonResponseService,
        private readonly RoleManagerModels $roleManagerModels,
        private readonly UserLogService $userLogService,
        private readonly CustomLogError $customLogError,

    ) {}


    public function srv_getRole(Request $request): JsonResponse
    {
        try {
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);
            $roles = $this->roleManagerModels::paginate($perPage, ['*'], 'page', $page);
            
            $roleDataFormated = $roles->getCollection()->map(function ($role) {
                return [
                    'role' => $role->role,
                    'slug' => $role->slug,
                ];
            });
            return $this->jsonResponseService->successResponseWithData(
                'Rôles récupérés avec succès',
                [
                    'userRoles' => $roleDataFormated,
                    'paginations' => [
                        'total' => $roles->total(),
                        'per_page' => $roles->perPage(),
                        'current_page' => $roles->currentPage(),
                        'last_page' => $roles->lastPage(),
                        'from' => $roles->firstItem(),
                        'to' => $roles->lastItem()
                    ]
                ],
                Response::HTTP_OK
            );
        } catch (QueryException $q) {
            // Journalisation sécurisée de l'erreur role
            $this->customLogError->logError("Erreur de base de données lors de la récupération des rôles : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur de base de données lors de la récupération des rôles.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }
    
    public function srv_createRole(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            RoleManagerModels::create([
                'role' => $request->role, //
                'slug' => Str::uuid()
            ]);
            // Journalisation des actions
            $this->userLogService->srv_createUserLog(
                'create',
                "Création d'un rôle '{$request->role}'"
            );
            DB::commit();
            return $this->jsonResponseService->successResponse(
                'Rôle créé avec succès',
                Response::HTTP_CREATED
            );
        } catch (Exception $q) {
            // Journalisation sécurisée de l'erreur role
            Log::error("Erreur de base de données lors de la création du rôle : " . $q->getMessage(), [
                'file' => $q->getFile(),
                'line' => $q->getLine(),
            ]);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur de base de données lors de la création du rôle.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    public function srv_updateRole(Request $request, string $slug): JsonResponse
    {
        try {
            // check if role with slug already exists
            $role = RoleManagerModels::where('slug', $slug)->first();
            if (!$role) {
                return  $this->jsonResponseService->errorResponse(
                    "Le rôle avec le slug '$slug' n'existe pas.",
                    Response::HTTP_NOT_FOUND
                );
            }

            // update role
            RoleManagerModels::where('slug', $slug)->update([
                'role' => $request->role
            ]);
            // Journalisation des actions
            $this->userLogService->srv_createUserLog(
                'update',
                "Modification d'un rôle '{$request->role}'"
            );
            DB::commit();
            return $this->jsonResponseService->successResponse(
                'Rôle modifié avec succès',
                Response::HTTP_OK
            );

        }
        catch (Exception $q) {
            // Journalisation sécurisée de l'erreur role
            Log::error("Erreur de base de données lors de la modification du rôle : " . $q->getMessage(), [
                'file' => $q->getFile(),
                'line' => $q->getLine(),
            ]);
            return $this->jsonResponseService->errorResponse(
                "Erreur de base de données lors de la modification du rôle.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
        // Lancer une exception personnalisée pour masquer les détails sensibles
    }


    public function srv_deleteRole(string $slug): JsonResponse
    {
        try {
            // check if role with slug already exists
            $role = RoleManagerModels::where('slug', $slug)->first();
            if (!$role) {
                return  $this->jsonResponseService->errorResponse(
                    "Le rôle avec le slug '$slug' n'existe pas.",
                    Response::HTTP_NOT_FOUND
                );
            }

            // Journalisation des actions
            $this->userLogService->srv_createUserLog(
                'delete',
                "Suppression d'un rôle '{$role->role}'"
            );

            // delete role
            RoleManagerModels::where('slug', $slug)->delete();
            return $this->jsonResponseService->successResponse(
                'Rôle supprimé avec succès',
                Response::HTTP_OK
            );
        } catch (Exception $th) {
            // Handle exception
            Log::error("Erreur de base de données lors de la suppression du rôle : " . $th->getMessage(), [
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            return $this->jsonResponseService->errorResponse(
                "Erreur de base de données lors de la suppression du rôle.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }
}
