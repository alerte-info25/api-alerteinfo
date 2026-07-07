<?php

namespace App\Services\CarouselServices;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Carousels\CarouselModels;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;

class CarouselService
{
    public function __construct(
        private readonly CarouselModels $carouselModels,
        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogError $customLogService,
        private readonly UploadFileManagerService $uploader
    ) {
    }

    public function srv_getAllCarousels(Request $request): JsonResponse
    {
        try {
            // Récupérer les paramètres de pagination
            $perPage = $request->input('per_page', 20); // Nombre d'éléments par page (par défaut : 10)
            $page = $request->input('page', 1); // Page actuelle (par défaut : 1)

            $carousels = $this->carouselModels::paginate($perPage, ['*'], 'page', $page);

            $carouselDataFormatted = $carousels->getCollection()->map(function ($carousel) {
                return [
                    "uuid" => $carousel->uuid,
                    "title" => $carousel->title,
                    "media_path" => $carousel->media_path_url,
                    "active" => $carousel->active,
                    'created_at' => $carousel->created_at,
                    'updated_at' => $carousel->updated_at,
                ];
            });

            return $this->jsonResponseService->successResponseWithData(
                "Carousels récupérés avec succès",
                [
                    'carouselList' => $carouselDataFormatted,
                    'pagination' => [
                        'total' => $carousels->total(),
                        'per_page' => $carousels->perPage(),
                        'current_page' => $carousels->currentPage(),
                        'last_page' => $carousels->lastPage(),
                    ],
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la récupération des carousels",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la récupération des carousels",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_getCarouselActive(): JsonResponse
    {
        try {
            $carousels = $this->carouselModels::active()
            ->select([
                "uuid",
                "title",
                "media_path",
            ])
            ->get();



            return $this->jsonResponseService->successResponseWithData(
                "Carousels récupérés avec succès",
                $carousels,
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la récupération des carousels",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la récupération des carousels",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createCarousel($requestData): JsonResponse
    {
        try {

            $result = $requestData->media_path ? $this->uploadMedia($requestData->media_path) : null;

            $carousel = $this->carouselModels::create([
                "title" => $requestData->title,
                "media_path" => $result,
                "uuid" => Str::uuid(),
            ]);

            return $this->jsonResponseService->successResponseWithData(
                "Carousel créé avec succès",
                $carousel,
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la création du carousel",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la création du carousel",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    
    public function srv_updateCarousel(Request $request, string $uuid): JsonResponse
    {
        try {
            $carousel = $this->carouselModels::where("uuid", $uuid)->firstOrFail();

            $result = $request->media_path ? $this->uploadMedia($request->media_path) : null;

            $carousel->update([
                "title" => $request->title,
                "media_path" => $result ?? $carousel->media_path,
            ]);
            return $this->jsonResponseService->successResponseWithData(
                "Carousel mis à jour avec succès",
                $carousel,
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour du carousel",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Carousel non trouvé @: { $uuid }",
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour du carousel",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la mise à jour du carousel",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteCarousel(string $uuid): JsonResponse
    {
        try {
            $carousel = $this->carouselModels::where("uuid", $uuid)->firstOrFail();
            $carousel->delete();
            return $this->jsonResponseService->successResponseWithData(
                "Carousel supprimé avec succès",
                $carousel,
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la suppression du carousel",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Carousel non trouvé @: { $uuid }",
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la suppression du carousel",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la suppression du carousel",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_enableOrDisableCarousel(string $uuid): JsonResponse
    {
        try {
            $carousel = $this->carouselModels::where("uuid", $uuid)->firstOrFail();
            $carousel->update([
                "active" =>  $carousel->active ? false : true,
            ]);
            return $this->jsonResponseService->successResponseWithData(
                "Carousel mis à jour avec succès",
                $carousel,
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour du carousel",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Carousel non trouvé @: { $uuid }",
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour du carousel",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la mise à jour du carousel",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    private function uploadMedia($mediaPath):string
    {
        $result = $this->uploader->uploadDefaultFile(
            $mediaPath,
            'carousels'
        );
        $error = $this->uploader->handleFileUploadError($result);
        if ($error) {
            throw new \RuntimeException($error);
        }
        return $result['fileData']['path'];
    }
}

