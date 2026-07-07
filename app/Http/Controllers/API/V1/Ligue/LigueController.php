<?php

namespace App\Http\Controllers\API\V1\Ligue;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\LigueServices\LigueService;


class LigueController extends Controller
{
    public function __construct(
        private readonly LigueService $ligueService
    ){}

    public function ctrl_getLigueList(Request $request): JsonResponse
    {
        return $this->ligueService->srv_getLigueList($request);
    }

    public function ctrl_storeLigue(Request $request): JsonResponse
    {
        return $this->ligueService->srv_createLigue($request);
    }

    public function ctrl_updateLigue(Request $request, $slug): JsonResponse
    {
        return $this->ligueService->srv_updateLigue($request, $slug);
    }

    public function ctrl_deleteLigue($slug): JsonResponse
    {
        return $this->ligueService->srv_deleteLigue($slug);
    }

    public function ctrl_refreshLigueCode($slug): JsonResponse
    {
        return $this->ligueService->srv_refreshLigueCode($slug);
    }
}

