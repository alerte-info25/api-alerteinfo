<?php

namespace App\Services\BadgeServices;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use App\Models\Badge\BadgeModel;
use Illuminate\Http\JsonResponse;
use App\Models\Badge\BadgeDataModel;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;

class BadgeDataService
{
    public function __construct(
        private readonly BadgeDataModel $badgeDataModel,
        private readonly BadgeModel $badgeModel,

        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogError $customLogService,
        private readonly UploadFileManagerService $uploader
    ) {
    }

    
    public function srv_getAllBadgeData(Request $request): JsonResponse
    {
        try {
            // Récupérer les paramètres de pagination
            $perPage = $request->input('per_page', 20); // Nombre d'éléments par page (par défaut : 10)
            $page = $request->input('page', 1); // Page actuelle (par défaut : 1)

            $badgeData = $this->badgeDataModel
            ->with('badge')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

            $badgeDataFormatted = $badgeData->getCollection()->map(function ($badgeData) {
                return [
                    "uuid" => $badgeData->uuid,
                    "badge_code" => $badgeData->badge_code,
                    "event_name" => $badgeData->badge->event_name ?? null,
                    "first_name" => $badgeData->first_name,
                    "last_name" => $badgeData->last_name,
                    "function" => $badgeData->function,
                    "owner_photo_path" => $badgeData->owner_photo_path_url,
                    "owner_country_name" => $badgeData->owner_country_name,
                    "owner_country_code" => $badgeData->owner_country_code,
                    "owner_country_flag" => $badgeData->owner_country_flag,
                    
                    "zone_access_1" => $badgeData->zone_access_1,
                    "zone_access_2" => $badgeData->zone_access_2,
                    "zone_access_3" => $badgeData->zone_access_3,
                    "zone_access_4" => $badgeData->zone_access_4,
                    "zone_access_5" => $badgeData->zone_access_5,
                    "zone_access_6" => $badgeData->zone_access_6,
                    "zone_access_7" => $badgeData->zone_access_7,
                    "zone_access_8" => $badgeData->zone_access_8,
                    "zone_access_9" => $badgeData->zone_access_9,
                    "zone_access_10" => $badgeData->zone_access_10,
                    "zone_access_11" => $badgeData->zone_access_11,
                    "zone_access_12" => $badgeData->zone_access_12,
                    "zone_access_13" => $badgeData->zone_access_13,
                    "zone_access_14" => $badgeData->zone_access_14,
                    "zone_access_15" => $badgeData->zone_access_15,
                    "zone_access_16" => $badgeData->zone_access_16,
                    "created_at" => $badgeData->created_at,
                    "updated_at" => $badgeData->updated_at,
                ];
            });

            return $this->jsonResponseService->successResponseWithData(
                "Badge data récupéré avec succès",
                [
                    "badgeDataList" => $badgeDataFormatted,
                    "pagination" => [
                        "total" => $badgeData->total(),
                        "per_page" => $badgeData->perPage(),
                        "current_page" => $badgeData->currentPage(),
                        "last_page" => $badgeData->lastPage(),
                        "from" => $badgeData->firstItem(),
                        "to" => $badgeData->lastItem(),
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

    public function srv_getBadgeDataListFilter(Request $request): JsonResponse
    {
        try {
            // Pagination parameters
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            $badgeCode = $request->input('badge_code');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');


            $query = $this->badgeDataModel
            ->with('badge');

            $query->when($badgeCode, function ($query) use ($badgeCode) {
                $query->whereHas('badge', function ($sub) use ($badgeCode) {
                    $sub->where('badge_code', $badgeCode);
                });
            });

            $query->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            });

            $badgeData = $query->paginate($perPage, ['*'], 'page', $page);

            $badgeDataFormatted = $badgeData->getCollection()->map(function ($badgeData) {
                return [
                    "uuid" => $badgeData->uuid,
                    "badge_code" => $badgeData->badge_code,
                    "event_name" => $badgeData->badge->event_name ?? null,
                    "first_name" => $badgeData->first_name,
                    "last_name" => $badgeData->last_name,
                    "function" => $badgeData->function,
                    "owner_photo_path" => $badgeData->owner_photo_path_url,
                    "owner_country_name" => $badgeData->owner_country_name,
                    "created_at" => $badgeData->created_at,
                    "updated_at" => $badgeData->updated_at,
                ];
            });

            return $this->jsonResponseService->successResponseWithData(
                "Badge data récupéré avec succès",
                [
                    "badgeDataList" => $badgeDataFormatted,
                    "pagination" => [
                        "total" => $badgeData->total(),
                        "per_page" => $badgeData->perPage(),
                        "current_page" => $badgeData->currentPage(),
                        "last_page" => $badgeData->lastPage(),
                        "from" => $badgeData->firstItem(),
                        "to" => $badgeData->lastItem(),
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

    public function srv_getTableData() : JsonResponse
    {
        try {
            // get table data
            $listEvent = $this->badgeModel->orderByDesc('id')->get();

            return $this->jsonResponseService->successResponseWithData(
                'Données de la table récupérées avec succès',
                [
                    'listEvent' => $listEvent,
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError('Erreur lors de la récupération des données de la table', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération des données de la table',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_getBadgeDataDetail(string $uuid): JsonResponse
    {
        try {
            $badge = $this->badgeDataModel->with(['badge','badge.badgeSponsor'])
            ->where("uuid", $uuid)->firstOrFail();

            $badgeFormatted = [
                "uuid" => $badge->uuid,
                "badge_code" => $badge->badge_code,
                "event_name" => $badge->badge->event_name ?? null,
                'right_logo_b64' => $badge->badge->right_logo_b64 ?? null,
                'left_logo_b64' => $badge->badge->left_logo_b64 ?? null,
                "first_name" => $badge->first_name,
                "last_name" => $badge->last_name,
                "function" => $badge->function,
                "owner_photo_path" => $badge->owner_photo_path_url,
                "owner_photo_b64" => $badge->owner_photo_b64,
                "owner_country_name" => $badge->owner_country_name,
                "owner_country_code" => $badge->owner_country_code,
                "owner_country_flag" => $badge->owner_country_flag,
                "description" => $badge->badge->description,
                "zone_access_1" => $badge->zone_access_1 ?? null,
                "zone_access_2" => $badge->zone_access_2 ?? null,
                "zone_access_3" => $badge->zone_access_3 ?? null,
                "zone_access_4" => $badge->zone_access_4 ?? null,
                "zone_access_5" => $badge->zone_access_5 ?? null,
                "zone_access_6" => $badge->zone_access_6 ?? null,
                "zone_access_7" => $badge->zone_access_7 ?? null,
                "zone_access_8" => $badge->zone_access_8 ?? null,
                "zone_access_9" => $badge->zone_access_9 ?? null,
                "zone_access_10" => $badge->zone_access_10 ?? null,
                "zone_access_11" => $badge->zone_access_11 ?? null,
                "zone_access_12" => $badge->zone_access_12 ?? null,
                "zone_access_13" => $badge->zone_access_13 ?? null,
                "zone_access_14" => $badge->zone_access_14 ?? null,
                "zone_access_15" => $badge->zone_access_15 ?? null,
                "zone_access_16" => $badge->zone_access_16 ?? null,
                "badge_sponsor" => $badge->badge->badgeSponsor,

                "created_at" => $badge->created_at,
                "updated_at" => $badge->updated_at,
            ];
            return $this->jsonResponseService->successResponseWithData(
                "Badge récupéré avec succès",
                $badgeFormatted,
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la récupération du badge",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Badge non trouvée @: { $uuid }",
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la récupération du badge",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la récupération du badge",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    
    public function srv_createBadgeData(Request $request): JsonResponse
    {
        try {
            $resultOwnerPhoto = $request->owner_photo ? $this->uploadMedia($request->owner_photo, 'badges/owner_photos') : null;

            $this->badgeDataModel::create([
                "badge_code" => $request->badge_code,
                "first_name" => $request->first_name,
                "last_name" => $request->last_name,
                "function" => $request->function,
                "owner_photo_path" => $resultOwnerPhoto['path'],
                "owner_photo_b64" => $resultOwnerPhoto['b64'],
                "owner_country_name" => $request->owner_country_name,
                'uuid' => Str::uuid()->toString(),
            ]);

            return $this->jsonResponseService->successResponse(
                "Vos données ont été envoyées avec succès. Vous serez contacté dès que votre badge sera prêt.",
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la création des données du badge",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la création des données du badge",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateBadgeData(Request $request, string $uuid): JsonResponse
    {
        try {
            $resultOwnerPhoto = $request->owner_photo ? $this->uploadMedia($request->owner_photo, 'badges/owner_photos') : null;

            $badge = $this->badgeDataModel::where("uuid", $uuid)->firstOrFail();

            $badge->update([
                "badge_code" => $request->badge_code ?? $badge->badge_code,
                "first_name" => $request->first_name ?? $badge->first_name,
                "last_name" => $request->last_name ?? $badge->last_name,
                "function" => $request->function ?? $badge->function,
                "owner_photo_path" => $resultOwnerPhoto ? $resultOwnerPhoto['path'] : $badge->owner_photo_path  ,
                "owner_photo_b64" => $resultOwnerPhoto ? $resultOwnerPhoto['b64'] : $badge->owner_photo_b64,
                "owner_country_name" => $request->owner_country_name ?? $badge->owner_country_name,
                'owner_country_code' => $request->owner_country_code ?? null,
                'owner_country_flag' => $request->owner_country_flag ?? null
            ]);
            return $this->jsonResponseService->successResponse(
                "Données du badge mise à jour avec succès",
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour des données du badge",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Données du badge non trouvées @: { $uuid }",
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour des données du badge",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la mise à jour des données du badge",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateBadgeDataZoneAccess(Request $request, string $uuid): JsonResponse
    {
        try {
            $badge = $this->badgeDataModel::where("uuid", $uuid)->firstOrFail();

            $badge->update([
                'zone_access_1' => $request->zone_access_1 ?? null,
                'zone_access_2' => $request->zone_access_2 ?? null,
                'zone_access_3' => $request->zone_access_3 ?? null,
                'zone_access_4' => $request->zone_access_4 ?? null,
                'zone_access_5' => $request->zone_access_5 ?? null,
                'zone_access_6' => $request->zone_access_6 ?? null,
                'zone_access_7' => $request->zone_access_7 ?? null,
                'zone_access_8' => $request->zone_access_8 ?? null,
                'zone_access_9' => $request->zone_access_9 ?? null,
                'zone_access_10' => $request->zone_access_10 ?? null,
                'zone_access_11' => $request->zone_access_11 ?? null,
                'zone_access_12' => $request->zone_access_12 ?? null,
                'zone_access_13' => $request->zone_access_13 ?? null,
                'zone_access_14' => $request->zone_access_14 ?? null,
                'zone_access_15' => $request->zone_access_15 ?? null,
                'zone_access_16' => $request->zone_access_16 ?? null,
            ]);
            return $this->jsonResponseService->successResponse(
                "Données du badge mise à jour avec succès",
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour des données du badge",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Données du badge non trouvées @: { $uuid }",
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour des données du badge",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la mise à jour des données du badge",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteBadge(string $uuid): JsonResponse
    {
        try {
            $badge = $this->badgeDataModel::where("uuid", $uuid)->firstOrFail();

            $oldBadgePhotoPath = $badge->owner_photo_path;
            $badge->delete();

            if ($oldBadgePhotoPath) {
                $this->uploader->deleteFile($oldBadgePhotoPath);
            }
            return $this->jsonResponseService->successResponse(
                "Données du badge supprimées avec succès",
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la suppression des données du badge",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Badge non trouvée @: { $uuid }",
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la suppression du badge",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la suppression du badge",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Upload media file
     *
     * @param string $mediaPath
     * @param string $folderPath
     * @return array
     */
    private function uploadMedia($mediaPath, $folderPath): array
    {
        $result = $this->uploader->uploadDefaultFile(
            $mediaPath,
            $folderPath
        );
        $error = $this->uploader->handleFileUploadError($result);
        if ($error) {
            throw new \RuntimeException($error);
        }
        $path = $result['fileData']['path'];
        return [
            'path' => $path,
            'b64' => $this->uploader->transformToBase64($path)
        ];
    }


}

