<?php
namespace App\Http\Controllers\API\V1\GeniusPay;

use App\Http\Controllers\Controller;
use App\Models\AbonnementsWebModels\AbonnementWebModels;
use App\Models\WebTransactions\WebTransactionModels;
use App\Services\CodeGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManualWebhookController extends Controller
{
    /**
     * Simuler manuellement un webhook pour un paiement
     */
    public function simulateWebhook(Request $request)
    {
        $reference = $request->get('reference');
        $abonnementCode = $request->get('abonnement_code');

        if (!$reference && !$abonnementCode) {
            return response()->json([
                'error' => 'Fournissez reference ou abonnement_code'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Trouver l'abonnement
            $abonnement = null;
            if ($abonnementCode) {
                $abonnement = AbonnementWebModels::where('abonnement_web_code', $abonnementCode)->first();
            } elseif ($reference) {
                $abonnement = AbonnementWebModels::where('payment_reference', $reference)->first();
            }

            if (!$abonnement) {
                return response()->json([
                    'error' => 'Abonnement non trouvé',
                    'reference' => $reference,
                    'abonnement_code' => $abonnementCode
                ], 404);
            }

            // Vérifier si déjà payé
            if ($abonnement->payments == 1) {
                return response()->json([
                    'message' => 'Abonnement déjà payé',
                    'abonnement' => $abonnement
                ]);
            }

            // 1. Activer l'abonnement
            $abonnement->payments = 1;
            $abonnement->updated_at = Carbon::now();
            $abonnement->save();

            // 2. Enregistrer la transaction
            $transaction = WebTransactionModels::create([
                'transaction_id' => $abonnement->payment_reference ?? 'MANUAL-' . time(),
                'montant' => $abonnement->montant,
                'operations' => 'Paiement abonnement web - Validation manuelle',
                'method_payment' => 'GeniusPay (Simulé)',
                'date_transaction' => Carbon::now()->format('Y-m-d H:i:s'),
                'status' => 'completed'
            ]);

            // 3. Mettre à jour le solde
            $this->updateSolde($abonnement->montant);

            DB::commit();

            Log::info('✅ Webhook simulé avec succès', [
                'abonnement_code' => $abonnement->abonnement_web_code,
                'reference' => $abonnement->payment_reference
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook simulé avec succès',
                'data' => [
                    'abonnement' => $abonnement,
                    'transaction' => $transaction
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur simulation webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer un abonnement par son code
     */
    public function activateSubscription(Request $request)
    {
        $abonnementCode = $request->get('abonnement_code');

        if (!$abonnementCode) {
            return response()->json(['error' => 'abonnement_code requis'], 400);
        }

        try {
            DB::beginTransaction();

            $abonnement = AbonnementWebModels::where('abonnement_web_code', $abonnementCode)->first();

            if (!$abonnement) {
                return response()->json(['error' => 'Abonnement non trouvé'], 404);
            }

            if ($abonnement->payments == 1) {
                return response()->json(['message' => 'Abonnement déjà actif']);
            }

            $abonnement->payments = 1;
            $abonnement->updated_at = Carbon::now();
            $abonnement->save();

            $this->updateSolde($abonnement->montant);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Abonnement activé avec succès',
                'abonnement' => $abonnement
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function updateSolde(float $amount): void
    {
        $solde = DB::table('web_solde_models')->where('id', 1)->first();

        if ($solde) {
            $commissionRate = 0.03;
            $commission = $amount * $commissionRate;
            $amountNet = $amount - $commission;

            DB::table('web_solde_models')
                ->where('id', 1)
                ->update([
                    'montants' => floatval($solde->montants) + $amount,
                    'montants_net' => floatval($solde->montants_net) + $amountNet,
                    'slug' => CodeGenerator::generateSlugCode(),
                    'updated_at' => Carbon::now()
                ]);
        }
    }
}
