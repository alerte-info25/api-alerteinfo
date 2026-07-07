<?php

namespace App\Http\Controllers\API\V1\Sessions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\SessionServices\SessionService;


class SessionController extends Controller
{
    public function __construct(
        private readonly SessionService $sessionService
    ) {}

    public function ctrl_getSessions(Request $request): JsonResponse
    {
        return $this->sessionService->srv_getSession($request);
    }

    public function ctrl_getSessionFormData(): JsonResponse
    {
        return $this->sessionService->getSessionFormData();
    }

    public function ctrl_storeSession(Request $request): JsonResponse
    {
        return $this->sessionService->srv_createSession($request);
    }

    public function ctrl_updateSession(Request $request, $slug): JsonResponse
    {
        return $this->sessionService->srv_updateSession($request, $slug);
    }

    public function ctrl_destroySession($slug): JsonResponse
    {
        return $this->sessionService->srv_destroySession($slug);
    }
}

