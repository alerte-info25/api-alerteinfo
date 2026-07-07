<?php

namespace App\Http\Controllers\API\V1\Banners;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Services\BannerServices\BannerService;
use App\Services\JsonResponseServices\JsonResponseService;

class FiaBannerController extends Controller
{
    public function __construct(
        private readonly BannerService $bannerService,
        private readonly JsonResponseService $jsonResponseService
    ) {
    }

    public function ctrl_getBanner(Request $request): JsonResponse
    {
        return $this->bannerService->srv_getAllBanners($request);
    }

    public function ctrl_createBanner(Request $request): JsonResponse
    {
        return $this->bannerService->srv_createBanner($request);
    }

    public function ctrl_updateBanner(Request $request, string $uuid): JsonResponse
    {
        return $this->bannerService->srv_updateBanner($request, $uuid);
    }

    
    public function ctrl_enableOrDisableBanner(string $uuid): JsonResponse
    {
        return $this->bannerService->srv_enableOrDisableBanner($uuid);
    }

    public function ctrl_deleteBanner(string $uuid): JsonResponse
    {
        return $this->bannerService->srv_deleteBanner($uuid);
    }

    // validate banner form
    private function validateBannerForm($request): JsonResponse
    {

        if(empty($request->media_path)) {
            return $this->jsonResponseService->errorResponse(
                "Le chemin de l'image est obligatoire",
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->jsonResponseService->successResponse(
            "Le banner a été créé avec succès",
            Response::HTTP_OK
        );
    }
}
