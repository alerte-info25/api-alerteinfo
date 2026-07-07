<?php

namespace App\Services\TrancheAgeServices;

use Exception;
use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\TrancheAges\TrancheAgeModel;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;


class TrancheAgeService
{
    /**
     * @var JsonResponseService
     */
    private $jsonResponseService;

    /**
     * @var TrancheAgeModel
     */
    private $trancheAgeModel;

    /**
     * @var CustomLogError
     */
    private $customLogError;

    /**
     * @var CodeGeneratorService
     */
    private $codeGeneratorService;
    public function __construct(
        JsonResponseService $jsonResponseService,
        TrancheAgeModel $trancheAgeModel,
        CustomLogError $customLogError,
        CodeGeneratorService $codeGeneratorService,
    ) {
        $this->jsonResponseService = $jsonResponseService;
        $this->trancheAgeModel = $trancheAgeModel;
        $this->customLogError = $customLogError;
        $this->codeGeneratorService = $codeGeneratorService;
    }

    public function srv_getTrancheAge(Request $request): JsonResponse
    {
        try {
             // pagination parameters
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);

            // get tranche age list
            $trancheAge = $this->trancheAgeModel->paginate($limit, ['*'], 'page', $page);

            $trancheAgeFormatted = $trancheAge->getCollection()->map(function ($trancheAge) {
                return [
                    'tranche_code_unique' => $trancheAge->tranche_code_unique,
                    'tranche_age' => $trancheAge->tranche_age,
                    'tranche_name' => $trancheAge->tranche_name,
                    'tranche_min' => $trancheAge->tranche_min,
                    'tranche_max' => $trancheAge->tranche_max,
                    'slug' => $trancheAge->slug,
                    'created_at' => $trancheAge->created_at,
                ];
            });
            return $this->jsonResponseService->successResponseWithData(
                'Tranche Age récupérés avec succès',
                [
                    'trancheAgeList' => $trancheAgeFormatted,
                    'paginations' => [
                        'total' => $trancheAge->total(),
                        'per_page' => $trancheAge->perPage(),
                        'current_page' => $trancheAge->currentPage(),
                        'last_page' => $trancheAge->lastPage(),
                        'from' => $trancheAge->firstItem(),
                        'to' => $trancheAge->lastItem()
                    ]
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            // Journalisation sécurisée de l'erreur role
            $this->customLogError->logError("Erreur lors de la récupération des rôles : ", $th);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la récupération des rôles.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    public function srv_createTrancheAge(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $trancheCodeUnique = $this->codeGeneratorService->generateDefaultCodeUnique(
                'tranche_age_models',
                'tranche_code_unique',
                'TRN'
            );
            $trancheAge = $this->trancheAgeModel->create([
                'tranche_code_unique' => $trancheCodeUnique,
                'tranche_age' => $request->tranche_age,
                'tranche_name' => $request->tranche_name,
                'tranche_min' => $request->tranche_min,
                'tranche_max' => $request->tranche_max,
                'slug' => Str::uuid(),
            ]);
            DB::commit();
            return $this->jsonResponseService->successResponseWithData(
                'Tranche Age créé avec succès',
                $trancheAge,
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur role
            $this->customLogError->logError("Erreur lors de la création d'un tranche age : ", $th);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la création d'un tranche age.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    public function srv_updateTrancheAge(Request $request, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $trancheAge = $this->trancheAgeModel::where('slug', $slug)->firstOrFail();
            $trancheAge->update([
                'tranche_age' => $request->tranche_age,
                'tranche_name' => $request->tranche_name,
                'tranche_min' => $request->tranche_min,
                'tranche_max' => $request->tranche_max,
            ]);
            DB::commit();
            return $this->jsonResponseService->successResponseWithData(
                'Tranche Age mis à jour avec succès',
                $trancheAge,
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $q) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur role
            $this->customLogError->logError("Erreur: tranche age non trouvé : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur: tranche age non trouvé.", // Message générique
                Response::HTTP_NOT_FOUND, // Code HTTP d'erreur interne
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur role
            $this->customLogError->logError("Erreur lors de la mise à jour d'un tranche age : ", $th);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la mise à jour d'un tranche age.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }


    }

    public function srv_destroyTrancheAge($slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $trancheAge = $this->trancheAgeModel::where('slug', $slug)->firstOrFail();
            $trancheAge->delete();
            DB::commit();
            return $this->jsonResponseService->successResponse(
                'Tranche Age supprimé avec succès',
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $q) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur role
            $this->customLogError->logError("Erreur: tranche age non trouvé : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur: tranche age non trouvé.", // Message générique
                Response::HTTP_NOT_FOUND, // Code HTTP d'erreur interne
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur role
            $this->customLogError->logError("Erreur lors de la suppression d'un tranche age : ", $th);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la suppression d'un tranche age.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }
}
