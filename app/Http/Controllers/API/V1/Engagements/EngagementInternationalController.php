<?php

namespace App\Http\Controllers\API\V1\Engagements;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\EngagementServices\EngagementInternationalService;

class EngagementInternationalController extends Controller
{
    public function __construct(
        private readonly EngagementInternationalService $engagementInternationalService,
    ){}

    public function ctrl_getEngagementInternationalList(Request $request): JsonResponse
    {
        return $this->engagementInternationalService->srv_getEngagementInternationalList($request);
    }

    public function ctrl_getTableData(): JsonResponse
    {
        return $this->engagementInternationalService->srv_getTableData();
    }   

    public function ctrl_getEngagementInternationalDetails(string $uuid): JsonResponse
    {
        return $this->engagementInternationalService->srv_getEngagementInternationalDetails($uuid);
    }

    public function ctrl_getEngagementInternationalListFilter(Request $request): JsonResponse
    {
        return $this->engagementInternationalService->srv_getEngagementInternationalListFilter($request);
    }


}
