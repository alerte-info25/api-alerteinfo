<?php
namespace App\Services\AccountServices;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\UserAccounts\UserRoleModel;
use App\Models\UserAccounts\AdminAccountModel;
use Symfony\Component\HttpFoundation\Response;
use App\Services\MailSenderServices\MailSenderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;


class UsersAccountServices
{


    public function __construct(
        private readonly AdminAccountModel $__accountModel,
        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogError $customLogError,
        private readonly MailSenderService $mailSenderService,
        private readonly UploadFileManagerService $uploadFileManagerService,
        private readonly CodeGeneratorService $codeGeneratorService,
        private readonly UserRoleModel $userRoleModel,
    ) {}






    // check if user account exists in the database using email address
    /**
     * Summary of __checkUserAccount
     * @param mixed $emailAddress
     * @return AdminAccountModel|mixed
     */
    public function srv_checkUserAccount($emailAddress)
    {
        // check if admin account exists in the database
        $accountModel = $this->__accountModel->where('email', $emailAddress)->first();
        return $accountModel;
    }

    // check if user password is correct
    /**
     * Summary of __checkUserPassword
     * @param mixed $user
     * @param mixed $password
     * @return bool
     */
    public function srv_checkUserPassword($user, $password)
    {
        return password_verify($password, $user->password);
    }

