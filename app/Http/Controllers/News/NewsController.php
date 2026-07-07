<?php

namespace App\Http\Controllers\News;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\News\NewsService;
use App\Http\Controllers\Controller;

class NewsController extends Controller
{
    public function __construct(
        private readonly NewsService $newsService,
    ){}

    public function ctrl_getNews(Request $requestData): JsonResponse
    {
        return $this->newsService->srv_getNews($requestData);
    }

    public function ctrl_getRecentNews(): JsonResponse
    {
        return $this->newsService->srv_getRecentNews();
    }

    public function ctrl_getNewsLimited(): JsonResponse
    {
        return $this->newsService->srv_getNewsLimited();
    }

    public function ctrl_getNewsDetails($slug): JsonResponse
    {
        return $this->newsService->srv_getNewsBySlug($slug);
    }

    public function ctrl_createNews(Request $requestData): JsonResponse
    {
        return $this->newsService->srv_createNews($requestData);
    }

    public function ctrl_updateNews(Request $requestData, $slug): JsonResponse
    {
        return $this->newsService->srv_updateNews($requestData, $slug);
    }

    public function ctrl_deleteNews($slug): JsonResponse
    {
        return $this->newsService->srv_deleteNews($slug);
    }


    public function ctrl_deleteNewsPermanently($slug): JsonResponse
    {
        return $this->newsService->srv_deleteNewsPermanently($slug);
    }

    public function ctrl_restoreNews($slug): JsonResponse
    {
        return $this->newsService->srv_restoreNews($slug);
    }

    public function ctrl_updateNewsState($slug, $state): JsonResponse
    {
        return $this->newsService->srv_updateNewsState($slug, $state);
    }

    public function ctrl_getRapportHebdomadaire(Request $request): JsonResponse
    {
        return $this->newsService->srv_getRapportHebdomadaire($request);
    }

    // filterInNewsDate
    public function ctrl_filterInNewsDate(Request $request): JsonResponse
    {
        return $this->newsService->srv_filterInNewsDate($request);
    }
}
