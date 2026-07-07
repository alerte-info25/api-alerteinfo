<?php

namespace App\Services\CustomLogServices;

use Illuminate\Support\Facades\Log;

class CustomLogService

{
    public function info($message, $data = [])
    {
        // Log the info message with context
        Log::info(
            "['INFO'] " .$message,
            [
                'data' => $data
            ]
        );
    }



    public function warning($message, $data = [])
    {
        Log::warning(
            "['WARNING'] " .$message,
            [
                'data' => $data
            ]
        );
    }


    public function error($message, $error)
    {
        Log::error(
            "['ERROR'] " .$message,
            [
                'eror' => $error->getMessage(),
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'trace' => $error->getTraceAsString()
            ]
        );
    }

}
