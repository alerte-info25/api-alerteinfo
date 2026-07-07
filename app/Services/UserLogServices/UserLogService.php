<?php

namespace App\Services\UserLogServices;

use App\Logs\CustomLogError;
use App\Models\UserLogs\UserLogModel;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JsonResponseServices\JsonResponseService;

class UserLogService
{
    /**
     * @var JsonResponseService
     */
    private $jsonResponseService;

    /**
     * @var CustomLogError
     */
    private $customLogError;
    public function __construct(
        JsonResponseService $jsonResponseService,
        CustomLogError $customLogError,
    ) {
        $this->jsonResponseService = $jsonResponseService;
        $this->customLogError = $customLogError;
    }

    public function srv_createUserLog($action, $description)
    {
        if (!auth('admin')->check()) {
            return $this->jsonResponseService->errorResponse(
                "Vous devez être connecté pour effectuer cette action", // Message générique
                Response::HTTP_UNAUTHORIZED, // Code HTTP d'erreur interne
            );
        }
        $account_code_unique = auth('admin')->user()->account_code_unique;


        try {
            if (empty($action) || empty($description)) {
                return $this->jsonResponseService->errorResponse(
                    "Les paramètres action et description sont obligatoires", // Message générique
                    Response::HTTP_BAD_REQUEST, // Code HTTP d'erreur interne
                );
            }

            $userLog = UserLogModel::create([
                'account_code_unique' => $account_code_unique,
                'action' => $action,
                'description' => $description,
            ]);

            //$this->customLogError->logInfo('Log utilisateur créé avec succès', $userLog);

        } catch (\Throwable $th) {
            // Log de l'erreur
            $this->customLogError->logError('Erreur lors de la création du log utilisateur', $th);

        }
    }
}

