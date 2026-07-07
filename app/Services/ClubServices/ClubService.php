<?php
namespace App\Services\ClubServices;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\ClubModels\ClubModels;
use Symfony\Component\HttpFoundation\Response;
use App\Services\UserLogServices\UserLogService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\CodeValidity\OrganisationCodeValidityModel;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;

class ClubService
{
    /**
     * @var ClubModels
     */
    private $clubModels;

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
        ClubModels $clubModels,
        JsonResponseService $jsonResponseService,
        CustomLogError $customLogError,
        CodeGeneratorService $codeGeneratorService,
        UploadFileManagerService $uploadFileManagerService,
        UserLogService $userLogService,
        OrganisationCodeValidityModel $organisationCodeValidityModel
    ) {
        $this->clubModels = $clubModels;
        $this->jsonResponseService = $jsonResponseService;
        $this->customLogError = $customLogError;
        $this->codeGeneratorService = $codeGeneratorService;
        $this->uploadFileManagerService = $uploadFileManagerService;
        $this->userLogService = $userLogService;
        $this->organisationCodeValidityModel = $organisationCodeValidityModel;
    }

    public function srv_getClubList(Request $request): JsonResponse{
        try {

            // pagination parameters
            $page = $request->input('page', 1);
            $perPage = $request->input('limit', 20);

            // get club list
            $clubList = $this->clubModels->paginate($perPage, ['*'], 'page', $page);

            $clubListFormatted = $clubList->getCollection()->map(function ($club) {
                return [
                    'club_code_unique' => $club->club_code_unique,
                    'club_name' => $club->club_name,
                    'club_abreviation' => $club->club_abreviation,
                    'club_code' => $club->club_code,
                    'code_validity' => $club->code_validity,
                    'club_logo' => $club->club_logo_url,
                    'slug' => $club->slug,
                    'created_at' => $club->created_at,
                ];
            });

            return $this->jsonResponseService->successResponseWithData(
                'Liste des clubs récupérée avec succès',
                [
                    'clubList' => $clubListFormatted,
                    'paginations' => [
                        'total' => $clubList->total(),
                        'per_page' => $clubList->perPage(),
                        'current_page' => $clubList->currentPage(),
                        'last_page' => $clubList->lastPage(),
                        'from' => $clubList->firstItem(),
                        'to' => $clubList->lastItem()
                    ]
                ],
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->customLogError->logError('Erreur lors de la récupération de la liste des clubs', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération de la liste des clubs',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createClub(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {


            $clubCodeUnique = $this->codeGeneratorService->generateDefaultCodeUnique(
                'club_models',
                'club_code_unique',
                'CLB'
            );

            $clubCreated = $this->clubModels->create([
                'club_code_unique' => $clubCodeUnique,
                'club_name' => $request->club_name,
                'club_abreviation' => $request->club_abreviation,
                'club_code' => strtoupper(Str::random(6)),
                'code_validity' => now()->addDays(7)->format('Y-m-d H:i:s'),
                'slug' => Str::uuid()
            ]);

            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'create',
                sprintf(
                    "Club %s créé avec succès par %s à %s",
                    $clubCreated->club_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'Club créé avec succès',
                $clubCreated,
                Response::HTTP_CREATED
            );

        } catch (\Throwable $th) {
            DB::rollBack();
            // delete file
            $this->customLogError->logError('Erreur lors de la création du club', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la création du club',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateClub(Request $request, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {

            $club = $this->clubModels->where('slug', $slug)->firstOrFail();

            $club->update([
                'club_name' => $request->club_name,
                'club_abreviation' => $request->club_abreviation,
            ]);

            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'update',
                sprintf(
                    "Club %s modifié avec succès par %s à %s",
                    $club->club_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'Club modifié avec succès',
                $club,
                Response::HTTP_OK
            );

        } catch(ModelNotFoundException $e){
            DB::rollBack();
            return $this->jsonResponseService->errorResponse(
                'Club non trouvée',
                Response::HTTP_NOT_FOUND
            );
        }

        catch (\Throwable $th) {
            DB::rollBack();
            // delete file
            $this->customLogError->logError('Erreur lors de la modification du club', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la modification du club',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteClub($slug): JsonResponse
    {
        DB::beginTransaction();
        try {

            $club = $this->clubModels->where('slug', $slug)->firstOrFail();

            // old logo
            $oldLogo = $club->club_logo;

            $club->delete();

            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'delete',
                sprintf(
                    "Club %s supprimé avec succès par %s à %s",
                    $club->club_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'Club supprimé avec succès',
                $club,
                Response::HTTP_OK
            );

        } catch(ModelNotFoundException $e){
            DB::rollBack();
            return $this->jsonResponseService->errorResponse(
                'Club non trouvée',
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            // delete file
            $this->uploadFileManagerService->deleteFile($oldLogo);
            $this->customLogError->logError('Erreur lors de la suppression du club', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la suppression du club',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_refreshClubCode($slug): JsonResponse
    {
        DB::beginTransaction();
        try {

            $validity = $this->organisationCodeValidityModel->firstOrFail();

            // add validity to a now() + validity
            $validityDate = now()->addDays($validity->validity)->format('Y-m-d H:i:s');

            $club = $this->clubModels->where('slug', $slug)->firstOrFail();

            $club->update([
                'code_validity' => $validityDate,
            ]);

            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'update',
                sprintf(
                    "La date de validité du code du club %s a été modifiée avec succès par %s à %s",
                    $club->club_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'La date de validité du code du club modifiée avec succès',
                $club,
                Response::HTTP_OK
            );

        } catch(ModelNotFoundException $e){
            DB::rollBack();
            return $this->jsonResponseService->errorResponse(
                'Club non trouvée',
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la modification du club', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la modification du club',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

}

