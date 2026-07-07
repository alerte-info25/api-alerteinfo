<?php

namespace App\Http\Controllers\Auths;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AccountServices\UsersAccountServices;
use App\Services\JsonResponseServices\JsonResponseService;

class AuthentificatorController extends Controller
{


    public function __construct(
        private readonly UsersAccountServices $userAccountService,
        private readonly JsonResponseService $jsonResponseService
    ) {
    }
    // mets tous les messages en français
    public function ctrl_usersAuthentication(Request $request): JsonResponse
    {
        // implement logic to authenticate user with jwt token

        if (empty($request->email_address)) {
            return $this->jsonResponseService->srv_errorResponse(
                'Veuillez fournir une adresse email.',
                Response::HTTP_BAD_REQUEST,
            );
        }
        //check if email address is valid
        if (!filter_var($request->email_address, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponseService->srv_errorResponse(
                'Veuillez fournir une adresse email valide.',
                Response::HTTP_BAD_REQUEST,
            );
        }

        if (empty($request->user_password)) {
            return $this->jsonResponseService->srv_errorResponse(
                'Veuillez fournir un mot de passe.',
                Response::HTTP_BAD_REQUEST,
            );
        }

        // check if user account exists
        $checkUserAccount = $this->userAccountService->srv_checkUserAccount($request->email_address);
        //return $checkUserAccount;
        if ($checkUserAccount == null) {
            return $this->jsonResponseService->srv_errorResponse(
                'Cette adresse email ne correspond pas à un compte utilisateur.',
                Response::HTTP_NOT_FOUND,
            );
        }
        // check if password is correct
        if (!$this->userAccountService->srv_checkUserPassword($checkUserAccount, $request->user_password)) {
            return $this->jsonResponseService->srv_errorResponse(
                'Mot de passe incorrect.',
                Response::HTTP_BAD_REQUEST,
            );
        }
        // return try to connect user
        return $this->userAccountService->srv_tryToConnectUser($request);

    }


    // process update user account
    public function ctrl_processUpdateUserAccountPassword(Request $request): JsonResponse
    {
        if (empty($request->email_address)) {
            return $this->jsonResponseService->srv_errorResponse(
                'Veuillez fournir une adresse email.',
                Response::HTTP_BAD_REQUEST,
            );
        }
        //check if email address is valid
        if (!filter_var($request->email_address, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponseService->srv_errorResponse(
                'Veuillez fournir une adresse email valide.',
                Response::HTTP_BAD_REQUEST,
            );
        }

        // check if user account exists
        return $this->userAccountService->srv_processUpdateUserAccountPassword($request->email_address);
    }

    // verify the user otp code
    public function ctrl_verifyUserOTP(Request $request): JsonResponse
    {
        if (empty($request->email_address) || empty($request->otp_code)) {
            return $this->jsonResponseService->srv_errorResponse(
                'Veuillez fournir une adresse email et un code OTP.',
                Response::HTTP_BAD_REQUEST,
            );
        }
        //check if email address is valid
        if (!filter_var($request->email_address, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponseService->srv_errorResponse(
                'Veuillez fournir une adresse email valide.',
                Response::HTTP_BAD_REQUEST,
            );

        }

        return $this->userAccountService->srv_verifyUserAccountOTP($request);
    }

    // update user password
    // update user password
    public function ctrl_updateUserPassword(Request $request): JsonResponse
    {
        //return $request->all();
        if (empty($request->email_address) || empty($request->user_password)) {
            return $this->jsonResponseService->srv_errorResponse(
                'Veuillez fournir une adresse email et un nouveau mot de passe.',
                Response::HTTP_BAD_REQUEST,
            );
        }
        //check if email address is valid
        if (!filter_var($request->email_address, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponseService->srv_errorResponse(
                'Veuillez fournir une adresse email valide.',
                Response::HTTP_BAD_REQUEST,
            );
        }

        // update user password
        return $this->userAccountService->srv_updateUserAccountPassword($request);

    }

    // user logout
    public function ctrl_logout(Request $request): JsonResponse
    {
        return $this->userAccountService->srv_logoutUserAccount();
    }
}
