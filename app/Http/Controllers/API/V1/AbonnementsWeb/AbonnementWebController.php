<?php

namespace App\Http\Controllers\API\V1\AbonnementsWeb;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\AbonnementWebSite\AbonnementWebSiteService;

class AbonnementWebController extends Controller
{
    public function __construct(
        private readonly AbonnementWebSiteService $abonnementWebSiteService
    )
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function ctrl_getAbonnementWebSite(Request $request): JsonResponse
    {
        return $this->abonnementWebSiteService->srv_getAbonnementWebSite($request);
    }
    
    public function ctrl_getAbonnementWebFormData(): JsonResponse
    {
        return $this->abonnementWebSiteService->srv_getAbonnementWebFormData();
    }
    public function ctrl_storeAbonnementWebSite(Request $request): JsonResponse
    {
        return $this->abonnementWebSiteService->srv_storeAbonnementWebSite($request);
    }
    
    public function ctrl_updateAbonnementWebValidityDate(Request $request)
    {
        // check abonnement_code
        if(empty($request->abonnement_code)){
            return response()->json([
                'code' => 302,
                'status' => 'erreur',
                'message' => 'Le code d\'abonnement est obligatoire'
            ]);
        }
        // check validation_value
        if(empty($request->validation_value)){
            return response()->json([
                'code' => 302,
                'status' => 'erreur',
                'message' => 'La valeur de validation est obligatoire(la durée)'
            ]);
        }
        return $this->abonnementWebSiteService->srv_updateAbonnementWebValidityDate($request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
