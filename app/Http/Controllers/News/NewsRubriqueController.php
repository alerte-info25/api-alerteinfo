<?php

namespace App\Http\Controllers\News;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\News\NewsRubriqueService;

class NewsRubriqueController extends Controller
{

    public function __construct(
        private readonly NewsRubriqueService $newsRubriqueService,
    ){}

    public function ctrl_getNewsRubrique(): JsonResponse
    {
        return $this->newsRubriqueService->srv_getNewsRubrique();
    }

    public function ctrl_createNewsRubrique(Request $request): JsonResponse
    {
        return $this->newsRubriqueService->srv_createNewsRubrique($request);
    }

    public function ctrl_updateNewsRubrique(Request $request, $slug): JsonResponse
    {
        return $this->newsRubriqueService->srv_updateNewsRubrique($request, $slug);
    }

    public function ctrl_deleteNewsRubrique($slug): JsonResponse
    {
        return $this->newsRubriqueService->srv_deleteNewsRubrique($slug);
    }
}
