<?php

namespace App\Observers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Redactions\DepecheModels;
use App\Models\WebFcm\WebFcmTokenModels;
use App\Services\FrontendAlerteInfoServices\FrontendAlerteInfoService;

class DepecheModelsObserver
{
    protected $frontendAlerteInfoService;
    public function __construct(FrontendAlerteInfoService $frontendAlerteInfoService)
    {
        $this->frontendAlerteInfoService = $frontendAlerteInfoService;
    }
    /**
     * Handle the DepecheModels "created" event.
     */
    public function created(DepecheModels $depecheModels): void
    {
        $tokens = WebFcmTokenModels::pluck('tokens')->flatten()->toArray();

        if (empty($tokens)) {
            Log::error("Aucun token FCM disponible.");
        }

        $accessToken = $this->frontendAlerteInfoService->getOAuthToken();
        if (!$accessToken) {
            Log::error("Échec de la génération du jeton OAuth 2.0.' : "  );
        }

        
        $requestData = [
            'titre' => $depecheModels->titre,
            'media_url' => $depecheModels->media_url,
            'slug' => $depecheModels->slug,
        ];

        foreach ($tokens as $token) {
            $this->sendFCMPushNotification($token, $requestData, $accessToken);
        }
    }

    
    private function sendFCMPushNotification(string $token, array $requestData, string $accessToken)
    {
        // Construisez le payload JSON
        try {
            $projectId = "alerte-info-web-push";

            $data = [
                "message" => [
                    "token" => $token,
                    "notification" => [
                        "title" => $requestData['titre'],
                        "body" => 'Nouvel article publié : ' . $requestData['titre'],
                    ],
                    "data" => [
                        "slug" => $requestData['slug'] ?? null,
                        "article_titre" => $requestData['titre'] ?? null,
                        "article_image" => $requestData['media_url'] ?? null,
                    ],
                ],
            ];

        
            $dataString = json_encode($data);

            $headers = [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                Log::error("Erreur cURL : " . curl_error($ch));
                curl_close($ch);
                return;
            }

            curl_close($ch);

            $responseData = json_decode($response, true);

            if (isset($responseData['error'])) {
                Log::error("Erreur FCM : " . json_encode($responseData['error']));
            } else {
                Log::info("Notification envoyée avec succès pour le token $token");
            }

        } catch (\Throwable $th) {
            Log::error("Erreur notification : " . $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
        }
    }
}
