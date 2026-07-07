<?php

namespace App\Http\Controllers\API\V1\Partners;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Services\PartnerServices\PartnerService;
use App\Services\JsonResponseServices\JsonResponseService;

class FiaPartnersController extends Controller
{
    public function __construct(
        private readonly PartnerService $partnerService,
        private readonly JsonResponseService $jsonResponseService
    ) {
    }

    public function ctrl_getPartner(Request $request): JsonResponse
    {
        return $this->partnerService->srv_getAllPartners($request);
    }


    public function ctrl_createPartner(Request $request): JsonResponse
    {
        $validatePartnerForm = $this->validatePartnerForm($request);
        if($validatePartnerForm->getStatusCode() != Response::HTTP_OK) {
            return $validatePartnerForm;
        }
        return $this->partnerService->srv_createPartner($request);
    }


    public function ctrl_updatePartner(Request $request, string $uuid): JsonResponse
    {
        
        if(empty($uuid)) {
            return $this->jsonResponseService->errorResponse(
                "L'uuid est obligatoire",
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->partnerService->srv_updatePartner($request, $uuid);
    }

    
    public function ctrl_enableOrDisablePartner(string $uuid): JsonResponse
    {
        if(empty($uuid)) {
            return $this->jsonResponseService->errorResponse(
                "L'uuid est obligatoire",
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->partnerService->srv_enableOrDisablePartner($uuid);
    }

    public function ctrl_deletePartner(string $uuid): JsonResponse
    {
        if(empty($uuid)) {
            return $this->jsonResponseService->errorResponse(
                "L'uuid est obligatoire",
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->partnerService->srv_deletePartner($uuid);
    }

    // validate carousel form
    private function validatePartnerForm($request): JsonResponse
    {
        if(empty($request->media_path)) {
            return $this->jsonResponseService->errorResponse(
                "L'image du partenaire est obligatoire",
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->jsonResponseService->successResponse(
            "Le partenaire a été créé avec succès",
            Response::HTTP_OK
        );
    }
}
