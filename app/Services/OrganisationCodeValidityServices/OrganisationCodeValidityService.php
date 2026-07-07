<?php

namespace App\Services\OrganisationCodeValidityServices;

use Exception;
use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\CodeValidity\OrganisationCodeValidityModel;
use App\Services\JsonResponseServices\JsonResponseService;


class OrganisationCodeValidityService
{
    /**
     * @var JsonResponseService
     */
    private $jsonResponseService;

    /**
     * @var OrganisationCodeValidityModel
     */
    private $organisationCodeValidityModel;

    /**
     * @var CustomLogError
     */
    private $customLogError;
    public function __construct(
        JsonResponseService $jsonResponseService,
        OrganisationCodeValidityModel $organisationCodeValidityModel,
        CustomLogError $customLogError,
    ) {
        $this->jsonResponseService = $jsonResponseService;
        $this->organisationCodeValidityModel = $organisationCodeValidityModel;
        $this->customLogError = $customLogError;
    }

    public function srv_getOrganisationCodeValidity(): JsonResponse
    {
        try {
            $organisationCodeValidity = $this->organisationCodeValidityModel::all();
            return $this->jsonResponseService->successResponseWithData(
                'Organisation Code Validity récupérés avec succès',
                $organisationCodeValidity,
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

    public function srv_createOrganisationCodeValidity(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $organisationCodeValidity = $this->organisationCodeValidityModel::create([
                'validity' => $request->validity,
                'slug' => Str::uuid(),
            ]);
            DB::commit();
            return $this->jsonResponseService->successResponseWithData(
                'Organisation Code Validity créé avec succès',
                $organisationCodeValidity,
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur role
            $this->customLogError->logError("Erreur lors de la création d'un organisation code validity : ", $th);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la création d'un organisation code validity.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    public function srv_updateOrganisationCodeValidity(Request $request, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $organisationCodeValidity = $this->organisationCodeValidityModel::where('slug', $slug)->firstOrFail();
            $organisationCodeValidity->update([
                'validity' => $request->validity,
            ]);
            DB::commit();
            return $this->jsonResponseService->successResponseWithData(
                'Validité des codes mis à jour avec succès',
                $organisationCodeValidity,
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $q) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur role
            $this->customLogError->logError("Erreur: organisation code validity non trouvé : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur: organisation code validity non trouvé.", // Message générique
                Response::HTTP_NOT_FOUND, // Code HTTP d'erreur interne
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur role
            $this->customLogError->logError("Erreur lors de la mise à jour d'un organisation code validity : ", $th);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la mise à jour d'un organisation code validity.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    public function srv_destroyOrganisationCodeValidity($slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $organisationCodeValidity = $this->organisationCodeValidityModel::where('slug', $slug)->firstOrFail();
            $organisationCodeValidity->delete();
            DB::commit();
            return $this->jsonResponseService->successResponse(
                'Validité des codes supprimé avec succès',
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $q) {
                // Journalisation sécurisée de l'erreur role
            $this->customLogError->logError("Erreur: organisation code validity non trouvé : ", $q);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur: organisation code validity non trouvé.", // Message générique
                Response::HTTP_NOT_FOUND, // Code HTTP d'erreur interne
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            // Journalisation sécurisée de l'erreur role
            $this->customLogError->logError("Erreur lors de la suppression d'un organisation code validity : ", $th);
            // Lancer une exception personnalisée pour masquer les détails sensibles
            return $this->jsonResponseService->errorResponse(
                "Erreur lors de la suppression d'un organisation code validity.", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }
}
