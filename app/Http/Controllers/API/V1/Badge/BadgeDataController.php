<?php

namespace App\Http\Controllers\API\V1\Badge;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BadgeServices\BadgeDataService;
use Illuminate\Http\JsonResponse;

class BadgeDataController extends Controller
{
    public function __construct(
        private readonly BadgeDataService $badgeDataService,
    ) {
    }

    public function ctrl_getBadgeDataList(Request $request): JsonResponse
    {
        return $this->badgeDataService->srv_getAllBadgeData($request);
    }

    public function ctrl_getTableData(): JsonResponse
    {
        return $this->badgeDataService->srv_getTableData();
    }

    
    public function ctrl_getBadgeDataListFilter(Request $request): JsonResponse
    {
        return $this->badgeDataService->srv_getBadgeDataListFilter($request);
    }

    public function ctrl_getBadgeDataDetail(string $uuid): JsonResponse
    {
        return $this->badgeDataService->srv_getBadgeDataDetail($uuid);
    }

    public function ctrl_updateBadgeData(Request $request, string $uuid): JsonResponse
    {
        return $this->badgeDataService->srv_updateBadgeData($request, $uuid);
    }

    public function ctrl_updateBadgeDataZoneAccess(Request $request, string $uuid): JsonResponse
    {
        return $this->badgeDataService->srv_updateBadgeDataZoneAccess($request, $uuid);
    }

    public function ctrl_deleteBadgeData(string $uuid): JsonResponse
    {
        return $this->badgeDataService->srv_deleteBadgeData($uuid);
    }
}
