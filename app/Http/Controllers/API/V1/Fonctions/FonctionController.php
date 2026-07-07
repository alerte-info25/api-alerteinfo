<?php

namespace App\Http\Controllers\API\V1\Fonctions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\FonctionServices\FonctionService;


class FonctionController extends Controller
{
    public function __construct(
        private readonly FonctionService $fonctionService
    ){}

    public function ctrl_getFonctionList(Request $request): JsonResponse
    {
        return $this->fonctionService->srv_getFonctionList($request);
    }

    public function ctrl_getFonctionFormData(): JsonResponse
    {
        return $this->fonctionService->srv_getFonctionFormData();
    }

    public function ctrl_storeFonction(Request $request): JsonResponse
    {
        return $this->fonctionService->srv_createFonction($request);
    }

    public function ctrl_updateFonction(Request $request, $slug): JsonResponse
    {
        return $this->fonctionService->srv_updateFonction($request, $slug);
    }

    public function ctrl_destroyFonction($slug): JsonResponse
    {
        return $this->fonctionService->srv_deleteFonction($slug);
    }
}

