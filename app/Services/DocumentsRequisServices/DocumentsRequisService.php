<?php

namespace App\Services\DocumentsRequisServices;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Services\UserLogServices\UserLogService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\DocumentsRequisModels\DocumentsRequisModel;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;


class DocumentsRequisService
{
    /**
     * @var JsonResponseService
     */
    private $jsonResponseService;

    /**
     * @var CustomLogError
     */
    private $customLogError;

    /**
     * @var UserLogService
     */
    private $userLogService;

    /**
     * @var DocumentsRequisModel
     */
    private $documentsRequisModel;

    /**
     * @var CodeGeneratorService
     */
    private $codeGeneratorService;

    public function __construct(
        JsonResponseService $jsonResponseService,
        CustomLogError $customLogError,
        UserLogService $userLogService,
        DocumentsRequisModel $documentsRequisModel,
        CodeGeneratorService $codeGeneratorService
    ) {
        $this->jsonResponseService = $jsonResponseService;
        $this->customLogError = $customLogError;
        $this->userLogService = $userLogService;
        $this->documentsRequisModel = $documentsRequisModel;
        $this->codeGeneratorService = $codeGeneratorService;
    }

    public function srv_getDocumentsRequis(Request $request)
    {
        try {
            // pagination parameters
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);

            // get documents requis list
            $documentsRequis = $this->documentsRequisModel->paginate($limit, ['*'], 'page', $page);
            $documentsRequisFormatted = collect($documentsRequis)->items()->map(function ($documentsRequis) {
                return [
                    'document_code_unique' => $documentsRequis->document_code_unique,
                    'document_name' => $documentsRequis->document_name,
                    'slug' => $documentsRequis->slug,
                    'created_at' => $documentsRequis->created_at,
                    'updated_at' => $documentsRequis->updated_at,
                ];
            });
            return $this->jsonResponseService->successResponseWithData(
                'Documents requis récupérés avec succès',
                [
                    'documentsRequisList' => $documentsRequisFormatted,
                    'paginations' => [
                        'total' => $documentsRequis->total(),
                        'per_page' => $documentsRequis->perPage(),
                        'current_page' => $documentsRequis->currentPage(),
                        'last_page' => $documentsRequis->lastPage(),
                    ],
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Erreur lors de la récupération des documents requis",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Une erreur est survenue lors de la récupération des documents requis',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createDocumentsRequis(Request $request)
    {
        DB::beginTransaction();
        try {

            $documentCodeUnique = $this->codeGeneratorService->generateDefaultCodeUnique(
                'documents_requis_models',
                'document_code_unique',
                'DRM'
            );


            $documentsRequis = $this->documentsRequisModel->create([
                'document_code_unique' => $documentCodeUnique,
                'document_name' => $request->document_name,
                'slug' => Str::uuid(),
            ]);

            DB::commit();
            return $this->jsonResponseService->successResponseWithData(
                'Document requis créé avec succès',
                $documentsRequis,
                Response::HTTP_CREATED,
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError(
                "Erreur lors de la création du document requis",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Une erreur est survenue lors de la création du document requis',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateDocumentsRequis(Request $request, $slug)
    {
        DB::beginTransaction();
        try {

            $documentsRequis = $this->documentsRequisModel->where('slug', $slug)->firstOrFail();

            $documentsRequis->update([
                'document_name' => $request->document_name,
                'slug' => $request->slug,
            ]);

            DB::commit();
            return $this->jsonResponseService->successResponseWithData(
                'Document requis modifié avec succès',
                $documentsRequis,
                Response::HTTP_OK,
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->jsonResponseService->errorResponse(
                'Document requis non trouvé',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError(
                "Erreur lors de la modification du document requis",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Une erreur est survenue lors de la modification du document requis',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteDocumentsRequis($slug)
    {
        DB::beginTransaction();
        try {

            $documentsRequis = $this->documentsRequisModel->where('slug', $slug)->firstOrFail();

            $documentsRequis->delete();

            DB::commit();
            return $this->jsonResponseService->successResponseWithData(
                'Document requis supprimé avec succès',
                $documentsRequis,
                Response::HTTP_OK,
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->jsonResponseService->errorResponse(
                'Document requis non trouvé',
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError(
                "Erreur lors de la suppression du document requis",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Une erreur est survenue lors de la suppression du document requis',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }



}

