<?php

namespace App\Http\Controllers\API\V1\Licence;

use Illuminate\Http\Request;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LicenceServices\LicenceService;
use App\Services\JsonResponseServices\JsonResponseService;




class LicenceController extends Controller
{
    public function __construct(
        private readonly LicenceService $licenceService,
        private readonly JsonResponseService $jsonResponseService
    ) {}

    public function ctrl_getNouvelleLicenceList(Request $request): JsonResponse
    {
        return $this->licenceService->srv_getNouvelleLicenceList($request);
    }



    public function ctrl_getRenouvellementLicenceList(Request $request): JsonResponse
    {
        return $this->licenceService->srv_getRenouvellementLicenceList($request);
    }

    public function ctrl_getLicenceDetails($slug): JsonResponse
    {
        return $this->licenceService->srv_getLicenceDetails($slug);
    }

    public function ctrl_updateLicence(Request $request, string $slug): JsonResponse
    {
        return $this->licenceService->srv_updateLicence($request, $slug);
    }

    public function ctrl_checkOrganisationCodeValidity($code,$categoryCodeUnique): JsonResponse
    {
        if(empty($code)){
            return $this->jsonResponseService->errorResponse(
                'Le code de l\'organisation est obligatoire',
                Response::HTTP_BAD_REQUEST
            );
        }
        if(empty($categoryCodeUnique)){
            return $this->jsonResponseService->errorResponse(
                'Le code de la catégorie est obligatoire',
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->licenceService->srv_checkOrganisationCodeValidity($code,$categoryCodeUnique);
    }



    // edit licence
    public function ctrl_getCurrentLicence($slug): JsonResponse
    {
        if(empty($slug)){
            return $this->jsonResponseService->errorResponse(
                'Le slug est obligatoire',
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->licenceService->srv_getCurrentLicence($slug);
    }

    
    public function ctrl_getTableData(): JsonResponse
    {
        return $this->licenceService->srv_getTableData();
    }

    public function ctrl_getLicenceListFilter(Request $request): JsonResponse
    {
        return $this->licenceService->srv_getLicenceListFilter($request);
    }


    public function ctrl_updateLicenceDocuments(Request $request, $slug): JsonResponse
    {
        return $this->licenceService->srv_updateLicenceDocuments($request, $slug);
    }
}

