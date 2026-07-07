<?php

namespace App\Http\Controllers\WebTvs;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\WebTvs\WebTvService;

class WebTvController extends Controller
{
    public function __construct(
        private readonly WebTvService $webTvService,
    ){}

    public function ctrl_getWebTv(Request $requestData): JsonResponse
    {
        return $this->webTvService->srv_getWebTv($requestData);
    }

    public function ctrl_getWebTvLimited(): JsonResponse
    {
        return $this->webTvService->srv_getWebTvLimited();
    }

    public function ctrl_createWebTv(Request $request): JsonResponse
    {
        return $this->webTvService->srv_createWebTv($request);
    }

    public function ctrl_updateWebTv(Request $request, $slug): JsonResponse
    {
        return $this->webTvService->srv_updateWebTv($request, $slug);
    }

    public function ctrl_deleteWebTv($slug): JsonResponse
    {
        return $this->webTvService->srv_deleteWebTv($slug);
    }
}
