<?php

namespace App\Http\Controllers\API\V1\Sessions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\SessionServices\TypeSessionService;

class TypeSessionController extends Controller
{
    public function __construct(
        private readonly TypeSessionService $typeSessionService
    ) {}

    public function ctrl_getTypeSessions(Request $request): JsonResponse
    {
        return $this->typeSessionService->srv_getTypeSession($request);
    }

    public function ctrl_storeTypeSession(Request $request): JsonResponse
    {
        return $this->typeSessionService->srv_createTypeSession($request);
    }

    public function ctrl_updateTypeSession(Request $request, $slug): JsonResponse
    {
        return $this->typeSessionService->srv_updateTypeSession($request, $slug);
    }

    public function ctrl_destroyTypeSession($slug): JsonResponse
    {
        return $this->typeSessionService->srv_destroyTypeSession($slug);
    }
}

