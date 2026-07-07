<?php

namespace App\Services\LicenceServices;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\LicenceModels\LicenceDocumentsModel;
use App\Models\DocumentsRequisModels\DocumentsRequisModel;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;


class LicenceDocumentService
{

    public function __construct(
        private readonly LicenceDocumentsModel $licenceDocumentModel,
        private readonly CustomLogError $customLogError,
        private readonly JsonResponseService $jsonResponseService,
        private readonly UploadFileManagerService $uploader,
        private readonly DocumentsRequisModel $documentsRequisModel
    ) {
    }


    // store licence document
    public function srv_storeLicenceDocument(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $result = $request->document_path ? $this->uploadMedia($request->document_path) : null;

            $documents = $this->documentsRequisModel::where('document_code_unique', $request->document_code_unique)->firstOrFail();
            if($documents == null){
                return $this->jsonResponseService->errorResponse(
                    "Type de document non trouvé", // Message générique
                    Response::HTTP_NOT_FOUND, // Code HTTP d'erreur 404
                );
            }



            $licenceDocument = $this->licenceDocumentModel->create([
                'licence_code_unique' => $request->licence_code_unique,
                'document_code_unique' => $request->document_code_unique,
                'document_path' => $result['path'],
                'type' => $result['fileType'],
                'slug' => Str::uuid(),
            ]);

            DB::commit();

            return $this->jsonResponseService->successResponseWithData(
                "Document de la licence ajouté avec succès",
                $licenceDocument,
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError(
                'Erreur lors de l\'ajout du document de la licence',
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de l\'ajout du document de la licence',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // update licence document
    public function srv_updateLicenceDocument(Request $request, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {


            $documents = $this->documentsRequisModel::where('document_code_unique', $request->document_code_unique)->firstOrFail();
            if($documents == null){
                return $this->jsonResponseService->errorResponse(
                    "Type de document non trouvé", // Message générique
                    Response::HTTP_NOT_FOUND, // Code HTTP d'erreur 404
                );
            }

            $licenceDocument = $this->licenceDocumentModel->where('slug', $slug)->firstOrFail();

            $oldDocumentPath = $licenceDocument->document_path;
            $oldDocumentType = $licenceDocument->type;

            $documentPath =  $request->document_path ? $this->uploadMedia($request->document_path) : null;

        

            $licenceDocument = $this->licenceDocumentModel->where('slug', $slug)
            ->update([
                'document_code_unique' => $request->document_code_unique,
                'document_path' => $documentPath['path'] ?? $oldDocumentPath,
                'type' =>  $documentPath['fileType'] ?? $oldDocumentType,
            ]);

            DB::commit();

            // delete old document
            if($documentPath['path']){
                $this->uploader->deleteFile($oldDocumentPath);
            }

            return $this->jsonResponseService->successResponse(
                "Document de la licence modifié avec succès",
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            // delete old document
            $this->uploader->deleteFile($documentPath['path']);
            $this->customLogError->logError(
                'Erreur lors de l\'ajout du document de la licence',
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de l\'ajout du document de la licence',
                $th->getMessage()
            );
        }
    }

    // delete document
    public function srv_deleteLicenceDocument($slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $document = $this->licenceDocumentModel->where('slug', $slug)->firstOrFail();
            $oldDocumentPath = $document->document_path;
            $document->delete();
            DB::commit();
            // delete old document
            $this->uploader->deleteFile($oldDocumentPath);
            return $this->jsonResponseService->successResponse(
                "Document supprimé avec succès",
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError(
                'Erreur lors de la suppression du document',
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la suppression du document',
                $th->getMessage()
            );
        }
    }



    /**
     * Summary of uploadMedia
     * @param mixed $document_path
     * @throws \RuntimeException
     * @return array{fileType: mixed, path: string}
     */
    private function uploadMedia($document_path):array
    {
        $mainFolder = "licence/attachments/" . now()->format('Y');
        $result = $this->uploader->uploadDefaultFile(
            $document_path,
            $mainFolder
        );
        $error = $this->uploader->handleFileUploadError($result);
        if ($error) {
            throw new \RuntimeException($error);
        }
        return [
            "path" => $result['fileData']['path'] ?? null,
            "fileType" => $result['fileData']['fileType'] ?? null,
        ];
    }


}
