<?php

namespace App\Http\Controllers\Galeries;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\MediaServices\MediaService;

class GalerieController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService,
    ){}

    public function ctrl_getGalerie(Request $requestData): JsonResponse
    {
        return $this->mediaService->srv_getMedia($requestData);
    }

    public function ctrl_getGalerieLimited(): JsonResponse
    {
        return $this->mediaService->srv_getGalerieLimited();
    }

    public function ctrl_createGalerie(Request $request): JsonResponse
    {
        return $this->mediaService->srv_createMedia($request);
    }

    public function ctrl_updateGalerie(Request $request, $slug): JsonResponse
    {
        return $this->mediaService->srv_updateMedia($request, $slug);
    }

    public function ctrl_deleteGalerie($slug): JsonResponse
    {
        return $this->mediaService->srv_deleteMedia($slug);
    }
}
