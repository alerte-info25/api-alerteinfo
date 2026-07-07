<?php

namespace App\Http\Controllers\API\V1\Federation;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\FederationServices\FederationService;


class FederationController extends Controller
{
    public function __construct(
        private readonly FederationService $federationService
    ){}

    public function ctrl_getFederationList(Request $request): JsonResponse
    {
        return $this->federationService->srv_getFederationList($request);
    }

    public function ctrl_storeFederation(Request $request): JsonResponse
    {
        return $this->federationService->srv_createFederation($request);
    }

    public function ctrl_updateFederation(Request $request, $slug): JsonResponse
    {
        return $this->federationService->srv_updateFederation($request, $slug);
    }

    public function ctrl_destroyFederation($slug): JsonResponse
    {
        return $this->federationService->srv_deleteFederation($slug);
    }

    public function ctrl_refreshFederationCode($slug): JsonResponse
    {
        return $this->federationService->srv_refreshFederationCode($slug);
    }
}
