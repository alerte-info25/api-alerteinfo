<?php

namespace App\Http\Controllers\API\V1\UserAccountManagers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\AccountServices\RoleManagerService;
use App\Services\JsonResponseServices\JsonResponseService;

class RoleManagerController extends Controller
{
    public function __construct(
        private readonly RoleManagerService $roleManagerService,
        private readonly JsonResponseService $jsonResponseService
    ) {}

    public function ctrl_getRoles(Request $request)
    {
        return $this->roleManagerService->srv_getRole($request);
    }

    public function ctrl_createRole(Request $request): JsonResponse
    {
        return $this->roleManagerService->srv_createRole($request);
    }

    public function ctrl_updateRole(Request $request, $slug)
    {
        return $this->roleManagerService->srv_updateRole($request, $slug);
    }

    public function ctrl_destroyRole($slug): JsonResponse
    {
        return $this->roleManagerService->srv_deleteRole($slug);
    }

}
