<?php

namespace App\Services\UploadFileManagerServices;

use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Laravel\Facades\Image;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;

class UploadFileManagerService
{
    protected $jsonResponseService;

    public function __construct(
        JsonResponseService $jsonResponseService,
    )
    {
        $this->jsonResponseService = $jsonResponseService;
    }






     // upload default file
    /**
     * Summary of uploadDefaultFile
     * @param mixed $request
     * @param mixed $fileField
     * @param mixed $allowedExtensions
     * @param mixed $storageFolderName
     * @param mixed $folderName
     * @param mixed $prefix
     * @return string|null
     */
    public static function uploadDefaultFile(
        $request,
        $fileField,
        $allowedExtensions,
        $storageFolderName,
        $folderName,
        $prefix
    )

    {
        $maxFileSize = 2 * 1024 * 1024; // Taille maximale : 2 Mo

        // Vérifier si un fichier est fourni
        if (!$request->hasFile($fileField)) {
            return null; // Retourne null si aucun fichier n'est fourni
        }

        $file = $request->file($fileField);

        // Vérifier l'extension du fichier
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            return "invalid_file_type"; // Erreur : type de fichier non autorisé
        }

        // Vérifier la taille du fichier
        if ($file->getSize() > $maxFileSize) {
            return "file_too_large"; // Erreur : fichier trop volumineux
        }

        // Transformer le nom du dossier
        $folderNameConverted = strtoupper(str_replace(' ', '_', $folderName));

        // Définir le dossier de stockage
        $storageFolder = "{$storageFolderName}/{$folderNameConverted}";

        // Créer le répertoire si nécessaire
        if (!Storage::disk('public')->exists($storageFolder)) {
            Storage::disk('public')->makeDirectory($storageFolder);
        }

        // Générer un nom unique pour le fichier
        $filename = date('Ymd_His') . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9]/', '_', $prefix) . '.' . $extension;

        try {
            // Si c'est une image, redimensionner avec ImageManager
            if (in_array($extension, ['jpg', 'jpeg', 'png','webp'])) {
                $manager = new ImageManager(new Driver());
                $img = $manager->read($file->getPathname());

                // Redimensionner uniquement si le dossier est "PHOTOS"
                if (strtoupper($folderName) === 'PHOTOS') {
                    $img = $img->cover(300, 300); // Redimensionnement spécifique pour les photos
                }

                // Enregistrer l'image dans le dossier de stockage
                $filePath = "{$storageFolder}/{$filename}";
                Storage::disk('public')->put($filePath, (string) $img->encode());
            } else {
                // Pour les autres types de fichiers (ex: PDF), utiliser putFileAs
                $filePath = "{$storageFolder}/{$filename}";
                Storage::disk('public')->putFileAs($storageFolder, $file, $filename);
            }

            // Retourner le chemin du fichier
            return $filePath;
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'upload du fichier : " . $e->getMessage());
            return "file_upload_failed"; // Erreur : échec de l'upload
        }
    }

    /**
     * Gère les erreurs liées à l'upload de fichiers.
     *
     * @param string|null $error Code d'erreur retourné par uploadDefaultFile().
     * @param string $allowedExtensions Liste des extensions autorisées.
     * @return string|null Message d'erreur ou null si OK.
     */
    public  function handleFileUploadError($filePath, string $allowedExtensions)
    {
        // Vérifier si le chemin du fichier est une chaîne valide
        if (!is_string($filePath)) {
            Log::warning("handleFileUploadError : filePath n'est pas une chaîne valide.");

            return $this->jsonResponseService->srv_errorResponse(
                "handleFileUploadError : filePath n'est pas une chaîne valide.",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // Gestion des erreurs spécifiques
        switch ($filePath) {
            case "invalid_file_type":
                return $this->jsonResponseService->srv_errorResponse(
                    "Le format du fichier est incorrect. Seules les extensions $allowedExtensions sont acceptées.",
                    Response::HTTP_BAD_REQUEST
                );

            case "file_upload_failed":
                return $this->jsonResponseService->srv_errorResponse(
                    "Erreur lors de l'upload du fichier.",
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );

            case "file_too_large":
                return $this->jsonResponseService->srv_errorResponse(
                    "Le fichier est trop volumineux. La taille maximale est de 2 Mo.",
                    Response::HTTP_BAD_REQUEST
                );

            default:
                return null; // Aucune erreur détectée
        }
    }

}
