<?php

namespace App\Http\Controllers\API\V1\Badge;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\BadgeServices\BadgeSponsorService;

class BadgeSponsorController extends Controller
{
    public function __construct(
        private readonly BadgeSponsorService $badgeSponsorService,
    ) {}

    public function ctrl_getBadgeSponsorsList(Request $request): JsonResponse
    {
        return $this->badgeSponsorService->srv_getBadgeSponsorsList($request);
    }

    

    public function ctrl_getBadgeSponsorsFormData(): JsonResponse
    {
        return $this->badgeSponsorService->srv_getBadgeSponsorsFormData();
    }
    
    public function ctrl_getBadgeSponsorsByBadgeCode(string $badgeCode): JsonResponse
    {
        return $this->badgeSponsorService->srv_getByBadgeCode($badgeCode);
    }

    public function ctrl_createBadgeSponsor(Request $request): JsonResponse
    {
        return $this->badgeSponsorService->srv_createBadgeSponsor($request);
    }

    public function ctrl_updateBadgeSponsor(Request $request, string $uuid): JsonResponse
    {
        return $this->badgeSponsorService->srv_updateBadgeSponsor($request, $uuid);
    }

    public function ctrl_deleteBadgeSponsor(string $uuid): JsonResponse
    {
        return $this->badgeSponsorService->srv_deleteBadgeSponsor($uuid);
    }
}
