<?php

namespace App\Services\BannerServices;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Banners\FiaBannerModel;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;

class BannerService
{
    public function __construct(
        private readonly FiaBannerModel $bannerModels,
        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogError $customLogService,
        private readonly UploadFileManagerService $uploader
    ) {
    }


    public function srv_getAllBanners(Request $request): JsonResponse
    {
        try {
            // Récupérer les paramètres de pagination
            $perPage = $request->input('per_page', 20); // Nombre d'éléments par page (par défaut : 10)
            $page = $request->input('page', 1); // Page actuelle (par défaut : 1)

            $banners = $this->bannerModels::paginate($perPage, ['*'], 'page', $page);

            $bannersDataFormatted = $banners->getCollection()->map(function ($banner) {
                return [
                    "uuid" => $banner->uuid,
                    "title" => $banner->title,
                    "media_path" => $banner->media_path_url,
                    "web_site_url" => $banner->web_site_url,
                    "active" => $banner->active,
                    "created_at" => $banner->created_at,
                    "updated_at" => $banner->updated_at,
                ];
            });

            return $this->jsonResponseService->successResponseWithData(
                "Banners récupérés avec succès",
                [
                    "banners" => $bannersDataFormatted,
                    "pagination" => [
                        "total" => $banners->total(),
                        "per_page" => $banners->perPage(),
                        "current_page" => $banners->currentPage(),
                        "last_page" => $banners->lastPage(),
                        "from" => $banners->firstItem(),
                        "to" => $banners->lastItem(),
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

    public function srv_getBannerActive(): JsonResponse
    {
        try {
            $banners = $this->bannerModels::active()
            ->select([
                "uuid",
                "media_path_url",
                "web_site_url",
            ])
            ->get();

            return $this->jsonResponseService->successResponseWithData(
                "Banners récupérés avec succès",
                $banners,
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la récupération des banners",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la récupération des banners",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createBanner(Request $request): JsonResponse
    {
        try {
            $result = $request->media_path ? $this->uploadMedia($request->media_path) : null;

            $this->bannerModels::create([
                "media_path" => $result,
                "web_site_url" => $request->web_site_url ?? null,
                'uuid' => Str::uuid()->toString(),
            ]);

            return $this->jsonResponseService->successResponse(
                "Bannière créé avec succès",
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la création de la bannière",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la création de la bannière",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    

    public function srv_updateBanner(Request $request, string $uuid): JsonResponse
    {
        try {
            $result = $request->media_path ? $this->uploadMedia($request->media_path) : null;



            $banner = $this->bannerModels::where("uuid", $uuid)->firstOrFail();
            $banner->update([
                "media_path" => $result ?? $banner->media_path,
                "web_site_url" => $request->web_site_url ?? $banner->web_site_url,
            ]);
            return $this->jsonResponseService->successResponse(
                "Bannière mise à jour avec succès",
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour de la bannière",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Bannière non trouvée @: { $uuid }",
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

    public function srv_deleteBanner(string $uuid): JsonResponse
    {
        try {
            $banner = $this->bannerModels::where("uuid", $uuid)->firstOrFail();
            $banner->delete();
            return $this->jsonResponseService->successResponse(
                "Bannière supprimée avec succès",
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la suppression de la bannière",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Bannière non trouvée @: { $uuid }",
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

    public function srv_enableOrDisableBanner(string $uuid): JsonResponse
    {
        try {
            $banner = $this->bannerModels::where("uuid", $uuid)->firstOrFail();
            $banner->update([
                "active" => $banner->active ? false : true,
            ]);
            return $this->jsonResponseService->successResponse(
                "Bannière mise à jour avec succès",
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour de la bannière",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Bannière non trouvée @: { $uuid }",
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour de la bannière",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la mise à jour de la bannière",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    private function uploadMedia($mediaPath):string
    {
        $result = $this->uploader->uploadDefaultFile(
            $mediaPath,
            'banners'
        );
        $error = $this->uploader->handleFileUploadError($result);
        if ($error) {
            throw new \RuntimeException($error);
        }
        return $result['fileData']['path'];
    }
}

