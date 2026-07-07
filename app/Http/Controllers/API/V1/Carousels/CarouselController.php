<?php

namespace App\Http\Controllers\API\V1\Carousels;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Services\CarouselServices\CarouselService;
use App\Services\JsonResponseServices\JsonResponseService;

class CarouselController extends Controller
{

    public function __construct(
        private readonly CarouselService $carouselService,
        private readonly JsonResponseService $jsonResponseService
    ) {
    }

    public function ctrl_getCarousel(Request $request): JsonResponse
    {
        return $this->carouselService->srv_getAllCarousels($request);
    }


    public function ctrl_createCarousel(Request $request): JsonResponse
    {
        $validateCarouselForm = $this->validateCarouselForm($request);
        if($validateCarouselForm->getStatusCode() != Response::HTTP_OK) {
            return $validateCarouselForm;
        }
        return $this->carouselService->srv_createCarousel($request);
    }

    public function ctrl_updateCarousel(Request $request, string $uuid): JsonResponse
    {
        $validateCarouselForm = $this->validateCarouselForm($request);
        if($validateCarouselForm->getStatusCode() != Response::HTTP_OK) {
            return $validateCarouselForm;
        }
        if(empty($uuid)) {
            return $this->jsonResponseService->errorResponse(
                "L'uuid est obligatoire",
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->carouselService->srv_updateCarousel($request, $uuid);
    }

    public function ctrl_enableOrDisableCarousel(string $uuid): JsonResponse
    {
        if(empty($uuid)) {
            return $this->jsonResponseService->errorResponse(
                "L'uuid est obligatoire",
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->carouselService->srv_enableOrDisableCarousel($uuid);
    }

    public function ctrl_deleteCarousel(string $uuid): JsonResponse
    {
        if(empty($uuid)) {
            return $this->jsonResponseService->errorResponse(
                "L'uuid est obligatoire",
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->carouselService->srv_deleteCarousel($uuid);
    }

    // validate carousel form
    private function validateCarouselForm($request): JsonResponse
    {
        if(empty($request->title)) {
            return $this->jsonResponseService->errorResponse(
                "Le titre est obligatoire",
                Response::HTTP_BAD_REQUEST
            );
        }
        if(empty($request->media_path)) {
            return $this->jsonResponseService->errorResponse(
                "Le chemin de l'image est obligatoire",
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->jsonResponseService->successResponse(
            "Le carousel a été créé avec succès",
            Response::HTTP_OK
        );
    }
}
