<?php
namespace App\Logs;

use Illuminate\Support\Facades\Log;

class CustomLogError
{
    /**
     * Summary of logError
     * @param mixed $message
     * @param mixed $error
     * @return void
     */
    public function logError($message,$error)
    {
        // Log the error message with context
        Log::error(
            "{$message} : " . $error->getMessage(),
            [
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'trace' => $error->getTraceAsString()
            ]
        );
    }

    public function logInfo($message, $data = [])
    {
        // Log the info message with context
        Log::info(
            $message,
            [
                'data' => $data
            ]
        );
    }
    public function logWarning($message, $data = [])
    {
        // Log the warning message with context
        Log::warning(
            $message,
            [
                'data' => $data
            ]
        );
    }
}
