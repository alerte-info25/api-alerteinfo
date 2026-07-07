<?php

namespace App\Http\Controllers\API\V1\AbonnementsWeb;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\AbonnementWebSite\AbonneWebSiteService;

class AbonnesWebController extends Controller
{
    public function __construct(
        private readonly AbonneWebSiteService $abonneWebSiteService
    ){}

    public function ctrl_getAbonneWebSite(Request $request): JsonResponse
    {
        return $this->abonneWebSiteService->srv_getAbonneWebSite($request);
    }

    public function ctrl_getAbonneWebCategory(): JsonResponse
    {
        return $this->abonneWebSiteService->srv_getAbonneWebCategory();
    }
}

