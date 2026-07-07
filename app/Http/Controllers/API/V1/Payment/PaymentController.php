<?php

namespace App\Http\Controllers\API\V1\Payment;
use App\Http\Controllers\Controller;
use App\Services\FineoPay\FineoPay;
use Illuminate\Support\Facades\Request;

class PaymentController extends Controller
{

    // Dans PaymentController.php
    public function fineoCallback(Request $request)
    {
        $data = $request->all();

        // Log du callback pour débogage
        \Log::info('FineoPay Callback reçu', $data);

        // Vérifier le statut du paiement
        if (($data['status'] ?? '') === 'success') {
            $syncRef = $data['syncRef'];     // C'est ton transaction_id
            $amount = $data['amount'];
            $reference = $data['reference'];  // Référence FineoPay

            // Mettre à jour le statut de la demande dans ta base
            // Table "demandes" ou "operations"
            DB::table('demandes')
                ->where('operation_code', $syncRef)
                ->update([
                    'payment_status' => 'paid',
                    'payment_reference' => $reference,
                    'payment_amount' => $amount,
                    'payment_date' => now(),
                    'updated_at' => now()
                ]);

            // Optionnel : envoyer un email de confirmation
            // Mail::to(...)->send(...);

            return response()->json(['status' => 'ok'], 200);
        }

        // Paiement échoué
        if (isset($data['syncRef'])) {
            DB::table('demandes')
                ->where('operation_code', $data['syncRef'])
                ->update([
                    'payment_status' => 'failed',
                    'payment_message' => $data['message'] ?? 'Échec du paiement',
                    'updated_at' => now()
                ]);
        }

        return response()->json(['status' => 'ok'], 200);
    }
}
