<?php
namespace App\Services\FederationServices;

use App\Services\UserLogServices\UserLogService;
use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\FederationModels\FederationModels;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;
use App\Models\CodeValidity\OrganisationCodeValidityModel;

class FederationService
{
    /**
     * @var FederationModels
     */
    private $federationModels;

    /**
     * @var JsonResponseService
     */
    private $jsonResponseService;

    /**
     * @var CustomLogError
     */
    private $customLogError;

    /**
     * @var CodeGeneratorService
     */
    private $codeGeneratorService;

    /**
     * @var UploadFileManagerService
     */
    private $uploadFileManagerService;

    /**
     * @var UserLogService
     */
    private $userLogService;

    /**
     * @var OrganisationCodeValidityModel
     */
    private $organisationCodeValidityModel;
    public function __construct(
        FederationModels $federationModels,
        JsonResponseService $jsonResponseService,
        CustomLogError $customLogError,
        CodeGeneratorService $codeGeneratorService,
        UploadFileManagerService $uploadFileManagerService,
        UserLogService $userLogService,
        OrganisationCodeValidityModel $organisationCodeValidityModel
    ) {
        $this->federationModels = $federationModels;
        $this->jsonResponseService = $jsonResponseService;
        $this->customLogError = $customLogError;
        $this->codeGeneratorService = $codeGeneratorService;
        $this->uploadFileManagerService = $uploadFileManagerService;
        $this->userLogService = $userLogService;
        $this->organisationCodeValidityModel = $organisationCodeValidityModel;
    }

    public function srv_getFederationList(Request $request): JsonResponse
    {
        try {
            // pagination parameters
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);

            // get federation list
            $federationList = $this->federationModels->paginate($limit, ['*'], 'page', $page);
            $federationListFormatted = $federationList->getCollection()->map(function ($federation) {
                return [
                    'federation_code_unique' => $federation->federation_code_unique,
                    'federation_name' => $federation->federation_name,
                    'federation_abreviation' => $federation->federation_abreviation,
                    'federation_code' => $federation->federation_code,
                    'code_validity' => $federation->code_validity,
                    'slug' => $federation->slug
                ];
            });

            return $this->jsonResponseService->successResponseWithData(
                'Liste des federations récupérée avec succès',
                [
                    'federationList' => $federationListFormatted,
                    'paginations' => [
                        'total' => $federationList->total(),
                        'per_page' => $federationList->perPage(),
                        'current_page' => $federationList->currentPage(),
                        'last_page' => $federationList->lastPage(),
                        'from' => $federationList->firstItem(),
                        'to' => $federationList->lastItem()
                    ]
                ],
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->customLogError->logError('Erreur lors de la récupération de la liste des federations', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération de la liste des federations',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createFederation(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $federationCodeUnique = $this->codeGeneratorService->generateDefaultCodeUnique(
                'federation_models',
                'federation_code_unique',
                'FDR'
            );

            $federationCreated = $this->federationModels->create([
                'federation_code_unique' => $federationCodeUnique,
                'federation_name' => $request->federation_name,
                'federation_abreviation' => $request->federation_abreviation,
                'federation_code' => strtoupper(Str::random(6)),
                'code_validity' => now()->addDays(7)->format('Y-m-d H:i:s'),
                'slug' => Str::uuid()
            ]);

            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'create',
                sprintf(
                    "Federation %s créé avec succès par %s à %s",
                    $federationCreated->federation_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'Federation créé avec succès',
                $federationCreated,
                Response::HTTP_CREATED
            );

        } catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la création de la federation', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la création de la federation',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateFederation(Request $request, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $federation = $this->federationModels->where('slug', $slug)->firstOrFail();

            $federation->update([
                'federation_name' => $request->federation_name,
                'federation_abreviation' => $request->federation_abreviation,
            ]);

            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'update',
                sprintf(
                    "Federation %s mise à jour avec succès par %s à %s",
                    $federation->federation_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'Federation mise à jour avec succès',
                $federation,
                Response::HTTP_OK
            );

        }catch (ModelNotFoundException $e) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la mise à jour de la federation', $e);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la mise à jour de la federation',
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la mise à jour de la federation', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la mise à jour de la federation',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteFederation($slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $federation = $this->federationModels->where('slug', $slug)->firstOrFail();

            $federation->delete();

            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'delete',
                sprintf(
                    "Federation %s supprimée avec succès par %s à %s",
                    $federation->federation_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'Federation supprimée avec succès',
                $federation,
                Response::HTTP_OK
            );

        }catch (ModelNotFoundException $e) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la suppression de la federation', $e);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la suppression de la federation',
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la suppression de la federation', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la suppression de la federation',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_refreshFederationCode($slug): JsonResponse
    {
        DB::beginTransaction();
        try {

            $validity = $this->organisationCodeValidityModel->firstOrFail();

            // add validity to a now() + validity
            $validityDate = now()->addDays($validity->validity)->format('Y-m-d H:i:s');

            $federation = $this->federationModels->where('slug', $slug)->firstOrFail();

            $federation->update([
                'code_validity' => $validityDate,
            ]);

            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'update',
                sprintf(
                    "La date de validité du code de la federation %s a été modifiée avec succès par %s à %s",
                    $federation->federation_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'La date de validité du code de la federation modifiée avec succès',
                $federation,
                Response::HTTP_OK
            );

        } catch(ModelNotFoundException $e){
            DB::rollBack();
            return $this->jsonResponseService->errorResponse(
                'Federation non trouvée',
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la modification de la federation', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la modification de la federation',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}


