<?php

namespace App\Services\BadgeServices;   

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use App\Models\Badge\BadgeModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;

class BadgeService
{
    public function __construct(
        private readonly BadgeModel $badgeModel,
        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogError $customLogService,
        private readonly UploadFileManagerService $uploader,
        private readonly CodeGeneratorService $codeGeneratorService
    ) {
    }

    
    public function srv_getAllBadges(Request $request): JsonResponse
    {
        try {
            // Récupérer les paramètres de pagination
            $perPage = $request->input('per_page', 20); // Nombre d'éléments par page (par défaut : 10)
            $page = $request->input('page', 1); // Page actuelle (par défaut : 1)

            $badges = $this->badgeModel->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

            $badgesDataFormatted = $badges->getCollection()->map(function ($badge) {
                return [
                    "uuid" => $badge->uuid,
                    "badge_code" => $badge->badge_code,
                    "event_name" => $badge->event_name,
                    "event_date_start" => $badge->event_date_start,
                    "event_date_end" => $badge->event_date_end,
                    "right_logo_path" => $badge->right_logo_path_url,
                    "left_logo_path" => $badge->left_logo_path_url,
                    //"right_logo_b64" => $badge->right_logo_b64,
                    //"left_logo_b64" => $badge->left_logo_b64,
                    "description" => $badge->description,
                    "created_at" => $badge->created_at,
                    "updated_at" => $badge->updated_at,
                ];
            });

            return $this->jsonResponseService->successResponseWithData(
                "Badges récupérés avec succès",
                [
                    "badgesList" => $badgesDataFormatted,
                    "pagination" => [
                        "total" => $badges->total(),
                        "per_page" => $badges->perPage(),
                        "current_page" => $badges->currentPage(),
                        "last_page" => $badges->lastPage(),
                        "from" => $badges->firstItem(),
                        "to" => $badges->lastItem(),
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

    public function srv_getBadgeActive(): JsonResponse
    {
        try {
            $badges = $this->badgeModel::active()
            ->select([
                "uuid",
                "event_name",
                "event_date_start",
                "event_date_end",
            ])
            ->get();

            return $this->jsonResponseService->successResponseWithData(
                "Badges récupérés avec succès",
                $badges,
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la récupération des badges",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la récupération des badges",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_getBadgeDetail(string $uuid): JsonResponse
    {
        try {
            $badge = $this->badgeModel->with([
                "badgeData",
                "badgeSponsor"
            ])
            ->where("uuid", $uuid)->firstOrFail();
            return $this->jsonResponseService->successResponseWithData(
                "Badge récupéré avec succès",
                $badge,
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

    public function srv_createBadge(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $resultRightLogo = $request->right_logo ? $this->uploadMedia($request->right_logo, 'badges/right_logos') : null;
            $resultLeftLogo = $request->left_logo ? $this->uploadMedia($request->left_logo, 'badges/left_logos') : null;

            $badgeCode = $this->codeGeneratorService->generateDefaultCodeUnique(
                "badge_models",
                "badge_code",
                'BDG'
            );

            $this->badgeModel::create([
                "badge_code" => $badgeCode,
                "event_name" => $request->event_name,
                "event_date_start" => now()->parse($request->event_date_start),
                "event_date_end" => now()->parse($request->event_date_end),
                "right_logo_path" => $resultRightLogo['path'],
                "left_logo_path" => $resultLeftLogo['path'],
                "right_logo_b64" => $resultRightLogo['b64'],
                "left_logo_b64" => $resultLeftLogo['b64'],
                "description" => $request->description,
                'uuid' => Str::uuid()->toString(),
            ]);

            DB::commit();
            return $this->jsonResponseService->successResponse(
                "Badge généré avec succès",
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            // Delete file
            $this->uploader->deleteFile($resultRightLogo['path']);
            $this->uploader->deleteFile($resultLeftLogo['path']);


            $this->customLogService->logError(
                "Une erreur est survenue lors de la création du badge",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la création du badge",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateBadge(Request $request, string $uuid): JsonResponse
    {
        try {
            $resultRightLogo = $request->right_logo ? $this->uploadMedia($request->right_logo, 'badges/right_logos') : null;
            $resultLeftLogo = $request->left_logo ? $this->uploadMedia($request->left_logo, 'badges/left_logos') : null;

            $badge = $this->badgeModel::where("uuid", $uuid)->firstOrFail();
            $badge->update([
                "event_name" => $request->event_name,
                "event_date_start" => now()->parse($request->event_date_start),
                "event_date_end" => now()->parse($request->event_date_end),
                "right_logo_path" => $resultRightLogo ? $resultRightLogo['path'] : $badge->right_logo_path,
                "left_logo_path" => $resultLeftLogo ? $resultLeftLogo['path'] : $badge->left_logo_path,
                "right_logo_b64" => $resultRightLogo ? $resultRightLogo['b64'] : $badge->right_logo_b64,
                "left_logo_b64" => $resultLeftLogo ? $resultLeftLogo['b64'] : $badge->left_logo_b64,
                "description" => $request->description,
            ]);
            return $this->jsonResponseService->successResponse(
                "Badge mise à jour avec succès",
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour du badge",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Badge non trouvée @: { $uuid }",
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour du badge",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la mise à jour du badge",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteBadge(string $uuid): JsonResponse
    {
        try {
            $badge = $this->badgeModel::where("uuid", $uuid)->firstOrFail();
            $badge->delete();
            return $this->jsonResponseService->successResponse(
                "Badge supprimé avec succès",
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la suppression du badge",
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

