<?php

namespace App\Http\Controllers\API\V1\Epreuve;

use Illuminate\Http\Request;
use App\Services\EpeuveServices\EpeuveService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;


class EpreuveController extends Controller
{
    public function __construct(
        private readonly EpeuveService $epreuveService
    ){}

    public function ctrl_getEpreuveList(Request $request): JsonResponse
    {
        return $this->epreuveService->srv_getEpeuveList($request);
    }

    public function ctrl_getEpreuveFormData(): JsonResponse
    {
        return $this->epreuveService->srv_getEpeuveFormData();
    }

    public function ctrl_storeEpreuve(Request $request): JsonResponse
    {
        return $this->epreuveService->srv_createEpeuve($request);
    }

    public function ctrl_updateEpreuve(Request $request, string $uuid): JsonResponse
    {
        return $this->epreuveService->srv_updateEpeuve($request, $uuid);
    }

    public function ctrl_destroyEpreuve(string $uuid): JsonResponse
    {
        return $this->epreuveService->srv_deleteEpeuve($uuid);
    }

    public function ctrl_forceDeleteEpeuve(string $uuid): JsonResponse
    {
        return $this->epreuveService->srv_forceDeleteEpeuve($uuid);
    }

    public function ctrl_restoreEpeuve(string $uuid): JsonResponse
    {
        return $this->epreuveService->srv_restoreEpeuve($uuid);
    }
}