    // try to logged in user
    public function srv_tryToConnectUser($request): JsonResponse
    {
        try {
            $__access_token = null;
            $__guard = null;

            $credentials = [
                'email' => $request->email_address,
                'password' => $request->user_password
            ];

            // Authentification
            if (auth('admin')->attempt($credentials)) {
                $__access_token = auth('admin')->attempt($credentials);
                $__guard = 'admin';
            }

            // Si aucun guard ne correspond, l'authentification a échoué
            if (!$__guard) {
                // Log de l'échec de la connexion
                $this->customLogError->logWarning('Échec de la connexion', ['email' => $request->email_address]);

                return $this->jsonResponseService->srv_errorResponse(
                    "Échec de la connexion", // Message générique
                    Response::HTTP_BAD_REQUEST, // Code HTTP d'erreur non autorisé
                );

            }

            // Récupération des informations utilisateur
            $userData = auth($__guard)->user();
            if (!$userData) {
                return $this->jsonResponseService->srv_errorResponse(
                    "Oups! utilisateur introuvable", // Message générique
                    Response::HTTP_NOT_FOUND, // Code HTTP d'erreur non autorisé
                );
            }

            // Vérification du compte utilisateur
            $accountModel = $this->srv_checkUserAccount($userData->email);
            if ($accountModel == null) {
                return $this->jsonResponseService->srv_errorResponse(
                    "Oups! utilisateur introuvable", // Message générique
                    Response::HTTP_NOT_FOUND, // Code HTTP d'erreur non autorisé
                );
            }

            // Récupération des informations de rôle et permissions
            $roleName = $accountModel->role?->role_name; // Nom du rôle
            Log::info($roleName);



            // Mise à jour des informations de connexion
            $accountModel->connected = 1;
            $accountModel->last_login_at = Carbon::now()->format('Y-m-d H:i:s');
            $accountModel->save();


            // Log de la connexion réussie
            $this->customLogError->logInfo('Connexion réussie', [
                'email' => $userData->email,
                'guard' => $__guard
            ]);


            // renvoi des données de l'utilisateur
            return $this->jsonResponseService->srv_successResponseWithData(
                "Connexion réussie",
                [
                    'access_token' => $__access_token,
                    'userData' => [
                            'first_name' => $accountModel->first_name,
                            'last_name' => $accountModel->last_name,
                            'email' => $accountModel->email,
                            'photo' => $accountModel->photo_url,
                            'connected' => $accountModel->connected,
                            'role' => $roleName,
                        ],
                    'guard' => $__guard
                ],
                Response::HTTP_OK // Code HTTP de succès
            );
        } catch (\Throwable $th) {
            // Log the error message
            $this->customLogError->logError("Erreur lors de la tentative de connexion: {$request->email_address}", $th);
            return $this->jsonResponseService->srv_errorResponse(
                "Erreur lors de la tentative de connexion", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }



    // process update user account


    public function srv_processUpdateUserAccountPassword($emailAddress)
    {
        try {
            // check user account
            $accountModel = $this->srv_checkUserAccount($emailAddress);

            if ($accountModel == null) {
                return $this->jsonResponseService->srv_errorResponse(
                    "Oups! utilisateur introuvable", // Message générique
                    Response::HTTP_NOT_FOUND, // Code HTTP d'erreur non autorisé
                );
            }
            $lenght = 6;
            $keys = substr(str_shuffle(
                str_repeat($x = '1234567890', ceil($lenght / strlen($x)))
            ), 3, $lenght);


            $two_factor_secret = $keys;
            $current_date = Carbon::now()->addMinutes(5);
            $two_factor_code_sent_at = $current_date->format('Y-m-d H:i:s');

            $is_updated = $accountModel->update([
                'two_factor_secret' => $two_factor_secret,
                'two_factor_code_sent_at' => $two_factor_code_sent_at,
            ]);

            if ($is_updated) {
                $mailIsSending = $this->mailSenderService->srv_sendOTPForPasswordUpdate($accountModel, $two_factor_secret);
                if ($mailIsSending == true) {
                    // Log de l'envoi de l'OTP
                    $this->customLogError->logInfo('OTP envoyé avec succès', [
                        'email' => $accountModel->email,
                        'otp' => $two_factor_secret
                    ]);

                    return $this->jsonResponseService->srv_successResponseWithData(
                        "OTP envoyé avec succès",
                        [
                            'email' => $accountModel->email,
                            'first_name' => $accountModel->first_name,
                            'last_name' => $accountModel->last_name,
                        ],
                        Response::HTTP_OK // Code HTTP de succès
                    );
                } else {
                    // Log de l'échec de l'envoi de l'OTP
                    $this->customLogError->logWarning('Échec de l\'envoi de l\'OTP', [
                        'email' => $accountModel->email,
                        'otp' => $two_factor_secret
                    ]);

                    return $this->jsonResponseService->srv_errorResponse(
                        "Erreur lors de l'envoi de l'OTP", // Message générique
                        Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
                    );
                }

            } else {
                return $this->jsonResponseService->srv_errorResponse(
                    "Erreur lors de l'envoi de l'OTP", // Message générique
                    Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
                );
            }

        } catch (\Throwable $th) {
            // Log the error message$th->getMessage;
            $this->customLogError->logError("Erreur lors de la mise à jour du mot de passe : {$emailAddress}", $th);

            return $this->jsonResponseService->srv_errorResponse(
                "Erreur lors de la mise à jour du mot de passe", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    // verify user account OTP
    /**
     * Summary of srv_verifyUserAccountOTP
     * @param mixed $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function srv_verifyUserAccountOTP(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            //code...
            $accountModel = $this->srv_checkUserAccount($request->email_address);

            if ($accountModel == null) {
                return $this->jsonResponseService->srv_errorResponse(
                    "Oups! utilisateur introuvable", // Message générique
                    Response::HTTP_NOT_FOUND, // Code HTTP d'erreur non autorisé
                );
            }
            $current_date = Carbon::now();
            $two_factor_code_sent_at = Carbon::parse($accountModel->two_factor_code_sent_at);

            if ($two_factor_code_sent_at->copy()->addMinutes(5)->lessThan($current_date)) {

                $this->customLogError->logInfo('OTP expiré ', ['OTP' => $accountModel->two_factor_secret]);

                return $this->jsonResponseService->srv_errorResponse(
                    "Le code OTP a expiré", // Message générique
                    Response::HTTP_BAD_REQUEST, // Code HTTP d'erreur non autorisé
                );
            }

            if ($accountModel->two_factor_secret == $request->otp_code) {
                Log::info('OTP vérifié ', ['OTP' => $accountModel->two_factor_secret]);
                $accountModel->update(
                    [
                        'two_factor_secret' => null,
                        'two_factor_code_sent_at' => null,
                    ]
                );
                DB::commit();
                // Log de la vérification réussie
                $this->customLogError->logInfo('OTP vérifié avec succès', [
                    'email' => $accountModel->email,
                    'otp' => $request->otp_code
                ]);
                return $this->jsonResponseService->srv_successResponseWithData(
                    "OTP vérifié avec succès",
                    [
                        'email' => $accountModel->email,
                        'first_name' => $accountModel->first_name,
                        'last_name' => $accountModel->last_name,
                    ],
                    Response::HTTP_OK // Code HTTP de succès
                );
            } else {
                // Log de l'échec de la vérification
                $this->customLogError->logWarning('Échec de la vérification de l\'OTP', [
                    'email' => $accountModel->email,
                    'otp' => $request->otp_code
                ]);
                return $this->jsonResponseService->srv_errorResponse(
                    "Le code OTP est incorrect", // Message générique
                    Response::HTTP_BAD_REQUEST, // Code HTTP d'erreur non autorisé
                );
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            //log error
            $this->customLogError->logError('Erreur lors de la vérification de l\'OTP', $th);
            //
            return $this->jsonResponseService->srv_errorResponse(
                "Erreur lors de la vérification de l'OTP", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );

        }
    }

    // resent user otp
    public function srv_resentUserAccountOTP($emailAddress): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Check if the user account exists
            $accountModel = $this->srv_checkUserAccount($emailAddress);

            if ($accountModel == null) {
                return $this->jsonResponseService->srv_errorResponse(
                    "Oups! utilisateur introuvable", // Message générique
                    Response::HTTP_NOT_FOUND, // Code HTTP d'erreur non autorisé
                );
            }

            // Generate a 6-digit OTP
            $length = 6;
            $keys = substr(str_shuffle(
                str_repeat($x = '1234567890', ceil($length / strlen($x)))
            ), 3, $length);

            $two_factor_secret = $keys;
            $current_date = Carbon::now()->addMinutes(5);
            $two_factor_code_sent_at = $current_date->format('Y-m-d H:i:s');

            // Update the user account with the new OTP
            $is_updated = self::$__accountModel->update([
                'two_factor_secret' => $two_factor_secret,
                'two_factor_code_sent_at' => $two_factor_code_sent_at,
            ]);
            DB::commit();

            if (!$is_updated) {
                DB::rollBack();
                // Log de l'échec de la mise à jour
                $this->customLogError->logWarning('Échec de la mise à jour de l\'OTP', [
                    'email' => $accountModel->email,
                ]);
                return $this->jsonResponseService->srv_errorResponse(
                    "Erreur lors de la mise à jour de l'OTP", // Message générique
                    Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
                );
            }



            // Log the successful update
            $this->customLogError->logInfo('OTP mis à jour avec succès', [
                'email' => $accountModel->email,
                'otp' => $two_factor_secret
            ]);

            // Send the OTP via email
            $mailIsSending = $this->mailSenderService->srv_sendOTPForPasswordUpdate($accountModel, $two_factor_secret);

            if (!$mailIsSending) {
                // Log de l'échec de l'envoi de l'OTP
                $this->customLogError->logWarning('Échec de l\'envoi de l\'OTP', [
                    'email' => $accountModel->email,
                    'otp' => $two_factor_secret
                ]);
                return $this->jsonResponseService->srv_errorResponse(
                    "Erreur lors de l'envoi de l'OTP", // Message générique
                    Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
                );
            }

            // Log the successful email sending
            $this->customLogError->logInfo('OTP envoyé avec succès', [
                'email' => $accountModel->email,
                'otp' => $two_factor_secret
            ]);

            // Return success response
            return $this->jsonResponseService->srv_successResponseWithData(
                "OTP envoyé avec succès",
                [
                    'email' => $accountModel->email,
                    'first_name' => $accountModel->first_name,
                    'last_name' => $accountModel->last_name,
                ],
                Response::HTTP_OK // Code HTTP de succès
            );

        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error message
            $this->customLogError->logError("Erreur lors de la mise à jour de l'OTP : {$emailAddress}", $e);
            // Return an error response
            return $this->jsonResponseService->srv_errorResponse(
                "Erreur lors de la mise à jour de l'OTP", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    // update user account password
    /**
     * Summary of srv_updateUserAccountPassword
     * @param mixed $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function srv_updateUserAccountPassword($request): JsonResponse
    {
        try {
            // Check if the user account exists
            $accountModel = $this->srv_checkUserAccount($request->email_address);

            if ($accountModel == null) {
                return $this->jsonResponseService->srv_errorResponse(
                    "Oups! utilisateur introuvable", // Message générique
                    Response::HTTP_NOT_FOUND, // Code HTTP d'erreur non autorisé
                );
            }

            // Hash the new password
            $newPassword = $request->user_password;

            $is_updated = $accountModel->update(
                [
                    'password' => $this->codeGeneratorService::generatePasswordHash($newPassword),
                ]
            );

            if (!$is_updated) {
                // Log de l'échec de la mise à jour
                $this->customLogError->logWarning('Échec de la mise à jour du mot de passe', [
                    'email' => $accountModel->email,
                ]);
                return $this->jsonResponseService->srv_errorResponse(
                    "Erreur lors de la mise à jour du mot de passe", // Message générique
                    Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
                );
            }

            // Log de la mise à jour réussie
            $this->customLogError->logInfo('Mot de passe mis à jour avec succès', [
                'email' => $accountModel->email,
            ]);

            // Send the password update notification email
            $mailIsSending = $this->mailSenderService::srv_sendPasswordUpdateNotification($accountModel, $newPassword);
            if (!$mailIsSending) {
                // Log de l'échec de l'envoi de l'email
                $this->customLogError->logWarning('Échec de l\'envoi de l\'email de notification', [
                    'email' => $accountModel->email,
                ]);
                return $this->jsonResponseService->srv_errorResponse(
                    "Erreur lors de l'envoi de l'email de notification", // Message générique
                    Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
                );
            }

            // Log de l'envoi de l'email
            $this->customLogError->logInfo('Email de notification envoyé avec succès', [
                'email' => $accountModel->email,
            ]);

            // Return success response
            return $this->jsonResponseService->srv_successResponseWithData(
                "Mot de passe mis à jour avec succès et notification envoyée",
                [
                    'email' => $accountModel->email,
                    'first_name' => $accountModel->first_name,
                    'last_name' => $accountModel->last_name,
                ],
                Response::HTTP_OK // Code HTTP de succès
            );

        } catch (\Exception $e) {
            // Log de l'erreur
            $this->customLogError->logError('Erreur lors de la mise à jour du mot de passe', $e);

            // Return an error response
            return $this->jsonResponseService->srv_errorResponse(
                "Erreur lors de la mise à jour du mot de passe", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }


    // logout the account
    /**
     * Summary of __logoutUserAccount
     * @return \Illuminate\Http\JsonResponse
     */
    public function srv_logoutUserAccount(): JsonResponse
    {
        try {
            $userEmail = null;
            $guard = null;

            // Vérifier quel type d'utilisateur est connecté
            if (auth('admin')->check()) {
                $userEmail = auth('admin')->user()->email;
                auth('admin')->logout();
                $guard = 'admin';
            }

            if ($userEmail && $guard) {
                // Récupérer le modèle utilisateur
                $accountModel = $this->srv_checkUserAccount($userEmail);

                if ($accountModel) {
                    // Mettre à jour les informations de connexion
                    $accountModel->update([
                        'connected' => 0,
                        'last_logout_at' => now()->format('Y-m-d H:i:s'),
                    ]);

                    // Log de la déconnexion réussie
                    $this->customLogError->logInfo('Déconnexion réussie', [
                        'email' => $userEmail,
                        'guard' => $guard
                    ]);
                    return $this->jsonResponseService->srv_successResponse(
                        "Déconnexion réussie",
                        Response::HTTP_OK // Code HTTP de succès
                    );
                } else {
                    // Log de l'échec de la mise à jour du statut de connexion
                    $this->customLogError->logWarning('Échec de la mise à jour du statut de connexion', [
                        'email' => $userEmail,
                        'guard' => $guard
                    ]);
                    return $this->jsonResponseService->srv_errorResponse(
                        "Échec de la déconnexion", // Message générique
                        Response::HTTP_BAD_REQUEST, // Code HTTP d'erreur non autorisé
                    );
                }
            } else {
                return $this->jsonResponseService->srv_errorResponse(
                    "Échec de la déconnexion", // Message générique
                    Response::HTTP_BAD_REQUEST, // Code HTTP d'erreur non autorisé
                );
            }
        } catch (\Throwable $th) {
            // Log de l'erreur
            $this->customLogError->logError('Erreur lors de la déconnexion', $th);
            // Retourner une réponse d'erreur
            return $this->jsonResponseService->srv_errorResponse(
                "Erreur lors de la déconnexion", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }


    // *********************************** ADMIN ACCOUNT MANAGEMENT ********************************

    // get all admin accounts list

    public function srv_getAdminAccounts(): JsonResponse
    {
        try {
            // Récupération des administrateurs avec leur rôle et groupe
            $administrators = $this->__accountModel
            ->with('role')// Jointure avec la table des rôles
            ->get()
            ->map(function ($administrator) {
                return [
                    'account_code_unique' => $administrator->account_code_unique,
                    'role_code_unique' => $administrator->role_code_unique,
                    'first_name' => $administrator->first_name,
                    'last_name' => $administrator->last_name,
                    'email' => $administrator->email,
                    'phone' => $administrator->phone,
                    'created_at' => $administrator->created_at,
                    'connected' => $administrator->connected,
                    'last_login_at' => $administrator->last_login_at,
                    'last_logout_at' => $administrator->last_logout_at,
                    'status' => $administrator->status,
                    'slug' => $administrator->slug,
                    'photo' => $administrator->photo_url,
                    'role' => $administrator->role->role_name,
                ];
            });

            return $this->jsonResponseService->srv_successResponseWithData(
                "Liste des administrateurs récupérée avec succès",
                [
                    'administrationAccounts' => $administrators,
                ],
                Response::HTTP_OK // Code HTTP de succès
            );

        } catch (\Throwable $th) {
            // Log de l'erreur
            $this->customLogError->logError('Erreur lors de la récupération des administrateurs', $th);
            // Retourner une réponse d'erreur
            return $this->jsonResponseService->srv_errorResponse(
                "Erreur lors de la récupération des administrateurs", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    public function srv_getAdminAccountFormData(): JsonResponse
    {
        try {
            $roles = $this->userRoleModel->all();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Liste des rôles récupérée avec succès",
                [
                    'roles' => $roles,
                ],
                Response::HTTP_OK // Code HTTP de succès
            );
        } catch (\Throwable $th) {
            // Log de l'erreur
            $this->customLogError->logError('Erreur lors de la récupération des rôles', $th);
            // Retourner une réponse d'erreur
            return $this->jsonResponseService->srv_errorResponse(
                "Erreur lors de la récupération des rôles", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }
    // create new admin account

    public function srv_createAdminAccount($requestData): JsonResponse
    {
        DB::beginTransaction();
        try {

            $adminPhotoPath = Null;
            if ($requestData->hasFile('photo')) {
                $adminPhotoPath = $this->uploadFileManagerService::uploadDefaultFile(
                    $requestData,
                    'photo',
                    ['jpg', 'jpeg', 'png'],
                    'ADMINS',
                    'PHOTOS',
                    $requestData->first_name . '' . $requestData->last_name
                );
                // Vérifier les erreurs lors de l'upload
                $catchError = $this->uploadFileManagerService->handleFileUploadError($adminPhotoPath, 'jpg, jpeg, png');
                if ($catchError != null) {
                    return $this->jsonResponseService->srv_errorResponse(
                        "Erreur : " . $catchError, // Message générique
                        Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
                    );
                }
            }
            $password = $this->codeGeneratorService::generatePassword();

            $accountCodeUnique = $this->codeGeneratorService::generateDefaultCodeUnique(
                'admin_account_models',
                'account_code_unique',
                'USR'
            );

            $adminAccount = $this->__accountModel->create([
                'account_code_unique' => $accountCodeUnique, // generate unique account code
                'role_code_unique' => $requestData->role_code_unique,
                'first_name' => $requestData->first_name,
                'last_name' => $requestData->last_name,
                'phone' => $requestData->phone,
                'photo' => $adminPhotoPath ?? '',
                'email' => $requestData->email_address,
                'password' => $this->codeGeneratorService::generatePasswordHash($password),
                'slug' => Str::uuid(),
            ]);

            if(!$adminAccount){
                DB::rollBack();
                // Log de l'échec de la création
                $this->customLogError->logWarning('Échec de la création du compte administrateur', [
                    'email' => $requestData->email_address,
                ]);
                return $this->jsonResponseService->srv_errorResponse(
                    "Erreur lors de la création du compte administrateur", // Message générique
                    Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
                );
            }

            // Log de la création réussie
            $this->customLogError->logInfo('Création réussie du compte administrateur', [
                'email' => $requestData->email_address,
                'password' => $password
            ]);

            $mailIsSending = $this->mailSenderService->srv_sendAccountCreationNotification($adminAccount, $password);
            if (!$mailIsSending) {
                DB::rollBack();
                // Log de l'échec de l'envoi de l'email
                $this->customLogError->logWarning('Échec de l\'envoi de l\'email de notification', [
                    'email' => $adminAccount->email,
                ]);
                return $this->jsonResponseService->srv_errorResponse(
                    "Erreur lors de l'envoi de l'email de notification", // Message générique
                    Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
                );
            }
            // Log de l'envoi de l'email
            $this->customLogError->logInfo('Email de notification envoyé avec succès', [
                'email' => $adminAccount->email,
            ]);
            DB::commit();

            return $this->jsonResponseService->srv_successResponseWithData(
                "Compte administrateur créé avec succès et notification envoyée",
                [
                    'email' => $adminAccount->email,
                    'first_name' => $adminAccount->first_name,
                    'last_name' => $adminAccount->last_name,
                ],
                Response::HTTP_CREATED // Code HTTP de succès
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            // Log de l'erreur
            $this->customLogError->logError('Erreur lors de la création du compte administrateur', $e);
            // Retourner une réponse d'erreur
            return $this->jsonResponseService->srv_errorResponse(
                "Erreur lors de la création du compte administrateur", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }

    }

    // update admin account
    /**
     * Summary of srv_updateAdminAccount
     * @param mixed $requestData
     * @param mixed $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function srv_updateAdminAccount($requestData, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $adminAccount = $this->__accountModel->where('slug', $slug)->firstOrFail();

            $oldAdminPhooPath = $adminAccount->photo;

            $newAdminPhooPath = null;

            if ($requestData->hasFile('photo')) {
                $newAdminPhooPath = $this->uploadFileManagerService::uploadDefaultFile(
                    $requestData,
                    'photo',
                    ['jpg', 'jpeg', 'png'],
                    'ADMINS',
                    'PHOTOS',
                    $adminAccount->first_name . '' . $adminAccount->last_name
                );
                // Vérifier les erreurs lors de l'upload
                $catchError = $this->uploadFileManagerService->handleFileUploadError($newAdminPhooPath, 'jpg, jpeg, png');
                if ($catchError != null) {
                    return $this->jsonResponseService->srv_errorResponse(
                        "Erreur : " . $catchError, // Message générique
                        Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
                    );
                }
                // Supprimer l'ancien document s'il existe
                if ($oldAdminPhooPath && Storage::disk('public')->exists($oldAdminPhooPath)) {
                    Storage::disk('public')->delete($oldAdminPhooPath);
                }
            }

            $adminAccount->update([
                'role_code_unique' => $requestData->role_code_unique,
                'first_name' => $requestData->first_name,
                'last_name' => $requestData->last_name,
                'email' => $requestData->email_address,
                'phone' => $requestData->phone,
                'photo' => $newAdminPhooPath ?? $oldAdminPhooPath,
            ]);

            DB::commit();
            // Log de la mise à jour réussie
            $this->customLogError->logInfo('Mise à jour réussie du compte administrateur', [
                'email' => $adminAccount->email,
            ]);

            return $this->jsonResponseService->srv_successResponseWithData(
                "Compte administrateur mis à jour avec succès",
                [
                    'email' => $adminAccount->email,
                    'first_name' => $adminAccount->first_name,
                    'last_name' => $adminAccount->last_name,
                ],
                Response::HTTP_OK // Code HTTP de succès
            );

        } catch (ModelNotFoundException $m) {
            DB::rollBack();
            // Log de l'erreur
            $this->customLogError->logWarning('Compte administrateur introuvable', [
                'slug' => $slug,
            ]);
            return $this->jsonResponseService->srv_errorResponse(
                "Compte administrateur introuvable", // Message générique
                Response::HTTP_NOT_FOUND, // Code HTTP d'erreur non autorisé
            );
        } catch (\Exception $e) {
            DB::rollBack();
            // Log de l'erreur
            $this->customLogError->logError('Erreur lors de la mise à jour du compte administrateur', $e);
            // Retourner une réponse d'erreur
            return $this->jsonResponseService->srv_errorResponse(
                "Erreur lors de la mise à jour du compte administrateur", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }
    // delete admin account
    /**
     * Summary of srv_deleteAdminAccount
     * @param mixed $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function srv_deleteAdminAccount($slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $AdminAccount = $this->__accountModel->where('slug', $slug)->firstOrFail();

            if ($AdminAccount->delete()) {
                DB::commit();
                // Log de la suppression réussie
                $this->customLogError->logInfo('Suppression réussie du compte administrateur', [
                    'email' => $AdminAccount->email,
                ]);
                return $this->jsonResponseService->srv_successResponse(
                    "Compte administrateur supprimé avec succès",
                    Response::HTTP_OK // Code HTTP de succès
                );
            } else {
                DB::rollBack();
                return $this->jsonResponseService->srv_errorResponse(
                    "Erreur lors de la suppression du compte administrateur", // Message générique
                    Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
                );
            }
        } catch (ModelNotFoundException $m) {
            DB::rollBack();
            // Log de l'erreur
            $this->customLogError->logWarning('Compte administrateur introuvable', [
                'slug' => $slug,
            ]);
            return $this->jsonResponseService->srv_errorResponse(
                "Compte administrateur introuvable", // Message générique
                Response::HTTP_NOT_FOUND, // Code HTTP d'erreur non autorisé
            );
        } catch (\Exception $e) {
            DB::rollBack();
            // Log de l'erreur
            $this->customLogError->logError('Erreur lors de la suppression du compte administrateur', $e);
            // Retourner une réponse d'erreur
            return $this->jsonResponseService->srv_errorResponse(
                "Erreur lors de la suppression du compte administrateur", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    // __enable Or Disable Admin Account
    /**
     * Summary of __enableOrDisableAdminAccount
     * @param mixed $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function srv_enableOrDisableAdminAccount($slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            // if status == 1 , update it to 0 or if status == 0 , update it to 1
            $adminAccount = $this->__accountModel::where('slug', $slug)->firstOrFail();
            $adminAccount->status = $adminAccount->status == 'Actif' ? 'Inactif' : 'Actif';
            if (!$adminAccount->save()) {
                DB::rollBack();
                return $this->jsonResponseService->srv_errorResponse(
                    "Erreur lors de la mise à jour du statut du compte administrateur", // Message générique
                    Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
                );
            }
            DB::commit();
            // Log de la mise à jour réussie
            $this->customLogError->logInfo('Mise à jour réussie du statut du compte administrateur', [
                'email' => $adminAccount->email,
            ]);
            return $this->jsonResponseService->srv_successResponseWithData(
                "Statut du compte administrateur mis à jour avec succès",
                [
                    'email' => $adminAccount->email,
                    'first_name' => $adminAccount->first_name,
                    'last_name' => $adminAccount->last_name,
                    'status' => $adminAccount->status,
                ],
                Response::HTTP_OK // Code HTTP de succès
            );
        } catch (ModelNotFoundException $m) {
            DB::rollBack();
            // Log de l'erreur
            $this->customLogError->logWarning('Compte administrateur introuvable', [
                'slug' => $slug,
            ]);
            return $this->jsonResponseService->srv_errorResponse(
                "Compte administrateur introuvable", // Message générique
                Response::HTTP_NOT_FOUND, // Code HTTP d'erreur non autorisé
            );

        } catch (\Exception $e) {
            DB::rollBack();
            // Log de l'erreur
            $this->customLogError->logError('Erreur lors de la mise à jour du statut du compte administrateur', $e);
            // Retourner une réponse d'erreur
            return $this->jsonResponseService->srv_errorResponse(
                "Erreur lors de la mise à jour du statut du compte administrateur", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }

    }

    // update admin account photo by slug

    /**
     * Summary of srv_updateAdminAccountPhoto
     * @param mixed $requestData
     * @param mixed $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function srv_updateAdminAccountPhoto($requestData, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {

            $adminAccount = $this->__accountModel::where('slug', $slug)->firstOrFail();

            $oldAdminPhooPath = $adminAccount->photo;

            if ($requestData->hasFile('photo')) {
                $newAdminPhooPath = $this->uploadFileManagerService::uploadDefaultFile(
                    $requestData,
                    'photo',
                    ['jpg', 'jpeg', 'png'],
                    'ADMINS',
                    'PHOTOS',
                    $adminAccount->first_name . '' . $adminAccount->last_name
                );
                // Vérifier les erreurs lors de l'upload
                $catchError = $this->uploadFileManagerService->handleFileUploadError($newAdminPhooPath, 'jpg, jpeg, png');
                if ($catchError != null) {
                    return $this->jsonResponseService->srv_errorResponse(
                        "Erreur : " . $catchError, // Message générique
                        Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
                    );
                }
                // Supprimer l'ancien document s'il existe
                if ($oldAdminPhooPath && Storage::disk('public')->exists($oldAdminPhooPath)) {
                    Storage::disk('public')->delete($oldAdminPhooPath);
                }
            }

            $adminAccount->photo = $newAdminPhooPath ?? $oldAdminPhooPath;
            if (!$adminAccount->save()) {
                DB::rollBack();
                return $this->jsonResponseService->srv_errorResponse(
                    "Erreur lors de la mise à jour de la photo du compte administrateur", // Message générique
                    Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
                );
            }

            DB::commit();
            // Log de la mise à jour réussie
            $this->customLogError->logInfo('Mise à jour réussie de la photo du compte administrateur', [
                'email' => $adminAccount->email,
            ]);
            return $this->jsonResponseService->srv_successResponseWithData(
                "Photo du compte administrateur mise à jour avec succès",
                [
                    'email' => $adminAccount->email,
                    'first_name' => $adminAccount->first_name,
                    'last_name' => $adminAccount->last_name,
                    'photo' => $adminAccount->photo,
                ],
                Response::HTTP_OK // Code HTTP de succès
            );
        } catch (ModelNotFoundException $m) {
            DB::rollBack();
            // Log de l'erreur
            $this->customLogError->logWarning('Compte administrateur introuvable', [
                'slug' => $slug,
            ]);
            return $this->jsonResponseService->srv_errorResponse(
                "Compte administrateur introuvable", // Message générique
                Response::HTTP_NOT_FOUND, // Code HTTP d'erreur non autorisé
            );
        } catch (\Exception $e) {
            DB::rollBack();
            // Log de l'erreur
            $this->customLogError->logError('Erreur lors de la mise à jour de la photo du compte administrateur', $e);
            // Retourner une réponse d'erreur
            return $this->jsonResponseService->srv_errorResponse(
                "Erreur lors de la mise à jour de la photo du compte administrateur", // Message générique
                Response::HTTP_INTERNAL_SERVER_ERROR, // Code HTTP d'erreur interne
            );
        }
    }

    // *********************************** END ADMIN ACCOUNT MANAGEMENT ********************************


    // *********************************** START USER ROLE MANAGEMENT ********************************

    public function srv_getUserRole(): JsonResponse
    {
        try {
            $userRoles = $this->userRoleModel->get();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Rôles récupérés avec succès",
                $userRoles,
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la récupération des rôles",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la récupération des rôles",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createUserRole(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $userRole = $this->userRoleModel->create([
                'role' => $request->role,
            ]);
            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Rôle créé avec succès",
                $userRole,
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la création du rôle",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la création du rôle",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateUserRole(Request $request, string $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $userRole = $this->userRoleModel->where('slug', $slug)->update([
                'role' => $request->role,
            ]);
            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Rôle mis à jour avec succès",
                $userRole,
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la mise à jour du rôle",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la mise à jour du rôle",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteUserRole(string $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            if(empty($slug)){
                return $this->jsonResponseService->srv_errorResponse(
                    "Le slug est obligatoire",
                    Response::HTTP_BAD_REQUEST
                );
            }
            $userRole = $this->userRoleModel->where('slug', $slug)->delete();
            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Rôle supprimé avec succès",
                $userRole,
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError(
                "Une erreur est survenue lors de la suppression du rôle",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la suppression du rôle",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // *********************************** END USER ROLE MANAGEMENT ********************************



}
