<?php

namespace App\Services\JsonResponseServices;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JsonResponseService
{
    public static function srv_successResponseWithData($message, $data, $statusCode): JsonResponse
    {
        return response()->json([
            'status' => 'Succès',
            'code' => $statusCode,
            'data' => $data,
            'message' => $message
        ], $statusCode);
    }
    public static function srv_successResponse($message, $statusCode): JsonResponse
    {
        return response()->json([
            'status' => 'Succès',
            'code' => $statusCode,
            'message' => $message
        ], $statusCode);
    }
    public static function srv_errorResponse($message,  $statusCode): JsonResponse
    {
        return response()->json([
            'status' => 'Erreur',
            'code' => $statusCode,
            'message' => $message
        ], $statusCode);
    }
}
