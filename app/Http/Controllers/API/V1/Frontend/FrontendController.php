<?php

namespace App\Http\Controllers\API\V1\Frontend;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Services\FrontendServices\FrontendService;
use App\Services\JsonResponseServices\JsonResponseService;

class FrontendController extends Controller
{
    public function __construct(
        private readonly FrontendService $frontendService,
        private readonly JsonResponseService $jsonResponseService
    ) {}


    public function ctrl_getHomePageData(): JsonResponse
    {
        return $this->frontendService->srv_getHomePageData();
    }

    public function ctrl_getInitLicenceFormData(Request $request): JsonResponse
    {
        return $this->frontendService->srv_getInitLicenceFormData();
    }

    public function ctrl_checkOrganisationCodeValidity($code,$categoryCodeUnique): JsonResponse
    {
        return $this->frontendService->srv_checkOrganisationCodeValidity($code,$categoryCodeUnique);
    }

    public function ctrl_checkLicenceCodeUnique(Request $request): JsonResponse
    {
        return $this->frontendService->srv_checkLicenceCodeUnique($request);
    }

    
    public function ctrl_checkLicenceCodeUniqueToPrint(Request $request): JsonResponse
    {
        return $this->frontendService->srv_checkLicenceCodeUniqueToPrint($request);
    }

    public function ctrl_generateLicence(Request $request): JsonResponse
    {
        if(empty($request->categorie_code_unique)){
            return $this->jsonResponseService->errorResponse(
                'La catégorie est obligatoire',
                Response::HTTP_BAD_REQUEST
            );
        }

        if(empty($request->fonction_code_unique)){
            return $this->jsonResponseService->errorResponse(
                'La fonction est obligatoire',
                Response::HTTP_BAD_REQUEST
            );
        }

        if(empty($request->organisation_code_unique)){
            return $this->jsonResponseService->errorResponse(
                'Erreur:  le code de la categorie est obligatoire',
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->frontendService->srv_generateLicence($request);
    }

    public function ctrl_getCurrentLicence($slug)
    {
        if(empty($slug)){
            return $this->jsonResponseService->errorResponse(
                'Le slug de la licence est obligatoire',
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->frontendService->srv_getCurrentLicence($slug);
    }

    public function ctrl_getLicenceDetails($slug)
    {
        if(empty($slug)){
            return $this->jsonResponseService->errorResponse(
                'Le slug de la licence est obligatoire',
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->frontendService->srv_getLicenceDetails($slug);
    }

    public function ctrl_checkTrancheAge($trancheAge): JsonResponse
    {
        return $this->frontendService->srv_checkTrancheAge($trancheAge);
    }

    public function ctrl_updateLicenceInfoPersonnel(Request $request, $slug): JsonResponse
    {
        return $this->frontendService->srv_updateLicenceInfoPersonnel($request, $slug);
    }

    public function ctrl_updateLicenceInfoOrganisation(Request $request, $slug): JsonResponse
    {
        return $this->frontendService->srv_updateLicenceInfoOrganisation($request, $slug);
    }



    // *************************************$ DOCUMENTS REQUIS *************************************

    public function ctrl_storeLicenceDocuments(Request $request): JsonResponse
    {
        return $this->frontendService->srv_storeLicenceDocuments($request);
    }


    public function ctrl_updateLicenceDocuments(Request $request, $slug): JsonResponse
    {
        return $this->frontendService->srv_updateLicenceDocuments($request, $slug);
    }

    public function ctrl_destroyLicenceDocuments($slug): JsonResponse
    {
        return $this->frontendService->srv_deleteLicenceDocuments($slug);
    }

    // *************************************$ END DOCUMENTS REQUIS *************************************



    // *************************************$ BADGE *************************************

    public function ctrl_getAvailableEventsList(): JsonResponse
    {
        return $this->frontendService->srv_getBadgeActiveList();
    }

    public function ctrl_createBadge(Request $request): JsonResponse
    {
        return $this->frontendService->srv_createBadge($request);
    }

    // *************************************$ END BADGE *************************************



}

