<?php

namespace App\Services\News;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Models\News\NewsRubriqueCategorieModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;



class NewsRubriqueCategorieService {
    public function __construct(
        private readonly NewsRubriqueCategorieModel $newsRubriqueCategorieModel,
        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogError $customLogError,
        private readonly UploadFileManagerService $uploadFileManagerService,
        private readonly CodeGeneratorService $codeGeneratorService,
    ) {
    }

    public function srv_getNewsRubriqueCategorie(): JsonResponse
    {
        try {
            $newsRubriqueCategorie = $this->newsRubriqueCategorieModel->get();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Rubriques catégories récupérées avec succès",
                $newsRubriqueCategorie,
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la récupération des rubriques catégories",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la récupération des rubriques catégories",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createNewsRubriqueCategorie(Request $requestData): JsonResponse
    {
        DB::beginTransaction();
        try {
            $codeUnique = $this->codeGeneratorService->generateDefaultCodeUnique(
                "news_rubrique_categorie_models",
                "rubrique_categorie_code_unique",
                "NEWS-RUB-CAT-"
            );
            $newsRubriqueCategorie = $this->newsRubriqueCategorieModel->create([
                "rubrique_categorie_code_unique" => $codeUnique,
                "rubrique_categorie_name" => $requestData->rubrique_categorie_name,
                "slug" => Str::uuid(),
            ]);
            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Rubrique catégorie créée avec succès",
                $newsRubriqueCategorie,
                Response::HTTP_CREATED,
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la création de la rubrique catégorie",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la création de la rubrique catégorie",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateNewsRubriqueCategorie(Request $requestData, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $newsRubriqueCategorie = $this->newsRubriqueCategorieModel->where('slug', $slug)->firstOrFail();
            $newsRubriqueCategorie->update([
                "rubrique_category_name" => $requestData->rubrique_category_name,
                "slug" => Str::uuid(),
            ]);
            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Rubrique catégorie mise à jour avec succès",
                $newsRubriqueCategorie,
                Response::HTTP_OK,
            );
        } catch (ModelNotFoundException $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la mise à jour de la rubrique catégorie",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la mise à jour de la rubrique catégorie",
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la mise à jour de la rubrique catégorie",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la mise à jour de la rubrique catégorie",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteNewsRubriqueCategorie($slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $newsRubriqueCategorie = $this->newsRubriqueCategorieModel->where('slug', $slug)->firstOrFail();
            $newsRubriqueCategorie->delete();
            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Rubrique catégorie supprimée avec succès",
                $newsRubriqueCategorie,
                Response::HTTP_OK,
            );
        } catch (ModelNotFoundException $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la suppression de la rubrique catégorie",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la suppression de la rubrique catégorie",
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la suppression de la rubrique catégorie",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la suppression de la rubrique catégorie",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
