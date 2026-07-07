<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use App\Models\NotificationPush;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $deviceToken;
    private $data;
    private $type;

    public function __construct($deviceToken, $data, $type)
    {
        $this->deviceToken = $deviceToken;
        $this->data = $data;
        $this->type = $type;
    }

    public function handle()
    {
        try {
            Log::info("Début du traitement de la notification", [
                'device_token' => $this->deviceToken,
                'type' => $this->type
            ]);

            $oneSignalData = $this->prepareOneSignalData();
            Log::info("Données OneSignal préparées", ['data' => $oneSignalData]);

            $response = $this->sendToOneSignal($oneSignalData);

            if ($response->successful()) {
                Log::info("Notification envoyée avec succès", [
                    'device_token' => $this->deviceToken,
                    'response' => $response->json()
                ]);
                $this->storeNotification(true);
            } else {
                Log::error("Échec de l'envoi de la notification", [
                    'device_token' => $this->deviceToken,
                    'response' => $response->body()
                ]);
                $this->storeNotification(false);
            }
        } catch (Exception $e) {
            Log::error("Exception lors de l'envoi de la notification", [
                'device_token' => $this->deviceToken,
                'error' => $e->getMessage()
            ]);
            Log::error('Erreur lors du traitement du job SendPushNotificationJob', [
                'job_id' => $this->job->getJobId(),
                'error' => $e->getMessage()
            ]);
            $this->storeNotification(false);
            throw $e;
        }
    }

    private function prepareOneSignalData()
    {
        $appId = env('ONESIGNAL_APP_ID');

        if (!$appId) {
            Log::error("ONESIGNAL_APP_ID n'est pas défini dans le fichier .env");
            throw new Exception("Configuration OneSignal manquante");
        }

        $baseData = [
            'app_id' => env('ONESIGNAL_APP_ID'),
            'include_player_ids' => [$this->deviceToken],
            'headings' => ['en' => $this->data['title']],
            'contents' => ['en' => $this->data['body']],
            'small_icon' => 'ic_stat_icon_monochrome',
            'data' => [
                'type' => $this->type,
            ],
        ];



        if ($this->type !== 'general') {
            $baseData['data']['slug'] = $this->data['slug'] ?? null;
            $baseData['data']['paysId'] = $this->data['pays_id'] ?? null;
        }

        if (isset($this->data['media'])) {
            $baseData['large_icon'] = $this->data['media'];
            $baseData['big_picture'] = $this->data['media'];
        }

        return $baseData;
    }

    private function sendToOneSignal($data)
    {
        $apiKey = env('ONESIGNAL_REST_API_KEY');

        if (!$apiKey) {
            Log::error("ONESIGNAL_REST_API_KEY n'est pas défini dans le fichier .env");
            throw new Exception("Configuration OneSignal manquante");
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', $data);

        Log::info("Requête envoyée à OneSignal", [
            'url' => 'https://onesignal.com/api/v1/notifications',
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'data' => $data
        ]);

        Log::info("Réponse de OneSignal", [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return $response;
    }


    // private function sendToOneSignal($data)
    // {
    //     $apiKey = env('ONESIGNAL_REST_API_KEY');

    //     if (!$apiKey) {
    //         Log::error("ONESIGNAL_REST_API_KEY n'est pas défini dans le fichier .env");
    //         // throw new Exception("Configuration OneSignal manquante");
    //     }

    //     $response = Http::withHeaders([
    //         'Authorization' => 'Basic ' . env('ONESIGNAL_REST_API_KEY'),
    //         'Content-Type' => 'application/json',
    //     ])->post('https://onesignal.com/api/v1/notifications', $data);

    //     Log::info("Réponse de OneSignal", [
    //         'status' => $response->status(),
    //         'body' => $response->body()
    //     ]);

    //     return $response;
    // }
    private function storeNotification($sent)
    {
        NotificationPush::create([
            'device_id' => $this->deviceToken,
            'type' => $this->type,
            'title' => $this->data['title'],
            'body' => $this->data['body'],
            'pays_id' => $this->data['pays_id'] ?? null,
            'media' => $this->data['media'] ?? null,
            'slug' => $this->data['slug'] ?? null,
            'sent' => $sent,
        ]);
    }
}
