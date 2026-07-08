<?php

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

$command = "$php artisan notifications:clean";

error_log("Démarrage du nettoyage des notifications...");

$output = [];
$returnVar = 0;
exec($command, $output, $returnVar);

if ($returnVar !== 0) {
    error_log("Erreur lors de l'exécution de la commande. Code de retour : $returnVar");
    foreach ($output as $line) {
        error_log($line);
    }
} else {
    error_log("Nettoyage des notifications exécuté avec succès.");
    foreach ($output as $line) {
        error_log($line);
    }
}

error_log("Fin du nettoyage des notifications.");
