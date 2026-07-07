<?php

namespace App\Services;

use Exception;
use Throwable;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Jobs\SendPushNotificationJob;
use Illuminate\Support\Facades\Queue;
use App\Models\Redactions\DepecheModels;
use App\Models\Redactions\CountriesModels;

class NotificationPushService
{
    public function sendBulkNotifications($content, $type = 'depeche')
    {
        try {
            Log::info("Début de sendBulkNotifications", ['type' => $type]);

            $batchSize = 1000; // Ajustez selon les capacités serveur
            $totalDevices = UserDevice::count();
            $processedDevices = 0;

            Log::info("Nombre total d'appareils : {$totalDevices}");

            UserDevice::chunk($batchSize, function ($devices) use ($content, $type, &$processedDevices, $totalDevices) {
                foreach ($devices as $device) {
                    if ($device->device_id) {
                        $this->queueNotification($device->device_id, $content, $type);
                        $processedDevices++;
                    } else {
                        Log::warning("Appareil sans device_id trouvé", ['device_id' => $device->id]);
                    }
                }
                Log::info("Progression : {$processedDevices}/{$totalDevices} appareils traités");
            });

            Log::info("Fin de sendBulkNotifications", ['processed' => $processedDevices, 'total' => $totalDevices]);
            return true;
        } catch (Exception $e) {
            Log::error("Erreur lors de la préparation des notifications en masse : " . $e->getMessage(), [
                'exception' => $e,
                'type' => $type,
                'content' => json_encode($content)
            ]);
            return false;
        }
    }

    private function queueNotification($deviceToken, $content, $type)
    {
        try {
            $data = $this->prepareNotificationData($content, $type);
            Queue::push(new SendPushNotificationJob($deviceToken, $data, $type));
            Log::info("Notification mise en file d'attente", ['device_token' => $deviceToken, 'type' => $type]);
        } catch (Exception $e) {
            Log::error("Erreur lors de la mise en file d'attente de la notification", [
                'device_token' => $deviceToken,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function prepareNotificationData($content, $type)
    {
        Log::info("Préparation des données de notification", ['type' => $type]);

        try {
            switch ($type) {
                case 'depeche':
                    return [
                        'title' => $content->titre,
                        'body' => $content->lead,
                        'pays_id' => $content->pays_id,
                        'media' => $content->media_url,
                        'slug' => $content->slug,
                    ];
                case 'flash':
                    $pays = CountriesModels::find($content->pays_id);
                    $paysName = $pays ? $pays->pays : 'Inconnu';
                    return [
                        'title' => "Flash - " . $paysName,
                        'body' => $content->contenus,
                        'pays_id' => $content->pays_id,
                        'slug' => $content->slug,
                    ];
                case 'general':
                    return [
                        'title' => $content['title'],
                        'body' => $content['body'],
                    ];
                default:
                    throw new Exception("Type de notification non pris en charge");
            }
        } catch (Exception $e) {
            Log::error("Erreur lors de la préparation des données de notification", [
                'type' => $type,
                'error' => $e->getMessage(),
                'content' => json_encode($content)
            ]);
            throw $e;
        }
    }

    public function sendGeneralNotification($title, $body)
    {
        try {
            Log::info("Envoi d'une notification générale", ['title' => $title]);
            return $this->sendBulkNotifications(['title' => $title, 'body' => $body], 'general');
        } catch (Throwable $th) {
            Log::error('errorlo'.$th);
        }

    }
}
