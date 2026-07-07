<?php

namespace App\Services\Analystics;

use App\Logs\CustomLogError;
use Illuminate\Http\JsonResponse;
use App\Models\Soldes\SoldeModels;
use App\Models\Soldes\TransactionsModels;
use App\Models\LicenceModels\LicenceModel;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JsonResponseServices\JsonResponseService;




class AnalysticService
{

    public function __construct(
        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogError $customLogError,
        private readonly LicenceModel $licenceModel,
        private readonly TransactionsModels $transactionsModels,
        private readonly SoldeModels $soldeModels,
    ) {
    }

    public function srv_getDefaultAnalysticsData(): JsonResponse
    {
        try {



            $data = [
                'top_section' => $this->getTopSectionData(),
                'operationData'         => $this->getOperationData(),
            ];

            return $this->jsonResponseService->successResponseWithData(
                "Données récupérer avec succès",
                $data,
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Erreur la de recuperation des données",
                $th
            );
            return $this->jsonResponseService->errorResponse(
                "Erreur la de recuperation des données",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function getTopSectionData(): array
    {
        // Récupère les données nécessaires en une seule requête
        $licences = $this->licenceModel
            ->select('genre', 'operation_type','payment')
            ->whereIn('genre', ['M', 'F']) // Seulement les genres valides
            ->get();

        // Helper pour compter par genre
        $countByGender = function ($collection) {
            return [
                'total' => $collection->count(),
                'hommeTotal' => $collection->where('genre', 'M')->count(),
                'femmeTotal' => $collection->where('genre', 'F')->count(),
            ];
        };

        $countByPayment = function ($collection) {
            return [
                'total' => $collection->count(),
                'payee' => $collection->where('payment', 'payed')->count(),
                'non_payee' => $collection->where('payment', 'cancelled')->count(),
            ];
        };

        // 1. Toutes les licences
        $totalLicence = $countByGender($licences);

        // 2. Nouvelles licences
        $nouvelles = $licences->where('operation_type', 'new');
        $totalNouvelleLicence = $countByGender($nouvelles);

        // 3. Renouvellements
        $renouvellements = $licences->where('operation_type', 'renewal');
        $totalRenouvellementLicence = $countByGender($renouvellements);

        // 4 paiement
        $paiement = $countByPayment($licences);
        
        

        return [
            'totalLicence' => $totalLicence,
            'totalNouvelleLicence' => $totalNouvelleLicence,
            'totalRenouvellementLicence' => $totalRenouvellementLicence,
            'totalSoldLicence' => $paiement,
        ];
    }

    private function getOperationData()
    {
        $transactions = $this->transactionsModels->get();
        $solde = $this->soldeModels
        ->select('amount_transferred','montants','montants_net','slug')
        ->first();

        return [
            'transactions' => $transactions,
            'montants' => $solde->montants ?? 0,
            'montants_net' => $solde->montants_net ?? 0,
        ];
    }



}
