<?php
namespace App\Http\Controllers\API\V1\CategorieGenerale;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\CategorieGeneraleServices\CategorieGeneraleService;



class CategorieGeneraleController extends Controller
{
    public function __construct(
        private readonly CategorieGeneraleService $categorieGeneraleServiceve
    ){}

    public function ctrl_getCategorieGeneraleList(Request $request): JsonResponse
    {
        return $this->categorieGeneraleServiceve->srv_getCategorieGeneraleList($request);
    }

    public function ctrl_getCategorieGeneraleFormData(): JsonResponse
    {
        return $this->categorieGeneraleServiceve->srv_getCategorieGeneraleFormData();
    }

    public function ctrl_storeCategorieGenerale(Request $request): JsonResponse
    {
        return $this->categorieGeneraleServiceve->srv_createCategorieGenerale($request);
    }

    public function ctrl_updateCategorieGenerale(Request $request, $slug): JsonResponse
    {
        return $this->categorieGeneraleServiceve->srv_updateCategorieGenerale($request, $slug);
    }

    public function ctrl_destroyCategorieGenerale($slug): JsonResponse
    {
        return $this->categorieGeneraleServiceve->srv_deleteCategorieGenerale($slug);
    }
}
