<?php

namespace App\Http\Controllers\API\V1\UserAccountManagers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\AccountServices\UsersAccountServices;

class UserAccountManagerController extends Controller
{
    public function __construct(
        private readonly UsersAccountServices $userAccountServices
    )
    {}

    public function ctrl_getUserAccounts(Request $request): JsonResponse
    {
        return $this->userAccountServices->srv_getAdminAccounts($request);
    }
    public function ctrl_getUserAccountFormData(): JsonResponse
    {
        return $this->userAccountServices->srv_getAdminAccountFormData();
    }

    public function ctrl_createUserAccount(Request $request): JsonResponse
    {

        return $this->userAccountServices->srv_createAdminAccount($request);
    }

    public function ctrl_updateUserAccount(Request $request, $slug): JsonResponse
    {

        return $this->userAccountServices->srv_updateAdminAccount($request, $slug);
    }

    public function ctrl_destroyUserAccount($slug): JsonResponse
    {
        return $this->userAccountServices->srv_deleteAdminAccount($slug);
    }
    public function ctrl_enableOrDisableUserAccount($slug): JsonResponse
    {
        return $this->userAccountServices->srv_enableOrDisableAdminAccount($slug);
    }
}
