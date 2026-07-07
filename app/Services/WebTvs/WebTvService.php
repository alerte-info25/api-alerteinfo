<?php

namespace App\Services\WebTvs;

use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use App\Models\WebTvs\WebTvModels;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Services\CustomLogServices\CustomLogService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;


class WebTvService
{
    protected $webTvModels;
    protected $jsonResponseService;
    protected $customLogService;

    public function __construct(
        WebTvModels $webTvModels,
        JsonResponseService $jsonResponseService,
        CustomLogService $customLogService
    ) {
        $this->webTvModels = $webTvModels;
        $this->jsonResponseService = $jsonResponseService;
        $this->customLogService = $customLogService;
    }


    public function srv_getWebTv($requestData): JsonResponse
    {
        try {
            // Récupérer les paramètres de pagination
            $page = $requestData->input('page', 1); // Page actuelle (par défaut : 1)
            $perPage = $requestData->input('per_page', 20); // Nombre d'éléments par page (par défaut : 10)

            $webTvs = $this->webTvModels->paginate($perPage, ['*'], 'page', $page);

            $webTvsDataFormated  = $webTvs->getCollection()->map(function ($webTv) {
                return [
                    'id' => $webTv->id,
                    'title' => $webTv->title,
                    'description' => $webTv->description,
                    'video_keys' => $webTv->video_keys,
                    'slug' => $webTv->slug,
                    'created_at' => $webTv->created_at,
                    'updated_at' => $webTv->updated_at,
                ];
            });


            return $this->jsonResponseService->srv_successResponseWithData(
                "WebTV récupérés avec succès",
                [
                    "webTvs" => $webTvsDataFormated,
                    "pagination" => [
                        "total" => $webTvs->total(),
                        "per_page" => $webTvs->perPage(),
                        "current_page" => $webTvs->currentPage(),
                        "last_page" => $webTvs->lastPage(),
                    ]
                ],
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            $this->customLogService->error(
                "Une erreur est survenue lors de la récupération des webTV",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la récupération des webTV",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_getWebTvLimited(): JsonResponse
    {
        try {
            $webTvs = $this->webTvModels->limit(4)->get();
            return $this->jsonResponseService->srv_successResponseWithData(
                "WebTV récupérés avec succès",
                $webTvs,
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            $this->customLogService->error(
                "Une erreur est survenue lors de la récupération des webTV",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la récupération des webTV",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createWebTv($requestData): JsonResponse
    {
        DB::beginTransaction();
        try {
            $webTv = $this->webTvModels->create([
                "title" => $requestData->title,
                "description" => $requestData->description,
                "video_keys" => $requestData->video_keys,
                "slug" => Str::uuid(),
            ]);
            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "WebTV créé avec succès",
                $webTv,
                Response::HTTP_CREATED,
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogService->error(
                "Une erreur est survenue lors de la création du webTV",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la création du webTV",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateWebTv($slug, $requestData): JsonResponse
    {
        DB::beginTransaction();
        try {
            $webTv = $this->webTvModels->where("slug", $slug)->firstOrFail();
            $webTv->update([
                "title" => $requestData->title,
                "description" => $requestData->description,
                "video_keys" => $requestData->video_keys,
            ]);
            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "WebTV mis à jour avec succès",
                $webTv,
                Response::HTTP_OK,
            );
        } catch(ModelNotFoundException $me){
            DB::rollBack();
            $this->customLogService->error(
                "Une erreur est survenue lors de la mise à jour du webTV",
                $me
            );
            return $this->jsonResponseService->srv_errorResponse(
                "WebTV non trouvé @: { $slug }",
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogService->error(
                "Une erreur est survenue lors de la mise à jour du webTV",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la mise à jour du webTV",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteWebTv($slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $webTv = $this->webTvModels->where("slug", $slug)->firstOrFail();
            $webTv->delete();
            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "WebTV supprimé avec succès",
                $webTv,
                Response::HTTP_OK,
            );
        } catch(ModelNotFoundException $me){
            DB::rollBack();
            $this->customLogService->error(
                "Une erreur est survenue lors de la suppression du webTV",
                $me
            );
            return $this->jsonResponseService->srv_errorResponse(
                "WebTV non trouvé @: { $slug }",
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogService->error(
                "Une erreur est survenue lors de la suppression du webTV",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la suppression du webTV",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateWebTvStatus($slug, $status): JsonResponse
    {
        DB::beginTransaction();
        try {
            $webTv = $this->webTvModels->where("slug", $slug)->firstOrFail();
            $webTv->update([
                "status" => $status,
            ]);
            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "WebTV mis à jour avec succès",
                $webTv,
                Response::HTTP_OK,
            );
        } catch(ModelNotFoundException $me){
            DB::rollBack();
            $this->customLogService->error(
                "Une erreur est survenue lors de la mise à jour du webTV",
                $me
            );
            return $this->jsonResponseService->srv_errorResponse(
                "WebTV non trouvé @: { $slug }",
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogService->error(
                "Une erreur est survenue lors de la mise à jour du webTV",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la mise à jour du webTV",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

}
