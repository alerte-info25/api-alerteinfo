<?php

namespace App\Services\FineoPay;

use Exception;
use Illuminate\Support\Facades\Log;

class FineoPay
{
    private string $businessCode;
    private string $apiKey;
    private string $baseUrl;
    private bool $isSandbox;
    public string $amount;
    public string $currency = 'XOF';
    public ?string $transaction_id;
    public ?string $customer_name;
    public ?string $customer_surname;
    public ?string $description;
    public ?string $notify_url;
    public ?string $return_url;
    public ?string $channels;
    public ?string $customer_email;
    public ?string $customer_phone_number;
    public ?string $chk_payment_date;
    public ?string $chk_payment_method;
    public ?string $chk_amount;
    public ?string $chk_currency;
    public ?string $chk_status;
    public ?string $chk_reference;
    public ?string $chk_message;

    public function __construct(?string $businessCode = null, ?string $apiKey = null, bool $isSandbox = true)
    {
        $this->businessCode = $businessCode ?? Marchand::getBusinessCode();
        $this->apiKey = $apiKey ?? Marchand::getApiKey();
        $this->isSandbox = $isSandbox ?? Marchand::isSandbox();
        $this->baseUrl = 'https://api.fineopay.com/api/v1/business/dev';
        $this->notify_url = Marchand::getCallbackUrl();
        $this->return_url = Marchand::getReturnUrl();

        $this->transaction_id = null;
        $this->customer_name = null;
        $this->customer_surname = null;
        $this->description = null;
        $this->channels = null;
        $this->customer_email = null;
        $this->customer_phone_number = null;
        $this->chk_payment_date = null;
        $this->chk_payment_method = null;
        $this->chk_amount = null;
        $this->chk_currency = null;
        $this->chk_status = null;
        $this->chk_reference = null;
        $this->chk_message = null;
        $this->amount = '0';
    }
    public function generatePaymentLink(?array $param): array
    {
        $this->checkDataExist($param);
        $this->transaction_id = $param['transaction_id'] ?? $this->generateTransId();
        $this->amount = $param['amount'];
        $this->currency = $param['currency'] ?? 'XOF';
        $this->description = $param['description'] ?? 'Paiement FIA';
        $this->customer_name = $param['customer_name'] ?? '';
        $this->customer_surname = $param['customer_surname'] ?? '';
        $this->customer_email = $param['customer_email'] ?? '';
        $this->customer_phone_number = $param['customer_phone_number'] ?? '';

        if (!empty($param['notify_url'])) {
            $this->notify_url = $param['notify_url'];
        }
        if (!empty($param['return_url'])) {
            $this->return_url = $param['return_url'];
        }
        $payload = [
            'title'       => 'FIA - ' . $this->description,
            'amount'      => (int) $this->amount,
            'callbackUrl' => $this->notify_url,
            'syncRef'     => $this->transaction_id,
        ];

        $response = $this->httpRequest('/checkout-link', 'POST', $payload);
        Log::info("Réponse FineoPay generatePaymentLink", ['response' => $response]);
        if ($response['success'] === true) {
            return [
                'code' => 201,
                'message' => 'Lien de paiement généré avec succès',
                'data' => [
                    'payment_url' => $response['data']['checkoutLink'],
                    'transaction_id' => $this->transaction_id
                ]
            ];
        }

        return [
            'code' => 400,
            'message' => $response['message'] ?? 'Erreur lors de la génération du lien de paiement',
            'data' => null
        ];
    }
    public function getPayStatus(string $id_transaction, ?string $site_id = null): void
    {
        $response = $this->httpRequest("/transactions/{$id_transaction}", 'GET');

        if ($response['success'] ?? false) {
            $transaction = $response['data'] ?? $response;

            $this->chk_status = $transaction['status'] ?? 'unknown';
            $this->chk_reference = $transaction['reference'] ?? $id_transaction;
            $this->chk_amount = $transaction['amount'] ?? null;
            $this->chk_currency = $transaction['currency'] ?? 'XOF';
            $this->chk_payment_method = $transaction['canal'] ?? null;
            $this->chk_payment_date = $transaction['timestamp'] ?? null;
            $this->chk_message = $transaction['message'] ?? ($this->chk_status === 'success' ? 'Paiement réussi' : 'Paiement échoué');
        } else {
            $this->chk_status = 'error';
            $this->chk_message = $response['message'] ?? 'Erreur lors de la vérification';
        }
    }
    private function checkDataExist(array $param): void
    {
        if (empty($this->businessCode)) {
            throw new Exception("Erreur: businessCode non défini");
        }
        if (empty($this->apiKey)) {
            throw new Exception("Erreur: apiKey non défini");
        }
        if (empty($param['amount'])) {
            throw new Exception("Erreur: Amount non défini");
        }
    }

