<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NotificationPush;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanOldNotifications extends Command
{
    protected $signature = 'notifications:clean';
    protected $description = 'Nettoie les anciennes notifications, en gardant seulement les 7 derniers jours pour chaque appareil';

    public function handle()
    {
        $this->info('Début du nettoyage des anciennes notifications...');
        Log::info('Démarrage du processus de nettoyage des notifications');

        $sevenDaysAgo = now()->subDays(7);

        try {
            DB::transaction(function () use ($sevenDaysAgo) {
                // Étape 1 : Sélectionner les IDs à conserver
                $idsToKeep = DB::table('notification_pushes')
                    ->select(DB::raw('MAX(id) as id'))
                    ->where('created_at', '>=', $sevenDaysAgo)
                    ->groupBy('device_id')
                    ->pluck('id');

                // Étape 2 : Supprimer les anciennes notifications
                $deletedCount = NotificationPush::where('created_at', '<', $sevenDaysAgo)
                    ->whereNotIn('id', $idsToKeep)
                    ->delete();

                $this->info("{$deletedCount} anciennes notifications supprimées.");
                Log::info("{$deletedCount} anciennes notifications supprimées.");
            });

            $this->info('Nettoyage des notifications terminé avec succès.');
            Log::info('Nettoyage des notifications terminé avec succès.');
        } catch (\Exception $e) {
            $this->error('Une erreur est survenue pendant le processus de nettoyage : ' . $e->getMessage());
            Log::error('Erreur pendant le nettoyage des notifications : ' . $e->getMessage());
        }
    }
}
