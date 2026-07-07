<?php

namespace App\Http\Controllers\API\V1\Transactions;

use App\Http\Controllers\Controller;
use App\Models\LicenceModels\LicenceModel;
use App\Models\Soldes\TransactionsModels;
use App\Services\FineoPay\FineoPay;
use App\Services\FineoPay\Marchand;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FineoNotifyController extends Controller
{
    public function notify(Request $request)
    {
        Log::info('Webhook FineoPay reçu', ['payload' => $request->all()]);

        // Récupère la transaction_id (syncRef) envoyée par FineoPay
        // Supposons que FineoPay envoie un JSON avec "syncRef" ou "reference"
        $payload = $request->all();
        $transactionId = $payload['syncRef'] ?? $payload['reference'] ?? null;

        if (!$transactionId) {
            Log::error('FineoPay: transaction_id manquant', ['payload' => $payload]);
            http_response_code(400);
            exit;
        }

        try {
            // Instancie le service FineoPay pour vérifier le statut
            $fineoPay = new FineoPay();
            $status = $fineoPay->verifyPayment($transactionId);

            Log::info('FineoPay statut vérifié', ['status' => $status]);

            if (!$status['success']) {
                // Paiement échoué ou en attente ?
                // On peut traiter selon le statut, mais on ne met pas à jour la licence si non payé
                http_response_code(200);
                exit;
            }

            // Si le paiement est réussi (status 'success')
            DB::transaction(function () use ($status, $transactionId) {
                $licence = LicenceModel::where('operation_code', $transactionId)
                    ->lockForUpdate()
                    ->first();

                if (!$licence) {
                    Log::warning('Licence non trouvée pour transaction', ['transaction_id' => $transactionId]);
                    return;
                }

                if ($licence->payment === 'payed') {
                    // Déjà traité, idempotence
                    return;
                }

                // 🔑 Génération du numéro de licence (réutilise ta méthode)
                $numLicence = $this->generateFIALicenceNumber();

                // 🔄 Mise à jour de la licence
                $licence->update([
                    'payment' => 'payed',
                    'num_licence' => $numLicence,
                    'request_date' => now(),
                ]);

                // 💳 Enregistrer la transaction
                TransactionsModels::create([
                    'transaction_id' => $transactionId,
                    'montant' => $status['amount'],
                    'operations' => $licence->operation_type . ' - ' . $licence->categorie->categorie_name ?? 'Licence',
                    'method_payment' => $status['payment_method'] ?? 'FineoPay',
                    'meta_data' => json_encode($status),
                    'date_transaction' => $status['payment_date'] ?? now(),
                    'status' => 'payed',
                ]);

                // 💵 Mise à jour du solde (comme avant)
                $amountNet = $this->calculeMontantNet(
                    $status['payment_method'] ?? 'fineo',
                    (int) $status['amount']
                );

                DB::table('solde_models')
                    ->where('id', 1)
                    ->update([
                        'montants' => DB::raw("`montants` + " . (int) $status['amount']),
                        'montants_net' => DB::raw("`montants_net` + {$amountNet}"),
                        'slug' => Str::uuid(),
                    ]);
            });

            http_response_code(200);
            exit;

        } catch (Exception $e) {
            Log::error('FineoPay webhook échoué', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId
            ]);
            http_response_code(500);
            exit;
        }
    }

    // Copie les méthodes privées depuis ton ancien NotifyController
    private function generateFIALicenceNumber(): string
    {
        $abreviation = 'FIA';
        $yearSuffix = date('y'); // ex: "25"

        return DB::transaction(function () use ($abreviation, $yearSuffix) {
            // 🔒 Verrouille la dernière licence payée de l'année
            $lastLicence = LicenceModel::where('payment', 'payed')
                ->where('num_licence', 'like', $abreviation . $yearSuffix . '.%')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $lastCode = $lastLicence?->num_licence;

            // Si premier de l'année
            if ($lastCode === null) {
                return $abreviation . $yearSuffix . '.000001';
            }

            // Vérifier le format avec regex sécurisée
            $escapedAbbr = preg_quote($abreviation, '/');
            if (!preg_match('/^' . $escapedAbbr . '\d{2}\.([0-9]+)([A-Z]*)$/', $lastCode, $matches)) {
                return $abreviation . $yearSuffix . '.000001';
            }

            $lastNumber = (int) $matches[1];
            $lastLetter = $matches[2] ?? '';
            $newNumber = $lastNumber + 1;
            $newLetter = $lastLetter;

            // Gérer le dépassement de 999999
            if ($newNumber > 999999) {
                $newNumber = 1;
                $newLetter = $lastLetter === '' ? 'A' : $this->incrementLetter($lastLetter);
            }

            return $abreviation . $yearSuffix . '.' . str_pad($newNumber, 6, '0', STR_PAD_LEFT) . $newLetter;
        });
    }

    private function incrementLetter(string $letters): string
    {
        $letters = str_split(strrev($letters));
        $carry = true;
        $result = [];

        foreach ($letters as $char) {
            if ($carry) {
                if ($char === 'Z') {
                    $result[] = 'A';
                    $carry = true;
                } else {
                    $result[] = chr(ord($char) + 1);
                    $carry = false;
                }
            } else {
                $result[] = $char;
            }
        }

        if ($carry) {
            $result[] = 'A';
        }

        return strrev(implode('', $result));
    }

    private function calculeMontantNet(string $method, int $amount): float
    {
        $tarif = Marchand::getCurrentTarif($method);
        return $amount - ($amount * $tarif);
    }
}
