<?php

namespace App\Observers;

use App\Models\UserDevice;
use Illuminate\Support\Str;
use App\Models\NotificationPush;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Redactions\FlashesModels;
use App\Models\Redactions\CountriesModels;

class FlashModelObserver
{
    /**
     * Handle the FlashesModels "created" event.
     */
    public function created(FlashesModels $flashesModels): void
    {
        $devices = UserDevice::all();

        if ($devices->isEmpty()) {
            Log::error("Aucun appareil enregistré pour envoyer la notification flash");
        }

        foreach ($devices as $device) {
            try {
                // Envoi de la notification seulement si l'appareil est enregistré
                $this->sendFlashNotification(
                    $device->device_id, // Utiliser le token de l'appareil pour l'envoi de la notification
                    $flashesModels->contenus,
                    paysId: $flashesModels->pays_id,
                    flashId: $flashesModels->id
                );
            } catch (\Throwable $e) {
                // Log de l'erreur en cas de problème lors de l'envoi de la notification
                Log::error("Erreur lors de l'envoi de la notification flash à l'appareil avec token: {$device->device_id} : " . $e->getMessage());
            }
        }
    }

    /**
     * Handle the FlashesModels "updated" event.
     */
    public function updated(FlashesModels $flashesModels): void
    {
        //
    }

    /**
     * Handle the FlashesModels "deleted" event.
     */
    public function deleted(FlashesModels $flashesModels): void
    {
        //
    }

    /**
     * Handle the FlashesModels "restored" event.
     */
    public function restored(FlashesModels $flashesModels): void
    {
        //
    }

    /**
     * Handle the FlashesModels "force deleted" event.
     */
    public function forceDeleted(FlashesModels $flashesModels): void
    {
        //
    }



    private function sendFlashNotification($deviceToken, $body, $paysId, $flashId)
    {
        try {
            // Récupérer les détails du pays
            $pays = CountriesModels::find($paysId);
            $notificationTitle = 'Flash - ' . ($pays ? $pays->pays : 'N/A');
            $notificationBody = Str::limit($body, 80, '...');

            // Préparer les données de la notification
            $notificationData = [
                'device_id' => $deviceToken,
                'type' => 'flash',
                'title' => $notificationTitle,
                'pays_id' => $paysId,
                'body' => $notificationBody,
                'sent' => false,
            ];

            // Préparer les données pour OneSignal
            $data = [
                'app_id' => env('ONESIGNAL_APP_ID'),
                'include_player_ids' => [$deviceToken],
                'headings' => ['en' => $notificationTitle],
                'contents' => ['en' => $notificationBody],
                'small_icon' => 'ic_stat_icon_monochrome',
                'data' => [
                    'type' => 'flash',
                    'paysId' => $paysId,
                    'flashId' => $flashId,
                    'flashBody' => $notificationBody
                ],
            ];

            // Envoyer la notification via OneSignal
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . env('ONESIGNAL_REST_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', $data);

            if ($response->successful()) {
                $notificationData['sent'] = true;
            }

            // Sauvegarder les données de la notification dans la base de données
            NotificationPush::create($notificationData);

            return $response->json();

        } catch (\Throwable $e) {
            // Log de l'erreur en cas de problème lors de l'envoi de la notification
            Log::error("Erreur lors de l'envoi de la notification Flash : " . $e->getMessage());

            // Sauvegarder les données de la notification dans la base de données avec le statut d'erreur
            NotificationPush::create(array_merge($notificationData, [
                'sent' => false,
            ]));


        }
    }
}
