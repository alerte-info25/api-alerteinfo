<?php

namespace App\Http\Controllers\API\V1\Competitions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\CompetitionServices\CategorieCompetitionService;

class CategorieCompetitionController extends Controller
{
    public function __construct(
        private readonly CategorieCompetitionService $categorieCompetitionService
    ){}

    public function ctrl_getCategorieCompetitionList(Request $request): JsonResponse
    {
        return $this->categorieCompetitionService->srv_getCategorieCompetitionList($request);
    }

    public function ctrl_getCategorieCompetitionFormData(): JsonResponse
    {
        return $this->categorieCompetitionService->srv_getCategorieCompetitionFormData();
    }

    public function ctrl_storeCategorieCompetition(Request $request): JsonResponse
    {
        return $this->categorieCompetitionService->srv_createCategorieCompetition($request);
    }

    public function ctrl_updateCategorieCompetition(Request $request, string $uuid): JsonResponse
    {
        return $this->categorieCompetitionService->srv_updateCategorieCompetition($request, $uuid);
    }

    public function ctrl_destroyCategorieCompetition(string $uuid): JsonResponse
    {
        return $this->categorieCompetitionService->srv_deleteCategorieCompetition($uuid);
    }

    public function ctrl_forceDeleteCategorieCompetition(string $uuid): JsonResponse
    {
        return $this->categorieCompetitionService->srv_forceDeleteCategorieCompetition($uuid);
    }

    public function ctrl_restoreCategorieCompetition(string $uuid): JsonResponse
    {
        return $this->categorieCompetitionService->srv_restoreCategorieCompetition($uuid);
    }
}
