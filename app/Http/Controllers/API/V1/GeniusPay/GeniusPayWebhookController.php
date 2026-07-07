<?php
// app/Http/Controllers/API/V1/GeniusPay/GeniusPayWebhookController.php

namespace App\Http\Controllers\API\V1\GeniusPay;

use App\Http\Controllers\Controller;
use App\Models\AbonnementsMobileModels\AbonnementsMobileModels;
use App\Models\AbonnementsWebModels\AbonnementWebModels;
use App\Models\Transactions\TransactionsModels;
use App\Models\WebTransactions\WebTransactionModels;
use App\Services\CodeGenerator;
use App\Services\GeniusPay\GeniusMarchand;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeniusPayWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        try {
            Log::info('Nous sommes bien dans le Genius Webhook');
            // Vérifier la signature du webhook
            $signature = $request->header('X-GeniusPay-Signature');
            // Log::info('Signature', $signature);
            if (!$this->verifyWebhookSignature($signature, $request->getContent())) {
                Log::warning('Signature webhook invalide', ['signature' => $signature]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $payload = $request->all();
            Log::info('Webhook GeniusPay reçu', ['payload' => $payload]);

            // Vérifier le statut du paiement
            if ($payload['status'] !== 'completed' && $payload['status'] !== 'success') {
                Log::info('Paiement non complété', ['status' => $payload['status']]);
                return response()->json(['message' => 'Payment not completed'], 200);
            }

            $reference = $payload['reference'] ?? $payload['transaction_id'] ?? null;
            $metadata = $payload['metadata'] ?? [];
            $amount = $payload['amount'] ?? 0;
            $paymentMethod = $payload['payment_method'] ?? 'GeniusPay';
            $description = $payload['description'] ?? 'Paiement GeniusPay';

            if (!$reference) {
                Log::error('Référence de transaction manquante', ['payload' => $payload]);
                return response()->json(['error' => 'Reference missing'], 400);
            }

            DB::beginTransaction();

            try {
                // 1. TRAITER L'ABONNEMENT WEB
                $webAbonnement = null;
                $isWebAbonnement = false;

                // Vérifier si c'est un abonnement web
                if (isset($metadata['abonnement_web_code'])) {
                    $webAbonnement = AbonnementWebModels::where('abonnement_web_code', $metadata['abonnement_web_code'])->first();
                    $isWebAbonnement = true;

                    if ($webAbonnement) {
                        Log::info('Abonnement Web trouvé', [
                            'code' => $webAbonnement->abonnement_web_code,
                            'payment' => $webAbonnement->payments
                        ]);

                        // Vérifier si l'abonnement n'est pas déjà payé
                        if ($webAbonnement->payments != 1) {
                            // Activer l'abonnement
                            $webAbonnement->payments = 1;
                            $webAbonnement->payment_reference = $reference;
                            $webAbonnement->updated_at = Carbon::now();
                            $webAbonnement->save();

                            // Enregistrer la transaction web
                            $this->saveWebTransaction($reference, $amount, $description, $paymentMethod, 'completed');

                            Log::info('Abonnement Web activé avec succès', [
                                'abonnement_web_code' => $webAbonnement->abonnement_web_code,
                                'montant' => $amount,
                                'reference' => $reference
                            ]);
                        } else {
                            Log::info('Abonnement Web déjà payé', ['code' => $webAbonnement->abonnement_web_code]);
                        }
                    } else {
                        Log::warning('Abonnement Web non trouvé', ['code' => $metadata['abonnement_web_code']]);
                    }
                }

                // 2. TRAITER L'ABONNEMENT MOBILE
                $mobileAbonnement = null;
                $isMobileAbonnement = false;

                if (isset($metadata['abonnement_code'])) {
                    $mobileAbonnement = AbonnementsMobileModels::where('abonnement_code', $metadata['abonnement_code'])->first();
                    $isMobileAbonnement = true;

                    if ($mobileAbonnement) {
                        Log::info('Abonnement Mobile trouvé', [
                            'code' => $mobileAbonnement->abonnement_code,
                            'payment' => $mobileAbonnement->payments
                        ]);

                        // Vérifier si l'abonnement n'est pas déjà payé
                        if ($mobileAbonnement->payments != 1) {
                            // Activer l'abonnement
                            $mobileAbonnement->payments = 1;
                            $mobileAbonnement->payment_reference = $reference;
                            $mobileAbonnement->updated_at = Carbon::now();
                            $mobileAbonnement->save();

                            // Mettre à jour le statut de l'abonné mobile
                            if (isset($mobileAbonnement->abonne_id)) {
                                DB::table('abonnes_mobile_models')
                                    ->where('id', $mobileAbonnement->abonne_id)
                                    ->update([
                                        'status_abonnement' => 1,
                                        'updated_at' => Carbon::now()
                                    ]);
                            }

                            // Enregistrer la transaction mobile
                            $this->saveMobileTransaction($reference, $amount, $description, $paymentMethod, 'completed');

                            Log::info('Abonnement Mobile activé avec succès', [
                                'abonnement_code' => $mobileAbonnement->abonnement_code,
                                'montant' => $amount,
                                'reference' => $reference
                            ]);
                        } else {
                            Log::info('Abonnement Mobile déjà payé', ['code' => $mobileAbonnement->abonnement_code]);
                        }
                    } else {
                        Log::warning('Abonnement Mobile non trouvé', ['code' => $metadata['abonnement_code']]);
                    }
                }

                // 3. METTRE À JOUR LE SOLDE (si un abonnement a été trouvé et activé)
                if (($webAbonnement && $webAbonnement->payments == 1) || ($mobileAbonnement && $mobileAbonnement->payments == 1)) {
                    $this->updateSolde($amount);
                }

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Webhook traité avec succès',
                    'data' => [
                        'type' => $isWebAbonnement ? 'web' : ($isMobileAbonnement ? 'mobile' : 'unknown'),
                        'web_abonnement' => $webAbonnement ? 'activé' : 'non trouvé',
                        'mobile_abonnement' => $mobileAbonnement ? 'activé' : 'non trouvé',
                        'reference' => $reference
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Erreur lors du traitement du webhook', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erreur webhook GeniusPay', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function saveWebTransaction($reference, $amount, $description, $paymentMethod, $status)
    {
        try {
            // Vérifier si la transaction existe déjà
            $existing = WebTransactionModels::where('transaction_id', $reference)->first();

            if ($existing) {
                if ($existing->status != 'completed') {
                    $existing->status = $status;
                    $existing->save();
                    Log::info('Transaction Web mise à jour', ['reference' => $reference]);
                }
                return;
            }

            // Créer une nouvelle transaction web
            $transaction = new WebTransactionModels();
            $transaction->transaction_id = $reference;
            $transaction->montant = $amount;
            $transaction->operations = $description;
            $transaction->method_payment = $paymentMethod;
            $transaction->date_transaction = Carbon::now()->format('Y-m-d H:i:s');
            $transaction->status = $status;
            $transaction->save();

            Log::info('Transaction Web enregistrée', ['reference' => $reference]);

        } catch (\Throwable $e) {
            Log::error('Erreur sauvegarde transaction web', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function saveMobileTransaction($reference, $amount, $description, $paymentMethod, $status)
    {
        try {
            // Vérifier si la transaction existe déjà
            $existing = TransactionsModels::where('transaction_id', $reference)->first();

            if ($existing) {
                if ($existing->status != 'completed') {
                    $existing->status = $status;
                    $existing->save();
                    Log::info('Transaction Mobile mise à jour', ['reference' => $reference]);
                }
                return;
            }

            // Créer une nouvelle transaction mobile
            $transaction = new TransactionsModels();
            $transaction->transaction_id = $reference;
            $transaction->montant = $amount;
            $transaction->operations = $description;
            $transaction->method_payment = $paymentMethod;
            $transaction->date_transaction = Carbon::now()->format('Y-m-d H:i:s');
            $transaction->status = $status;
            $transaction->save();

            Log::info('Transaction Mobile enregistrée', ['reference' => $reference]);

        } catch (\Throwable $e) {
            Log::error('Erreur sauvegarde transaction mobile', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function updateSolde(float $amount): void
    {
        try {
            $solde = DB::table('web_solde_models')->where('id', 1)->first();

            if ($solde) {
                // Calculer les frais de commission (3%)
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

                Log::info('Solde mis à jour', [
                    'ancien_montant' => $solde->montants,
                    'nouveau_montant' => floatval($solde->montants) + $amount,
                    'amount' => $amount
                ]);
            } else {
                // Créer le solde s'il n'existe pas
                DB::table('web_solde_models')->insert([
                    'montants' => $amount,
                    'montants_net' => $amount - ($amount * 0.03),
                    'slug' => CodeGenerator::generateSlugCode(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                Log::info('Solde créé', ['amount' => $amount]);
            }
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour solde', ['error' => $e->getMessage()]);
        }
    }

    private function verifyWebhookSignature(?string $signature, string $payload): bool
    {
        if (empty($signature)) {
            Log::warning('Signature webhook manquante');
            return false;
        }

        // Vérifier avec le secret du webhook
        $webhookSecret = GeniusMarchand::getWebhookSecret();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        // Vérifier si les signatures correspondent
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Signature mismatch', [
                'received' => $signature,
                'expected' => $expectedSignature
            ]);
            return false;
        }

        return true;
    }
}
