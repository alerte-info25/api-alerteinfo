<?php

namespace App\Services\News;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\News\NewsRubriqueModel;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;



class NewsRubriqueService {
    public function __construct(
        private readonly NewsRubriqueModel $newsRubriqueModel,
        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogError $customLogError,
        private readonly UploadFileManagerService $uploadFileManagerService,
        private readonly CodeGeneratorService $codeGeneratorService,
    ) {
    }

    public function srv_getNewsRubrique(): JsonResponse
    {
        try {
            $newsRubrique = $this->newsRubriqueModel->get();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Rubriques récupérées avec succès",
                $newsRubrique,
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la récupération des rubriques",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la récupération des rubriques",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createNewsRubrique($requestData): JsonResponse
    {
        DB::beginTransaction();
        try {
            $codeUnique = $this->codeGeneratorService->generateDefaultCodeUnique(
                "news_rubrique_models",
                "rubrique_code_unique",
                "NEWS-RUB-"
            );
            $newsRubrique = $this->newsRubriqueModel->create([
                "rubrique_code_unique" => $codeUnique,
                "rubrique_name" => $requestData->rubrique_name,
                "slug" => Str::uuid(),
            ]);
            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Rubrique créée avec succès",
                $newsRubrique,
                Response::HTTP_CREATED,
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la création de la rubrique",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la création de la rubrique",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateNewsRubrique($requestData, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $newsRubrique = $this->newsRubriqueModel->where('slug', $slug)->firstOrFail();
            $newsRubrique->update([
                "rubrique_name" => $requestData->rubrique_name,
                "slug" => Str::uuid(),
            ]);
            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Rubrique mise à jour avec succès",
                $newsRubrique,
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la mise à jour de la rubrique",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la mise à jour de la rubrique",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteNewsRubrique($slug): JsonResponse
    {
        try {
            $newsRubrique = $this->newsRubriqueModel->where('slug', $slug)->firstOrFail();
            $newsRubrique->delete();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Rubrique supprimée avec succès",
                $newsRubrique,
                Response::HTTP_OK,
            );
        } catch(ModelNotFoundException $th){
            $this->customLogError->logError(
                "Aucune rubrique trouvée",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Aucune rubrique trouvée",
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la suppression de la rubrique",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la suppression de la rubrique",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

}
