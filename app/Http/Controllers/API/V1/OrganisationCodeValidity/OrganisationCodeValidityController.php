<?php

namespace App\Http\Controllers\API\V1\OrganisationCodeValidity;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Services\OrganisationCodeValidityServices\OrganisationCodeValidityService;
use App\Services\JsonResponseServices\JsonResponseService;

class OrganisationCodeValidityController extends Controller
{
    public function __construct(
        private readonly OrganisationCodeValidityService $organisationCodeValidityService,
        private readonly JsonResponseService $jsonResponseService
    ) {}


    public function ctrl_getOrganisationCodeValidityList(): JsonResponse
    {
        return $this->organisationCodeValidityService->srv_getOrganisationCodeValidity();
    }

    public function ctrl_storeOrganisationCodeValidity(Request $request): JsonResponse
    {
        return $this->organisationCodeValidityService->srv_createOrganisationCodeValidity($request);
    }

    public function ctrl_updateOrganisationCodeValidity(Request $request, $slug): JsonResponse
    {
        if(empty($slug))
        {
            return $this->jsonResponseService->errorResponse(
                'Le slug est obligatoire',
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->organisationCodeValidityService->srv_updateOrganisationCodeValidity($request, $slug);
    }

    public function ctrl_destroyOrganisationCodeValidity($slug): JsonResponse
    {
        if(empty($slug))
        {
            return $this->jsonResponseService->errorResponse(
                'Le slug est obligatoire',
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->organisationCodeValidityService->srv_destroyOrganisationCodeValidity($slug);
    }
}
