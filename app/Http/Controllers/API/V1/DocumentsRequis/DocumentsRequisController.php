<?php

namespace App\Http\Controllers\API\V1\DocumentsRequis;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\DocumentsRequisServices\DocumentsRequisService;


class DocumentsRequisController extends Controller
{
    public function __construct(
        private readonly DocumentsRequisService $documentsRequisService
    ) {}

    public function ctrl_getDocumentsRequis(Request $request): JsonResponse
    {
        return $this->documentsRequisService->srv_getDocumentsRequis($request);
    }

    public function ctrl_storeDocumentsRequis(Request $request): JsonResponse
    {
        return $this->documentsRequisService->srv_createDocumentsRequis($request);
    }

    public function ctrl_updateDocumentsRequis(Request $request, $slug): JsonResponse
    {
        return $this->documentsRequisService->srv_updateDocumentsRequis($request, $slug);
    }

    public function ctrl_destroyDocumentsRequis($slug): JsonResponse
    {
        return $this->documentsRequisService->srv_deleteDocumentsRequis($slug);
    }
}

