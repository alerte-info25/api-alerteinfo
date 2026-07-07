<?php

namespace App\Http\Controllers\News;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\News\NewsRubriqueCategorieService;
use Illuminate\Http\JsonResponse;

class NewsRubriqueCategorieController extends Controller
{
    public function __construct(
        private readonly NewsRubriqueCategorieService $newsRubriqueCategorieService,
    ){}

    public function ctrl_getNewsRubriqueCategorie(): JsonResponse
    {
        return $this->newsRubriqueCategorieService->srv_getNewsRubriqueCategorie();
    }

    public function ctrl_createNewsRubriqueCategorie(Request $requestData): JsonResponse
    {
        return $this->newsRubriqueCategorieService->srv_createNewsRubriqueCategorie($requestData);
    }

    public function ctrl_updateNewsRubriqueCategorie(Request $requestData, $slug): JsonResponse
    {
        return $this->newsRubriqueCategorieService->srv_updateNewsRubriqueCategorie($requestData, $slug);
    }

    public function ctrl_deleteNewsRubriqueCategorie($slug): JsonResponse
    {
        return $this->newsRubriqueCategorieService->srv_deleteNewsRubriqueCategorie($slug);
    }
}
