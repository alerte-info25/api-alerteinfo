<?php

namespace App\Services\PartnerServices;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Partners\FiaPartnersModel;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;

class PartnerService
{
    public function __construct(
        private readonly FiaPartnersModel $partnerModels,
        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogError $customLogService,
        private readonly UploadFileManagerService $uploader,
        private readonly UploadFileManagerService $uploadder
    ) {
    }



    public function srv_getAllPartners(Request $request): JsonResponse
    {
        try {
            // Récupérer les paramètres de pagination
            $perPage = $request->input('per_page', 20); // Nombre d'éléments par page (par défaut : 10)
            $page = $request->input('page', 1); // Page actuelle (par défaut : 1)

            $partners = $this->partnerModels::paginate($perPage, ['*'], 'page', $page);

            $partnersDataFormatted = $partners->getCollection()->map(function ($partner) {
                return [
                    "uuid" => $partner->uuid,
                    "title" => $partner->title,
                    "media_path" => $partner->media_path_url,
                    "web_site_url" => $partner->web_site_url,
                    "active" => $partner->active,
                    "created_at" => $partner->created_at,
                    "updated_at" => $partner->updated_at,
                ];
            });

            return $this->jsonResponseService->successResponseWithData(
                "Liste des partenaires récupérés avec succès",
                [
                    "partners" => $partnersDataFormatted,
                    "pagination" => [
                        "total" => $partners->total(),
                        "per_page" => $partners->perPage(),
                        "current_page" => $partners->currentPage(),
                        "last_page" => $partners->lastPage(),
                        "from" => $partners->firstItem(),
                        "to" => $partners->lastItem(),
                    ],
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la récupération des partenaires",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la récupération des partenaires",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_getPartnerActive(): JsonResponse
    {
        try {
            $partners = $this->partnerModels::active()
            ->select([
                "uuid",
                "media_path",
                "web_site_url",
            ])
            ->get();

            return $this->jsonResponseService->successResponseWithData(
                "Liste des partenaires récupérés avec succès",
                $partners,
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la récupération des partenaires",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la récupération des partenaires",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createPartner(Request $request): JsonResponse
    {
        try {
            $result = $request->media_path ? $this->uploadMedia($request->media_path) : null;

            $this->partnerModels::create([
                "title" => $request->title,
                "media_path" => $result,
                "web_site_url" => $request->web_site_url ?? null,
                'uuid' => Str::uuid()->toString(),
            ]);

            return $this->jsonResponseService->successResponse(
                "Partenaire créé avec succès",
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la création du partenaire",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la création du partenaire",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updatePartner(Request $request, string $uuid): JsonResponse
    {
        try {
            $result = $request->media_path ? $this->uploadMedia($request->media_path) : null;



            $partner = $this->partnerModels::where("uuid", $uuid)->firstOrFail();
            $partner->update([
                "media_path" => $result ?? $partner->media_path,
                "web_site_url" => $request->web_site_url ?? null,
            ]);
            return $this->jsonResponseService->successResponse(
                "Partenaire mise à jour avec succès",
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour du partenaire",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Partenaire non trouvée @: { $uuid }",
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour du partenaire",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la mise à jour du carousel",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    public function srv_deletePartner(string $uuid): JsonResponse
    {
        try {
            $partner = $this->partnerModels::where("uuid", $uuid)->firstOrFail();
            $oldPath = $partner->media_path;
            $partner->delete();
            $this->uploadder->deleteFile($oldPath);
            return $this->jsonResponseService->successResponse(
                "Partenaire supprimée avec succès",
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la suppression du partenaire",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Partenaire non trouvée @: { $uuid }",
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la suppression du partenaire",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la suppression du partenaire",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_enableOrDisablePartner(string $uuid): JsonResponse
    {
        try {
            $partner = $this->partnerModels::where("uuid", $uuid)->firstOrFail();
            $partner->update([
                "active" => $partner->active ? false : true,
            ]);
            return $this->jsonResponseService->successResponse(
                "Partenaire mise à jour avec succès",
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour du partenaire",
                $me
            );
            return $this->jsonResponseService->errorResponse(
                "Partenaire non trouvée @: { $uuid }",
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            $this->customLogService->logError(
                "Une erreur est survenue lors de la mise à jour du partenaire",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Une erreur est survenue lors de la mise à jour du partenaire",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    private function uploadMedia($mediaPath):string
    {
        $result = $this->uploader->uploadDefaultFile(
            $mediaPath,
            'partners'
        );
        $error = $this->uploader->handleFileUploadError($result);
        if ($error) {
            throw new \RuntimeException($error);
        }
        return $result['fileData']['path'];
    }
}

