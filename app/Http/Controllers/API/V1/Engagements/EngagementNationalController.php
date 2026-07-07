<?php

namespace App\Http\Controllers\API\V1\Engagements;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\EngagementServices\EngagementNationalService;

class EngagementNationalController extends Controller
{
    public function __construct(
        private readonly EngagementNationalService $engagementNationalService,
    ) {}

    public function ctrl_getEngagementNationalList(Request $request): JsonResponse
    {
        return $this->engagementNationalService->srv_getEngagementNationalList($request);
    }

    

    public function ctrl_getEngagementNationalDetails(string $uuid): JsonResponse
    {
        return $this->engagementNationalService->srv_getEngagementNationalDetails($uuid);
    }

    public function ctrl_getEngagementNationalListFilter(Request $request): JsonResponse
    {
        return $this->engagementNationalService->srv_getEngagementNationalListFilter($request);
    }
}
