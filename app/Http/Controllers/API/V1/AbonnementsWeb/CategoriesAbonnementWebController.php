<?php

namespace App\Http\Controllers\API\V1\AbonnementsWeb;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\AbonnementWebSite\CategorieAbonneWebSiteService;

class CategoriesAbonnementWebController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct(
        private readonly CategorieAbonneWebSiteService $service
    ){}


    public function ctrl_getCategorieAbonneWebSite(Request $request): JsonResponse
    {
        return $this->service->srv_getCategorieAbonneWebSite($request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function ctrl_createCategorieAbonneWebSite(Request $request): JsonResponse
    {
        return $this->service->srv_createCategorieAbonneWebSite($request);
    }

    /**
     * Update the specified resource in storage.
     */
    public function ctrl_updateCategorieAbonneWebSite(Request $request, string $slug): JsonResponse
    {
        return $this->service->srv_updateCategorieAbonneWebSite($request, $slug);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function ctrl_destroyCategorieAbonneWebSite(string $slug): JsonResponse
    {
        return $this->service->srv_deleteCategorieAbonneWebSite($slug);
    }
}
