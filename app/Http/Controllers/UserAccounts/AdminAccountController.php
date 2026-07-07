<?php

namespace App\Http\Controllers\UserAccounts;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\AccountServices\UsersAccountServices;

class AdminAccountController extends Controller
{
    public function __construct(
        private readonly UsersAccountServices $usersAccountService
    )
    {
    }

    public function ctrl_getAdminAccount(): JsonResponse
    {
        return $this->usersAccountService->srv_getAdminAccounts();
    }




    public function ctrl_createAdminAccount(Request $request): JsonResponse
    {
        return $this->usersAccountService->srv_createAdminAccount($request);
    }

    public function ctrl_updateAdminAccount(Request $request, string $slug): JsonResponse
    {
        return $this->usersAccountService->srv_updateAdminAccount($request, $slug);
    }

    public function ctrl_deleteAdminAccount(string $slug): JsonResponse
    {
        return $this->usersAccountService->srv_deleteAdminAccount($slug);
    }

    // enable Or Disable Admin Account
    public function ctrl_enableOrDisableAdminAccount(string $slug): JsonResponse
    {
        return $this->usersAccountService->srv_enableOrDisableAdminAccount($slug);
    }



    public function ctrl_getUserRole(): JsonResponse
    {
        return $this->usersAccountService->srv_getUserRole();
    }

    public function ctrl_createUserRole(Request $request): JsonResponse
    {
        return $this->usersAccountService->srv_createUserRole($request);
    }

    public function ctrl_updateUserRole(Request $request, string $slug): JsonResponse
    {
        return $this->usersAccountService->srv_updateUserRole($request, $slug);
    }

    public function ctrl_deleteUserRole(string $slug): JsonResponse
    {
        return $this->usersAccountService->srv_deleteUserRole($slug);
    }




    // logout admin account
    public function ctrl_logoutAdminAccount(): JsonResponse
    {
        return $this->usersAccountService->srv_logoutUserAccount();
    }
}
