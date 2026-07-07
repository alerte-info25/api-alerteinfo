<?php

namespace App\Services\BadgeServices;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use App\Models\Badge\BadgeModel;
use Illuminate\Http\JsonResponse;
use App\Models\Badge\BadgeSponsorModel;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;

class BadgeSponsorService
{
    public function __construct(
        private readonly BadgeSponsorModel $badgeSponsorModel,
        private readonly BadgeModel $badgeModel,
        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogError $customLogService,
        private readonly UploadFileManagerService $uploader
    ) {
    }



    public function srv_getBadgeSponsorsList(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            // Récupérer les sponsors avec leur badge
            $sponsors = $this->badgeSponsorModel
                ->with('badge') // Relation vers BadgeModel
                ->orderByDesc('id')
                ->paginate($perPage, ['*'], 'page', $page);

            // Grouper par événement
            $grouped = $sponsors->getCollection()->map(function ($sponsor) {
                return [
                    'uuid' => $sponsor->uuid,
                    'badge_code' => $sponsor->badge_code,
                    'event_name' => $sponsor->badge->event_name,
                    'sponsor_name' => $sponsor->sponsor_name,
                    'sponsor_logo_path' => $sponsor->sponsor_logo_path_url,
                    'created_at' => $sponsor->created_at,
                    'updated_at' => $sponsor->updated_at,
                ];
            });
                
            return $this->jsonResponseService->successResponseWithData(
                "Sponsors groupés par événement récupérés avec succès",
                [
                    'sponsorsList' => $grouped,
                    'pagination' => [
                        'total' => $sponsors->total(),
                        'per_page' => $sponsors->perPage(),
                        'current_page' => $sponsors->currentPage(),
                        'last_page' => $sponsors->lastPage(),
                    ],
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Erreur lors de la récupération des sponsors groupés par événement",
                $th
            );

            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la récupération des sponsors groupés par événement",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_getBadgeSponsorsFormData(): JsonResponse
    {
        try {
            $badges = $this->badgeModel->scopeBadgeAvailable()
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


    public function srv_getByBadgeCode(string $badge_code): JsonResponse
    {
        try {
            $badges = $this->badgeSponsorModel->where('badge_code', $badge_code)
                ->select([
                    "uuid",
                    "sponsor_logo_b64",
                    "created_at",
                    "updated_at",
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

    public function srv_createBadgeSponsor(Request $request): JsonResponse
    {
        try {
            $result = $request->sponsor_logo ? $this->uploadMedia($request->sponsor_logo, 'badges/sponsor_logo') : null;

            $this->badgeSponsorModel::create([
                "badge_code" => $request->badge_code,
                "sponsor_name" => $request->sponsor_name,
                "sponsor_logo_path" => $result['path'],
                "sponsor_logo_b64" => $result['b64'],
                'uuid' => Str::uuid()->toString(),
            ]);

            return $this->jsonResponseService->successResponse(
                "Logo sponsor créé avec succès",
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la création du sponsor du badge",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la création du badge",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateBadgeSponsor(Request $request, string $uuid): JsonResponse
    {
        try {
            $result = $request->sponsor_logo ? $this->uploadMedia($request->sponsor_logo, 'badges/sponsor_logo') : null;

            $badge = $this->badgeSponsorModel::where("uuid", $uuid)->firstOrFail();
            $badge->update([
                "badge_code" => $request->badge_code,
                "sponsor_name" => $request->sponsor_name,
                "sponsor_logo_path" => $result ? $result['path'] : $badge->sponsor_logo_path,
                "sponsor_logo_b64" => $result ? $result['b64'] : $badge->sponsor_logo_b64,
            ]);
            return $this->jsonResponseService->successResponse(
                "Sponsor du badge mise à jour avec succès",
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour du sponsor du badge",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Sponsor du badge non trouvée @: { $uuid }",
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour du sponsor du badge",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la mise à jour du sponsor du badge",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteBadgeSponsor(string $uuid): JsonResponse
    {
        try {
            $badge = $this->badgeSponsorModel::where("uuid", $uuid)->firstOrFail();
            $logoPath = $badge->sponsor_logo_path;
            $badge->delete();
            $this->uploader->deleteFile($logoPath);
            return $this->jsonResponseService->successResponse(
                "Sponsor du badge supprimé avec succès",
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la suppression du sponsor du badge",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Sponsor du badge non trouvée @: { $uuid }",
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la suppression du sponsor du badge",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la suppression du sponsor du badge",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    
    /**
     * Upload media file
     *
     * @param string $mediaPath
     * @param string $folderPath
     * @return array{path: string, b64: string}
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

