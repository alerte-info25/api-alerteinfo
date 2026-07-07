<?php


namespace App\Http\Controllers\API\V1\TrancheAge;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TrancheAgeServices\TrancheAgeService;
use App\Services\JsonResponseServices\JsonResponseService;


class TrancheAgeController extends Controller
{
    public function __construct(
        private readonly TrancheAgeService $trancheAgeService,
        private readonly JsonResponseService $jsonResponseService
    ) {}

    public function ctrl_getTrancheAgeList(Request $request): JsonResponse
    {
        return $this->trancheAgeService->srv_getTrancheAge($request);
    }

    public function ctrl_storeTrancheAge(Request $request): JsonResponse
    {
        return $this->trancheAgeService->srv_createTrancheAge($request);
    }

    public function ctrl_updateTrancheAge(Request $request, $slug): JsonResponse
    {
        if(empty($slug))
        {
            return $this->jsonResponseService->errorResponse(
                'Le slug est obligatoire',
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->trancheAgeService->srv_updateTrancheAge($request, $slug);
    }

    public function ctrl_destroyTrancheAge($slug): JsonResponse
    {
        if(empty($slug))
        {
            return $this->jsonResponseService->errorResponse(
                'Le slug est obligatoire',
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->trancheAgeService->srv_destroyTrancheAge($slug);
    }
}
