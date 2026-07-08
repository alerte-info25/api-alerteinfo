<?php

ini_set('memory_limit', '256M');

$path = '/home/c2002372c/public_html/api-alerteinfo';
if (!is_dir($path)) {
    error_log("Erreur : Le répertoire $path n'existe pas.");
    exit(1);
}

chdir($path);

// Charger les variables d'environnement
if (file_exists($path . '/.env')) {
    $env = @parse_ini_file($path . '/.env', false, INI_SCANNER_RAW);
    if ($env === false) {
        error_log("Erreur lors de la lecture du fichier .env");
    } else {
        foreach ($env as $key => $value) {
            putenv("$key=$value");
        }
    }
}

$php = '/usr/local/bin/php';
if (!file_exists($php)) {
    error_log("Erreur : L'exécutable PHP ($php) n'existe pas.");
    exit(1);
}

$command = "$php artisan queue:work database --tries=3 --sleep=5 --memory=256";

error_log("Démarrage du traitement de la queue...");

$iterations = 0;
$maxIterations = 100;

while ($iterations < $maxIterations) {
    $output = [];
    $returnVar = 0;

    $memoryBefore = memory_get_usage(true);
    exec($command, $output, $returnVar);
    $memoryAfter = memory_get_usage(true);

    $memoryUsed = $memoryAfter - $memoryBefore;
    error_log("Mémoire utilisée pour cette itération : " . ($memoryUsed / 1024 / 1024) . " MB");

    if ($returnVar !== 0) {
        error_log("Erreur lors de l'exécution de la commande. Code de retour : $returnVar");
        foreach ($output as $line) {
            error_log($line);
        }
    } else {
        error_log("Queue worker exécuté avec succès.");
        error_log("Nombre de jobs traités : " . count($output));
        foreach ($output as $line) {
            error_log($line);
        }
    }

    gc_collect_cycles();

    $iterations++;
    sleep(10);
}

error_log("Script terminé après $maxIterations itérations.");
