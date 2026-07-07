<?php

namespace App\Http\Controllers\API\V1\Analystics;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Analystics\AnalysticService;



class AnalysticController extends Controller
{
    protected $analysticService;

    public function __construct(AnalysticService $analysticService)
    {
        $this->analysticService = $analysticService;
    }

    public function ctrl_getDefaultAnalysticsData(): JsonResponse
    {
        return $this->analysticService->srv_getDefaultAnalysticsData();
    }
}
