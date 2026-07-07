<?php

namespace App\Http\Controllers\API\V1\Competitions;

use Illuminate\Http\Request;
use App\Services\CompetitionServices\CompetitionService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;


class CompetitionController extends Controller
{
    public function __construct(
        private readonly CompetitionService $competitionService
    ){}

    public function ctrl_getCompetitionList(Request $request): JsonResponse
    {
        return $this->competitionService->srv_getCompetitionList($request);
    }

    public function ctrl_storeCompetition(Request $request): JsonResponse
    {
        return $this->competitionService->srv_createCompetition($request);
    }

    public function ctrl_updateCompetition(Request $request, string $uuid): JsonResponse
    {
        return $this->competitionService->srv_updateCompetition($request, $uuid);
    }

    public function ctrl_destroyCompetition(string $uuid): JsonResponse
    {
        return $this->competitionService->srv_deleteCompetition($uuid);
    }

    public function ctrl_forceDeleteCompetition(string $uuid): JsonResponse
    {
        return $this->competitionService->srv_forceDeleteCompetition($uuid);
    }

    public function ctrl_restoreCompetition(string $uuid): JsonResponse
    {
        return $this->competitionService->srv_restoreCompetition($uuid);
    }
}