    /**
     * Requête HTTP vers l'API FineoPay
     */
    private function httpRequest(string $endpoint, string $method = 'POST', ?array $data = null): array
    {
        if (!function_exists('curl_version')) {
            throw new Exception("Vous devez activer cURL pour utiliser FineoPay");
        }

        $ch = curl_init($this->baseUrl . $endpoint);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false, // À mettre à true en prod avec certificat
            CURLOPT_HTTPHEADER => [
                "businessCode: {$this->businessCode}",
                "apiKey: {$this->apiKey}",
                "Content-Type: application/json"
            ]
        ]);

        if ($method === 'POST' && $data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("Erreur cURL FineoPay: " . $curlError);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            return [
                'success' => false,
                'message' => $decoded['message'] ?? "Erreur HTTP $httpCode",
                'code' => $httpCode
            ];
        }

        return $decoded;
    }
    public function generateTransId(): string
    {
        return 'FIA-' . time() . '-' . mt_rand(1000, 9999);
    }

    // Champs pour paiement
    private function buildInputs(array $param): array
    {
        $inputs = [];

        // 1. Nom complet (pré-rempli)
        if (!empty($param['customer_surname']) || !empty($param['customer_name'])) {
            $inputs[] = [
                'key'      => 'nom_complet',
                'type'     => 'text',
                'label'    => 'Nom complet',
                'required' => true,
                'default'  => trim(($param['customer_surname'] ?? '') . ' ' . ($param['customer_name'] ?? ''))
            ];
        }

        // 2. Genre (si disponible)
        if (!empty($param['invoice_data']['Genre'] ?? null)) {
            $inputs[] = [
                'key'      => 'genre',
                'type'     => 'list',  // Type "list" pour un menu déroulant
                'label'    => 'Genre',
                'required' => false,
                'default'  => $param['invoice_data']['Genre'],
                'options'  => ['M' => 'Masculin', 'F' => 'Féminin']  // Optionnel
            ];
        }

        // 3. Date de naissance
        if (!empty($param['invoice_data']['Date de naissance'] ?? null)) {
            $inputs[] = [
                'key'      => 'date_naissance',
                'type'     => 'text',
                'label'    => 'Date de naissance',
                'required' => false,
                'default'  => $param['invoice_data']['Date de naissance']
            ];
        }

        // 4. Nationalité
        if (!empty($param['invoice_data']['Nationalité'] ?? null)) {
            $inputs[] = [
                'key'      => 'nationalite',
                'type'     => 'text',
                'label'    => 'Nationalité',
                'required' => false,
                'default'  => $param['invoice_data']['Nationalité']
            ];
        }

        // 5. Téléphone (IMPORTANT pour Mobile Money)
        if (!empty($param['customer_phone_number'])) {
            $inputs[] = [
                'key'      => 'telephone',
                'type'     => 'tel',
                'label'    => 'Téléphone',
                'required' => true,
                'default'  => $param['customer_phone_number']
            ];
        }

        // 6. Email
        if (!empty($param['customer_email'])) {
            $inputs[] = [
                'key'      => 'email',
                'type'     => 'email',
                'label'    => 'Adresse email',
                'required' => false,
                'default'  => $param['customer_email']
            ];
        }

        return $inputs;
    }

    // Dans App\Services\FineoPay\FineoPay.php

/**
 * Vérifier le statut d'un paiement et retourner un tableau structuré
 */
    public function verifyPayment(string $transactionId): array
    {
        $this->getPayStatus($transactionId);

        return [
            'success' => $this->chk_status === 'success',
            'status' => $this->chk_status,
            'transaction_id' => $transactionId,
            'reference' => $this->chk_reference,
            'amount' => $this->chk_amount,
            'currency' => $this->chk_currency,
            'payment_method' => $this->chk_payment_method,
            'payment_date' => $this->chk_payment_date,
            'message' => $this->chk_message,
        ];
    }
}
