<?php

namespace App\Http\Controllers\API\V1\Badge;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\BadgeServices\BadgeService;
use Illuminate\Http\JsonResponse;

class BadgeController extends Controller
{
    public function __construct(
        private readonly BadgeService $badgeService,
    ) {
    }

    public function ctrl_getBadgeList(Request $request): JsonResponse
    {
        return $this->badgeService->srv_getAllBadges($request);
    }

    public function ctrl_getBadgeDetail(string $uuid): JsonResponse
    {
        return $this->badgeService->srv_getBadgeDetail($uuid);
    }

    public function ctrl_createBadge(Request $request): JsonResponse
    {
        return $this->badgeService->srv_createBadge($request);
    }

    public function ctrl_updateBadge(Request $request, string $uuid): JsonResponse
    {
        return $this->badgeService->srv_updateBadge($request, $uuid);
    }

    public function ctrl_deleteBadge(string $uuid): JsonResponse
    {
        return $this->badgeService->srv_deleteBadge($uuid);
    }
}
