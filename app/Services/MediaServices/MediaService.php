<?php

namespace App\Services\MediaServices;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Galerie\GalerieModel;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use App\Services\CustomLogServices\CustomLogService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;

class MediaService {



    public function __construct(
        private readonly GalerieModel $galerieModel,
        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogService $customLogService,
        private readonly UploadFileManagerService $uploadFileManagerService
    ) {
    }

    public function srv_getMedia($requestData) {
        try {

            // Récupérer les paramètres de pagination
            $page = $requestData->input('page', 1); // Page actuelle (par défaut : 1)
            $perPage = $requestData->input('per_page', 20); // Nombre d'éléments par page (par défaut : 10)

            $medias = $this->galerieModel
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

            $mediaDataFormated = $medias->getCollection()->map(function ($media) {
                return [
                    "title" => $media->title,
                    'media_path' => $media->media_path_url,
                    "slug" => $media->slug,
                    "created_at" => $media->created_at,
                    "updated_at" => $media->updated_at,
                ];
            });

            return $this->jsonResponseService->srv_successResponseWithData(
                "Medias récupérés avec succès",
                [
                    "medias" => $mediaDataFormated,
                    "pagination" => [
                        "total" => $medias->total(),
                        "per_page" => $medias->perPage(),
                        "current_page" => $medias->currentPage(),
                        "last_page" => $medias->lastPage(),
                        "from" => $medias->firstItem(),
                        "to" => $medias->lastItem(),
                    ]
                ],
                Response::HTTP_OK,
            );

        } catch (\Throwable $th) {
            $this->customLogService->error(
                "Une erreur est survenue lors de la récupération des medias",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la récupération des medias",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_getGalerieLimited(): JsonResponse
    {
        try {
            $medias = $this->galerieModel->limit(20)->orderByDesc('id')->get();

            $mediaDataFormated = $medias->map(function ($media) {
                return [
                    "id" => $media->id,
                    "title" => $media->title,
                    'media_path' => $media->media_path_url,
                    "slug" => $media->slug,
                    "created_at" => $media->created_at,
                    "updated_at" => $media->updated_at,
                ];
            });

            return $this->jsonResponseService->srv_successResponseWithData(
                "Medias récupérés avec succès",
                $mediaDataFormated,
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            $this->customLogService->error(
                "Une erreur est survenue lors de la récupération des medias",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la récupération des medias",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createMedia($requestData): JsonResponse
    {
        try {
            $title = $requestData->title;
            $mediaPaths = [];

            // Vérifier si une pièce jointe a été fournie
            if ($requestData->hasFile('media_path')) {
                foreach ($requestData->file('media_path') as $file) {
                    // Crée une requête factice pour chaque fichier
                    $fakeRequest = new Request();
                    $fakeRequest->files->set('media_path', $file);

                    // Upload du fichier via ton service
                    $mediaPath = $this->uploadFileManagerService->uploadDefaultFile(
                        $fakeRequest,
                        'media_path',
                        ['jpg', 'jpeg', 'png', 'webp'],
                        'MEDIA',
                        "MEDIA",
                        "MEDIA"
                    );

                    // Gérer les erreurs éventuelles
                    $catchError = $this->uploadFileManagerService->handleFileUploadError($mediaPath, 'jpg, jpeg, png, webp');
                    if ($catchError !== null) {
                        return $this->jsonResponseService->srv_errorResponse(
                            "Erreur : " . $catchError,
                            Response::HTTP_BAD_REQUEST
                        );
                    }

                    $mediaPaths[] = $mediaPath;
                }
            }

            if (count($mediaPaths) === 0) {
                return $this->jsonResponseService->srv_errorResponse(
                    "Aucune image fournie",
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Enregistrer chaque média
            $medias = [];
            foreach ($mediaPaths as $path) {
                $medias[] = $this->galerieModel->create([
                    // generate random code unique
                    "galerie_code_unique" => strtoupper(Str::random(10)),
                    "title" => $title,
                    "media_path" => $path,
                    "slug" => Str::uuid(),
                ]);
            }

            return $this->jsonResponseService->srv_successResponseWithData(
                count($medias) . " média(s) créé(s) avec succès",
                $medias,
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            $this->customLogService->error(
                "Une erreur est survenue lors de la création du media",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la création du media",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    public function srv_updateMedia($requestData, $slug): JsonResponse
    {
        try {
            $media = $this->galerieModel->where('slug', $slug)->firstOrFail();

            // recuperer le nom de l'ancien fichier
            $oldMediaPath = $media->media_path;

            $title = $requestData->title;
            $mediaPaths = [];


            if ($requestData->hasFile('media_path')) {
                // Gérer le téléchargement de la pièce jointe
                foreach ($requestData->file('media_path') as $file) {
                    // Crée une requête factice pour chaque fichier
                    $fakeRequest = new Request();
                    $fakeRequest->files->set('media_path', $file);

                    // Upload du fichier via ton service
                    $mediaPath = $this->uploadFileManagerService->uploadDefaultFile(
                        $fakeRequest,
                        'media_path',
                        ['jpg', 'jpeg', 'png', 'webp'],
                        'MEDIA',
                        "MEDIA",
                        "MEDIA"
                    );

                    // Gérer les erreurs éventuelles
                    $catchError = $this->uploadFileManagerService->handleFileUploadError($mediaPath, 'jpg, jpeg, png, webp');
                    if ($catchError !== null) {
                        return $this->jsonResponseService->srv_errorResponse(
                            "Erreur : " . $catchError,
                            Response::HTTP_BAD_REQUEST
                        );
                    }

                    // supprimer l'ancien fichier
                    if ($oldMediaPath && Storage::disk('public')->exists($oldMediaPath)) {
                        Storage::disk('public')->delete($oldMediaPath);
                    }

                    $mediaPaths[] = $mediaPath;
                }
            }


            // Enregistrer chaque média
            $medias = [];

            foreach ($mediaPaths as $path) {
                $medias[] = $media->update([
                    "galerie_title" => $title,
                    "media_path" => $path ?? $oldMediaPath,
                ]);
            }

            return $this->jsonResponseService->srv_successResponseWithData(
                count($medias) . " média(s) mis à jour avec succès",
                $media,
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            $this->customLogService->error(
                "Une erreur est survenue lors de la mise à jour du media",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la mise à jour du media",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    public function srv_deleteMedia($slug): JsonResponse
    {
        try {

            $media = $this->galerieModel->where('slug', $slug)->firstOrFail();
            $media->delete();
            return $this->jsonResponseService->srv_successResponse(
                "Media supprimé avec succès",
                Response::HTTP_OK,
            );
        } catch (ModelNotFoundException $me) {
            $this->customLogService->error(
                "Une erreur est survenue lors de la suppression du media",
                $me
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Media non trouvé @: { $slug }",
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            $this->customLogService->error(
                "Une erreur est survenue lors de la suppression du media",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la suppression du media",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

}
