<?php
namespace App\Services\FonctionServices;

use App\Models\CategorieGeneraleModels\CategorieGeneraleModel;
use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\FonctionModels\FonctionModel;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;
use App\Services\UserLogServices\UserLogService;


class FonctionService
{
    
    public function __construct(
        private readonly FonctionModel $fonctionModel,
        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogError $customLogError,
        private readonly CodeGeneratorService $codeGeneratorService,
        private readonly UserLogService $userLogService,
        private readonly CategorieGeneraleModel $categorieGeneraleModel
    ) {
    }


    public function srv_getFonctionList(Request $request): JsonResponse
    {
        try {
            // pagination parameters
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);

            // get fonction list
            $fonctionList = $this->fonctionModel
            ->with('category')
            ->paginate($limit, ['*'], 'page', $page);
            $fonctionListFormatted = $fonctionList->getCollection()->map(function ($fonction) {
                return [
                    'fonction_code_unique' => $fonction->fonction_code_unique,
                    'category_code_unique' => $fonction->category_code_unique,
                    'category_name' => $fonction->category->categorie_name,
                    'fonction_name' => $fonction->fonction_name,
                    'slug' => $fonction->slug,
                    'created_at' => $fonction->created_at,
                ];
            });

            return $this->jsonResponseService->successResponseWithData(
                'Liste des fonctions récupérée avec succès',
                [
                    'fonctionList' => $fonctionListFormatted,
                    'paginations' => [
                        'total' => $fonctionList->total(),
                        'per_page' => $fonctionList->perPage(),
                        'current_page' => $fonctionList->currentPage(),
                        'last_page' => $fonctionList->lastPage(),
                        'from' => $fonctionList->firstItem(),
                        'to' => $fonctionList->lastItem()
                    ]
                ],
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->customLogError->logError('Erreur lors de la récupération de la liste des fonctions', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération de la liste des fonctions',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_getFonctionFormData()
    {
        try {
            $categorieGeneraleList = $this->categorieGeneraleModel->get();

            return $this->jsonResponseService->successResponseWithData(
                'Donnees de formulaire de la fonction récuperees avec succes',
                $categorieGeneraleList,
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->customLogError->logError('Erreur lors de la récupération des données de formulaire de la fonction', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération des données de formulaire de la fonction',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // create fonction
    public function srv_createFonction(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // get fonction data

            $fonctionCodeUnique = $this->codeGeneratorService->generateDefaultCodeUnique(
                'fonction_models',
                'fonction_code_unique',
                'FUNC'
            );

            // create fonction
            $fonction = $this->fonctionModel->create([
                'fonction_code_unique' => $fonctionCodeUnique,
                'category_code_unique' => $request->category_code_unique,
                'fonction_name' => $request->fonction_name,
                'slug' => Str::uuid()
            ]);


            DB::commit();
            // log fonction creation
            $this->userLogService->srv_createUserLog(
                'create',
                sprintf(
                    "Fonction %s créé avec succès par %s à %s",
                    $fonction->fonction_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'Fonction créée avec succès',
                $fonction,
                Response::HTTP_CREATED
            );

        } catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la création de la fonction', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la création de la fonction',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateFonction(Request $request, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $fonction = $this->fonctionModel->where('slug', $slug)->firstOrFail();

            $fonction->update([
                'category_code_unique' => $request->category_code_unique,
                'fonction_name' => $request->fonction_name,
            ]);

            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'update',
                sprintf(
                    "Fonction %s modifié avec succès par %s à %s",
                    $fonction->fonction_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'Fonction modifiée avec succès',
                $fonction,
                Response::HTTP_OK
            );

        } catch(ModelNotFoundException $e){
            DB::rollBack();
            return $this->jsonResponseService->errorResponse(
                'Fonction non trouvée',
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la modification de la fonction', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la modification de la fonction',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteFonction($slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $fonction = $this->fonctionModel->where('slug', $slug)->firstOrFail();

            $fonction->delete();

            DB::commit();

            // user log
            $this->userLogService->srv_createUserLog(
                'delete',
                sprintf(
                    "Fonction %s supprimé avec succès par %s à %s",
                    $fonction->fonction_name,
                    auth('admin')->user()->first_name . ' ' . auth('admin')->user()->last_name,
                    now()->format('Y-m-d H:i:s')
                )
            );

            return $this->jsonResponseService->successResponseWithData(
                'Fonction supprimée avec succès',
                $fonction,
                Response::HTTP_OK
            );

        } catch(ModelNotFoundException $e){
            DB::rollBack();
            return $this->jsonResponseService->errorResponse(
                'Fonction non trouvée',
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError('Erreur lors de la suppression de la fonction', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la suppression de la fonction',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
