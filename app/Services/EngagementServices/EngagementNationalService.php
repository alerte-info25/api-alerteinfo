<?php

namespace App\Services\EngagementServices;

use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Epreuve\EpreuveModel;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Engagements\EngagementNationalModel;
use App\Models\Competition\CategorieCompetitionModel;
use App\Models\Engagements\EngagementNationalDataModel;
use App\Services\JsonResponseServices\JsonResponseService;

class EngagementNationalService
{

    public function __construct(
        private readonly EngagementNationalModel $engagementNationalModel,
        private readonly EngagementNationalDataModel $engagementNationalDataModel,
        private readonly JsonResponseService $jsonResponse,
        private readonly CustomLogError $logError,
        private readonly EpreuveModel $epreuveModel,
        private readonly CategorieCompetitionModel $categorieCompetitionModel,
    ){}


    public function srv_getEngagementNationalList(Request $request)
    {
        try {
            // Pagination parameters
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            // Récupération des données avec les relations nécessaires
            $engagementNationalList = $this->engagementNationalDataModel
                ->withTrashed()
                ->with([
                    'athlete.club',
                    'athlete.documents',
                    'ligue',
                    'epreuve',
                    'engagement.competition',
                    'engagement.cat_competition',
                ])
                ->paginate($perPage, ['*'], 'page', $page);

            // Formatage propre des données
            $engagementNationalListFormatted = $engagementNationalList->getCollection()->map(function ($engagementNational) {
                // Récupère la première photo du sportif (si disponible)
                $firstPhoto = collect($engagementNational->athlete->documents ?? [])
                    ->first(function ($document) {
                        return isset($document->type, $document->document_name)
                            && stripos($document->type, 'image') !== false
                            && stripos($document->document_name, 'PHOTO') !== false;
                    });

                return [
                    'uuid' => $engagementNational->uuid,
                    'engagement_code_unique' => $engagementNational->engagement_code_unique,
                    'competition_name' => $engagementNational->engagement->competition->competition_name ?? null,
                    'cat_competition' => $engagementNational->engagement->cat_competition->categorie_name ?? null,
                    'created_at' => $engagementNational->created_at,
                    'first_name' => $engagementNational->athlete->first_name ?? null,
                    'last_name' => $engagementNational->athlete->last_name ?? null,
                    'full_name' => trim(($engagementNational->athlete->first_name ?? '') . ' ' . ($engagementNational->athlete->last_name ?? '')) ?: null,
                    'club_name' => $engagementNational->athlete->club->club_name ?? null,
                    'ligue_name' => $engagementNational->ligue->ligue_name ?? null,
                    'date_naissance' => $engagementNational->athlete->date_naissance ?? null,
                    'gender' => $engagementNational->athlete->genre ?? null,
                    'epreuve_name' => $engagementNational->epreuve->epreuve_name ?? null,
                    'dossars' => $engagementNational->dossars ?? null,
                    'performance' => $engagementNational->performance ?? null,
                    'rang' => $engagementNational->rang ?? null,
                    'points' => $engagementNational->points ?? null,
                    'document_path_url' => $firstPhoto->document_path_url ?? null, // 🖼️ une seule photo
                ];
            });

            // Réponse JSON standardisée
            return $this->jsonResponse->successResponseWithData(
                'Liste des engagements nationaux récupérés avec succès',
                [
                    'nationalList' => $engagementNationalListFormatted,
                    'paginations' => [
                        'total' => $engagementNationalList->total(),
                        'per_page' => $engagementNationalList->perPage(),
                        'current_page' => $engagementNationalList->currentPage(),
                        'last_page' => $engagementNationalList->lastPage(),
                        'from' => $engagementNationalList->firstItem(),
                        'to' => $engagementNationalList->lastItem(),
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


    public function srv_getEngagementNationalDetails(string $uuid): JsonResponse
    {
        try {
            // get engagement national details
            $engagementNationalDetails = $this->engagementNationalModel->withTrashed()
            ->with([
                        'competition',
                        'cat_competition',
                        'engagementData.athlete',
                        'engagementData.athlete.club',
                        'engagementData.epreuve',
                        'engagementData.ligue',
                    ])
                ->where('uuid', $uuid)
                ->first();

                $dataFormated = [
                    'uuid' => $engagementNationalDetails->uuid,
                    'engagement_code_unique' => $engagementNationalDetails->engagement_code_unique,
                    'competition_name' => $engagementNationalDetails->competition->competition_name ?? null,
                    'cat_competition' => $engagementNationalDetails->cat_competition->categorie_name ?? null,
                    'engagementData' => $engagementNationalDetails->engagementData->map(function ($engagementData) {
                        return [
                            'uuid' => $engagementData->uuid,
                            'full_name' => $engagementData->athlete->first_name . " " . $engagementData->athlete->last_name ?? null,
                            'club_name' => $engagementData->athlete->club->club_name ?? null,
                            'date_naissance' => $engagementData->athlete->date_naissance ?? null,
                            'gender' => $engagementData->athlete->genre ?? null,
                            'epreuve_name' => $engagementData->epreuve->epreuve_name ?? null,
                            'ligue_name' => $engagementData->ligue->ligue_name ?? null,
                            'dossars' => $engagementData->dossars ?? null,
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



    public function srv_getEngagementNationalListFilter(Request $request): JsonResponse
    {
        try {
            // Pagination parameters
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            // Filtres dynamiques
            $gender = $request->input('gender');
            $catCompetitionCode = $request->input('cat_competition_code_unique');
            $epreuveCode = $request->input('epreuve_code_unique');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Requête principale avec relations
            $query = $this->engagementNationalDataModel
                ->withTrashed()
                ->with([
                    'athlete.club',
                    'athlete.documents',
                    'ligue',
                    'epreuve',
                    'engagement.competition',
                    'engagement.cat_competition',
                ]);

            // ✅ Application des filtres dynamiques
            $query->when($gender, function ($q) use ($gender) {
                $q->whereHas('athlete', function ($sub) use ($gender) {
                    $sub->where('genre', $gender);
                });
            });

            $query->when($catCompetitionCode, function ($q) use ($catCompetitionCode) {
                $q->whereHas('engagement.cat_competition', function ($sub) use ($catCompetitionCode) {
                    $sub->where('categorie_code_unique', $catCompetitionCode);
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
            $engagementNationalList = $query->paginate($perPage, ['*'], 'page', $page);

            // Formatage
            $engagementNationalListFormatted = $engagementNationalList->getCollection()->map(function ($engagementNational) {
                // Première photo du sportif
                $firstPhoto = collect($engagementNational->athlete->documents ?? [])
                    ->first(function ($document) {
                        return isset($document->type, $document->document_name)
                            && stripos($document->type, 'image') !== false;
                    });

                return [
                    'uuid' => $engagementNational->uuid,
                    'engagement_code_unique' => $engagementNational->engagement_code_unique,
                    'competition_name' => $engagementNational->engagement->competition->competition_name ?? null,
                    'cat_competition' => $engagementNational->engagement->cat_competition->categorie_name ?? null,
                    'created_at' => $engagementNational->created_at,
                    'first_name' => $engagementNational->athlete->first_name ?? null,
                    'last_name' => $engagementNational->athlete->last_name ?? null,
                    'full_name' => trim(($engagementNational->athlete->first_name ?? '') . ' ' . ($engagementNational->athlete->last_name ?? '')) ?: null,
                    'club_name' => $engagementNational->athlete->club->club_name ?? null,
                    'ligue_name' => $engagementNational->ligue->ligue_name ?? null,
                    'date_naissance' => $engagementNational->athlete->date_naissance ?? null,
                    'gender' => $engagementNational->athlete->genre ?? null,
                    'epreuve_name' => $engagementNational->epreuve->epreuve_name ?? null,
                    'dossars' => $engagementNational->dossars ?? null,
                    'performance' => $engagementNational->performance ?? null,
                    'rang' => $engagementNational->rang ?? null,
                    'points' => $engagementNational->points ?? null,
                    'document_path_url' => $firstPhoto->document_path_url ?? null,
                ];
            });

            // Réponse standardisée
            return $this->jsonResponse->successResponseWithData(
                'Liste des engagements nationaux filtrés avec succès',
                [
                    'nationalList' => $engagementNationalListFormatted,
                    'paginations' => [
                        'total' => $engagementNationalList->total(),
                        'per_page' => $engagementNationalList->perPage(),
                        'current_page' => $engagementNationalList->currentPage(),
                        'last_page' => $engagementNationalList->lastPage(),
                        'from' => $engagementNationalList->firstItem(),
                        'to' => $engagementNationalList->lastItem(),
                    ]
                ],
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->logError->logError('Erreur lors du filtrage des engagements nationaux', $th);
            return $this->jsonResponse->errorResponse(
                'Erreur lors du filtrage des engagements nationaux',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


}

