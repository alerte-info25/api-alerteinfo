<?php

namespace App\Services\EngagementServices;

use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Epreuve\EpreuveModel;
use App\Models\Competition\CompetitionModel;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Competition\CategorieCompetitionModel;
use App\Models\Engagements\EngagementInternationalModel;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Models\Engagements\EngagementInternationalDataModel;

class EngagementInternationalService
{

    public function __construct(
        private readonly EngagementInternationalModel $engagementInternationalModel,
        private readonly EngagementInternationalDataModel $engagementInternationalDataModel,
        private readonly JsonResponseService $jsonResponse,
        private readonly CustomLogError $logError,
        private readonly EpreuveModel $epreuveModel,
        private readonly CategorieCompetitionModel $categorieCompetitionModel,
    ){}


    public function srv_getEngagementInternationalList(Request $request)
    {
        try {
            // Pagination parameters
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            // Récupération des données avec les relations nécessaires
            $engagementInternationalList = $this->engagementInternationalDataModel
                ->withTrashed()
                ->with([
                    'epreuve',
                    'engagement.competition',
                    'engagement.cat_competition',
                ])
                ->paginate($perPage, ['*'], 'page', $page);

            // Formatage propre des données
            $engagementInternationalListFormatted = $engagementInternationalList->getCollection()->map(function ($engagementInternational) {

                return [
                    'uuid' => $engagementInternational->uuid,
                    'engagement_code_unique' => $engagementInternational->engagement_code_unique,
                    'competition_name' => $engagementInternational->engagement->competition->competition_name ?? null,
                    'cat_competition' => $engagementInternational->engagement->cat_competition->categorie_name ?? null,
                    'created_at' => $engagementInternational->created_at,
                    'full_name' => trim(($engagementInternational->full_name ?? '') . ' ' . ($engagementInternational->full_name ?? '')) ?: null,
                    'date_naissance' => $engagementInternational->date_naissance ?? null,
                    'gender' => $engagementInternational->genre ?? null,
                    'epreuve_name' => $engagementInternational->epreuve->epreuve_name ?? null,
                    'performance' => $engagementInternational->performance ?? null,
                    'rang' => $engagementInternational->rang ?? null,
                    'points' => $engagementInternational->points ?? null,
                ];
            });



            // Réponse JSON standardisée
            return $this->jsonResponse->successResponseWithData(
                'Liste des engagements internationaux récupérés avec succès',
                [
                    'internationalList' => $engagementInternationalListFormatted,
                    'paginations' => [
                        'total' => $engagementInternationalList->total(),
                        'per_page' => $engagementInternationalList->perPage(),
                        'current_page' => $engagementInternationalList->currentPage(),
                        'last_page' => $engagementInternationalList->lastPage(),
                        'from' => $engagementInternationalList->firstItem(),
                        'to' => $engagementInternationalList->lastItem(),
                    ]
                ],
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la récupération de la liste des engagements nationaux', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la récupération de la liste des engagements nationaux',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_getTableData() : JsonResponse
    {
        try {
            // get table data
            $listEpreuve = $this->epreuveModel->all();
            $listCategorie = $this->categorieCompetitionModel->all();


            return $this->jsonResponse->successResponseWithData(
                'Données de la table récupérées avec succès',
                [
                    'listEpreuve' => $listEpreuve,
                    'listCategorie' => $listCategorie,
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la récupération des données de la table', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la récupération des données de la table',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    public function srv_getEngagementInternationalDetails(string $uuid): JsonResponse
    {
        try {
            // get engagement international details
            $engagementInternationalDetails = $this->engagementInternationalDataModel->withTrashed()
            ->with([
                        'engagement.competition',
                        'engagement.cat_competition',
                        'epreuve',
                    ])
                ->where('uuid', $uuid)
                ->first();

                $dataFormated = [
                    'uuid' => $engagementInternationalDetails->uuid,
                    'engagement_code_unique' => $engagementInternationalDetails->engagement_code_unique,
                    'competition_name' => $engagementInternationalDetails->engagement->competition->competition_name ?? null,
                    'cat_competition' => $engagementInternationalDetails->engagement->cat_competition->categorie_name ?? null,
                    'engagementData' => $engagementInternationalDetails->engagementData->map(function ($engagementData) {
                        return [
                            'uuid' => $engagementData->uuid,
                            'full_name' => $engagementData->full_name ?? null,
                            'date_naissance' => $engagementData->date_naissance ?? null,
                            'pays' => $engagementData->pays ?? null,
                            'gender' => $engagementData->genre ?? null,
                            'epreuve_name' => $engagementData->epreuve->epreuve_name ?? null,
                            'performance' => $engagementData->performance ?? null,
                            'rang' => $engagementData->rang ?? null,
                            'points' => $engagementData->points ?? null,
                        ];
                    }),
                ];

            return $this->jsonResponse->successResponseWithData(
                'Détails de l\'engagement national récupérés avec succès',
                $dataFormated,
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors de la récupération des détails de l\'engagement national', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors de la récupération des détails de l\'engagement national',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    // filter engagement international
    public function srv_getEngagementInternationalListFilter(Request $request): JsonResponse
    {
        try {
            // Pagination parameters
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            // Récupération des filtres
            $gender = $request->input('gender');
            $catCompetitionCode = $request->input('cat_competition_code_unique');
            $epreuveCode = $request->input('epreuve_code_unique');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Requête de base
            $query = $this->engagementInternationalDataModel->withTrashed()
                ->with([
                    'engagement.competition',
                    'engagement.cat_competition',
                    'epreuve',
                ]);

            // ✅ Filtres dynamiques
            $query->when($gender, function ($q) use ($gender) {
                $q->where('genre', $gender);
            });

            $query->when($catCompetitionCode, function ($q) use ($catCompetitionCode) {
                $q->whereHas('engagement.cat_competition', function ($sub) use ($catCompetitionCode) {
                    $sub->where('cat_competition_code_unique', $catCompetitionCode);
                });
            });

            $query->when($epreuveCode, function ($q) use ($epreuveCode) {
                $q->whereHas('epreuve', function ($sub) use ($epreuveCode) {
                    $sub->where('epreuve_code_unique', $epreuveCode);
                });
            });

            $query->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            });

            // Pagination
            $engagementInternationalList = $query->paginate($perPage, ['*'], 'page', $page);

            // Formatage
            $engagementInternationalListFormatted = $engagementInternationalList->map(function ($item) {
                return [
                    'uuid' => $item->uuid,
                    'engagement_code_unique' => $item->engagement_code_unique,
                    'competition_name' => $item->engagement->competition->competition_name ?? null,
                    'cat_competition' => $item->engagement->cat_competition->categorie_name ?? null,
                    'created_at' => $item->created_at,
                    'full_name' => trim(($item->full_name ?? '') . ' ' . ($item->full_name ?? '')) ?: null,
                    'date_naissance' => $item->date_naissance ?? null,
                    'gender' => $item->genre ?? null,
                    'epreuve_name' => $item->epreuve->epreuve_name ?? null,
                    'performance' => $item->performance ?? null,
                    'rang' => $item->rang ?? null,
                    'points' => $item->points ?? null,
                ];
            });

            // Réponse
            return $this->jsonResponse->successResponseWithData(
                'Liste des engagements internationaux filtrés avec succès',
                [
                    'internationalList' => $engagementInternationalListFormatted,
                    'paginations' => [
                        'total' => $engagementInternationalList->total(),
                        'per_page' => $engagementInternationalList->perPage(),
                        'current_page' => $engagementInternationalList->currentPage(),
                        'last_page' => $engagementInternationalList->lastPage(),
                        'from' => $engagementInternationalList->firstItem(),
                        'to' => $engagementInternationalList->lastItem(),
                    ]
                ],
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors du filtrage des engagements internationaux', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors du filtrage des engagements internationaux',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


}

