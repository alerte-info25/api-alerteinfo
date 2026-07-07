<?php

namespace App\Services\LigueServices;

use App\Services\UserLogServices\UserLogService;
use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\LigueModels\LigueModels;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;
use App\Models\CodeValidity\OrganisationCodeValidityModel;

class LigueService
{
    /**
     * @var LigueModels
     */
    private $ligueModels;

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
        LigueModels $ligueModels,
        JsonResponseService $jsonResponseService,
        CustomLogError $customLogError,
        CodeGeneratorService $codeGeneratorService,
        UploadFileManagerService $uploadFileManagerService,
        UserLogService $userLogService,
        OrganisationCodeValidityModel $organisationCodeValidityModel
    ) {
        $this->ligueModels = $ligueModels;
        $this->jsonResponseService = $jsonResponseService;
        $this->customLogError = $customLogError;
        $this->codeGeneratorService = $codeGeneratorService;
        $this->uploadFileManagerService = $uploadFileManagerService;
        $this->userLogService = $userLogService;
        $this->organisationCodeValidityModel = $organisationCodeValidityModel;
    }

    public function srv_getLigueList(Request $request): JsonResponse
    {
        try {

            // pagination parameters
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 10);

            // get ligue list
            $ligueList = $this->ligueModels->paginate($limit, ['*'], 'page', $page);
            $ligueListFormatted = $ligueList->getCollection()->map(function ($ligue) {
                return [
                    'ligue_code_unique' => $ligue->ligue_code_unique,
                    'ligue_name' => $ligue->ligue_name,
                    'ligue_abreviation' => $ligue->ligue_abreviation,
                    'ligue_code' => $ligue->ligue_code,
                    'code_validity' => $ligue->code_validity,
                    'slug' => $ligue->slug
                ];
            });

            return $this->jsonResponseService->successResponseWithData(
                'Liste des ligues récupérée avec succès',
                [
                    'ligueList' => $ligueListFormatted,
                    'paginations' => [
                        'total' => $ligueList->total(),
                        'per_page' => $ligueList->perPage(),
                        'current_page' => $ligueList->currentPage(),
                        'last_page' => $ligueList->lastPage(),
                        'from' => $ligueList->firstItem(),
                        'to' => $ligueList->lastItem()
                    ]
                ],
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->customLogError->logError('Erreur lors de la récupération de la liste des ligues', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération de la liste des ligues',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createLigue(Request $request): JsonResponse{
        try {

            $ligueCodeUnique = $this->codeGeneratorService->generateDefaultCodeUnique(
                'ligue_models',
                'ligue_code_unique',
                'LG'
            );

            $ligueCreated = $this->ligueModels->create([
                'ligue_code_unique' => $ligueCodeUnique,
                'ligue_name' => $request->ligue_name,
                'ligue_abreviation' => $request->ligue_abreviation,
                'ligue_code' => strtoupper(Str::random(6)),
                'code_validity' => now()->addDays(7)->format('Y-m-d H:i:s'),
                'slug' => Str::uuid()
            ]);

            DB::commit();


            // user log
            $this->userLogService->srv_createUserLog(
                'create',
                sprintf(
                    "Ligue %s créé avec succès par %s à %s",
                    $ligueCreated->ligue_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'Ligue créé avec succès',
                $ligueCreated,
                Response::HTTP_CREATED
            );

        } catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la création de la ligue', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la création de la ligue',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateLigue(Request $request, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {

            $ligue = $this->ligueModels->where('slug', $slug)->firstOrFail();

            $ligue->update([
                'ligue_name' => $request->ligue_name,
                'ligue_abreviation' => $request->ligue_abreviation,
            ]);

            DB::commit();


            // user log
            $this->userLogService->srv_createUserLog(
                'update',
                sprintf(
                    "Ligue %s modifié avec succès par %s à %s",
                    $ligue->ligue_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'Ligue modifié avec succès',
                $ligue,
                Response::HTTP_OK
            );

        } catch(ModelNotFoundException $e){
            DB::rollBack();
            return $this->jsonResponseService->errorResponse(
                'Ligue non trouvée',
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            // delete file
            $this->customLogError->logError('Erreur lors de la modification de la ligue', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la modification de la ligue',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteLigue($slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $ligue = $this->ligueModels->where('slug', $slug)->firstOrFail();
            $ligue->delete();
            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'delete',
                sprintf(
                    "Ligue %s supprimé avec succès par %s à %s",
                    $ligue->ligue_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );
            return $this->jsonResponseService->successResponse(
                'Ligue supprimée avec succès',
                Response::HTTP_OK
            );
        } catch(ModelNotFoundException $e){
            DB::rollBack();
            return $this->jsonResponseService->errorResponse(
                'Ligue non trouvée',
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la suppression de la ligue', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la suppression de la ligue',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_refreshLigueCode($slug): JsonResponse
    {
        DB::beginTransaction();
        try {

            $validity = $this->organisationCodeValidityModel->firstOrFail();

            // add validity to a now() + validity
            $validityDate = now()->addDays($validity->validity)->format('Y-m-d H:i:s');

            $ligue = $this->ligueModels->where('slug', $slug)->firstOrFail();

            $ligue->update([
                'code_validity' => $validityDate,
            ]);

            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'update',
                sprintf(
                    "La date de validité du code de la ligue %s a été modifiée avec succès par %s à %s",
                    $ligue->ligue_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'La date de validité du code de la ligue modifiée avec succès',
                $ligue,
                Response::HTTP_OK
            );

        } catch(ModelNotFoundException $e){
            DB::rollBack();
            return $this->jsonResponseService->errorResponse(
                'Ligue non trouvée',
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la modification de la ligue', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la modification de la ligue',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
