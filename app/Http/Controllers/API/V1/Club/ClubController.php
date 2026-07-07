<?php

namespace App\Http\Controllers\API\V1\Club;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\ClubServices\ClubService;


class ClubController extends Controller
{
    public function __construct(
        private readonly ClubService $clubService
    ){}

    public function ctrl_getClubList(Request $request): JsonResponse
    {
        return $this->clubService->srv_getClubList($request);
    }

    public function ctrl_storeClub(Request $request): JsonResponse
    {
        return $this->clubService->srv_createClub($request);
    }

    public function ctrl_updateClub(Request $request, $slug): JsonResponse
    {
        return $this->clubService->srv_updateClub($request, $slug);
    }

    public function ctrl_destroyClub($slug): JsonResponse
    {
        return $this->clubService->srv_deleteClub($slug);
    }

    public function ctrl_refreshClubCode($slug): JsonResponse
    {
        return $this->clubService->srv_refreshClubCode($slug);
    }
}

