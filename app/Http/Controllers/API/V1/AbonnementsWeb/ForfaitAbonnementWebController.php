<?php

namespace App\Http\Controllers\API\V1\AbonnementsWeb;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AbonnementsWebModels\AbonnementWebForfaitsModels;
use App\Services\AbonnementWebSite\ForfaitAbonnementWebSiteService;

class ForfaitAbonnementWebController extends Controller
{
    public function __construct(
        private readonly ForfaitAbonnementWebSiteService $service
    )
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function ctrl_getForfaitAbonnementWebSite(Request $request)
    {
        return $this->service->srv_getForfaitAbonnementWebSite($request);
    }

    public function ctrl_getForfaitAbonnementWebSiteFormData()
    {
        return $this->service->srv_getForfaitAbonnementFormData();
    }
    
    public function ctrl_getForfaitAbonnementWebSiteFormData2()
    {
        return $this->service->srv_getForfaitAbonnementFormData2();
    }

    
    /**
     * Store a newly created resource in storage.
     */
    public function ctrl_createForfaitAbonnementWebSite(Request $request)
    {
        return $this->service->srv_createForfaitAbonnementWebSite($request);
    }

    /**
     * Update the specified resource in storage.
     */
    public function ctrl_updateForfaitAbonnementWebSite(Request $request, string $slug)
    {
        return $this->service->srv_updateForfaitAbonnementWebSite($request, $slug);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function ctrl_deleteForfaitAbonnementWebSite(string $slug)
    {
        return $this->service->srv_deleteForfaitAbonnementWebSite($slug);
    }
}


