<?php

namespace App\Services\LicenceServices;

use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\CinetPay\CinetPay;
use App\Services\CinetPay\Marchand;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\LicenceModels\LicenceModel;
use App\Models\FonctionModels\FonctionModel;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LicenceServices\LicenceDocumentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\DocumentsRequisModels\DocumentsRequisModel;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;
use App\Models\CategorieGeneraleModels\CategorieGeneraleModel;

class LicenceService
{
    public function __construct(
        // Licence Model
        private readonly LicenceModel $licenceModel,
        // Custom Error
        private readonly CustomLogError $customLogError,
        // Response Service
        private readonly JsonResponseService $jsonResponseService,
        // Code Generator
        private readonly CodeGeneratorService $codeGeneratorService,
        // Licence Document Service
        private readonly LicenceDocumentService $licenceDocumentService,
        // Document Requis Models
        private readonly DocumentsRequisModel $documentsRequisModel,
        // Categorie Models
        private readonly CategorieGeneraleModel $categorieModel,
        // Fonction Models
        private readonly FonctionModel $fonctionModel,
    ) {
    }



    public function srv_getNouvelleLicenceList(Request $request): JsonResponse
    {
        try {

            // pagination parameters
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 25);

            // get licence list
            $licenceList = $this->licenceModel
            ->with('categorie', 'fonction','tranche','club','ligue','federation')
            ->where('operation_type', 'new')
            ->paginate($perPage, ['*'], 'page', $page);

                $licenceDataFormated = $licenceList->getCollection()->map(function ($licence) {
                    return [
                        'id' => $licence->id,
                        'licence_code_unique' => $licence->licence_code_unique,
                        'categorie_code_unique' => $licence->categorie_code_unique,
                        'fonction_code_unique' => $licence->fonction_code_unique,
                        'categorie' => $licence->categorie->categorie_name ?? null,
                        'fonction' => $licence->fonction->fonction_name ?? null,
                        'tranche' => $licence->tranche->tranche_name ?? null,
                        'num_licence' => $licence->num_licence ?? null,
                        'first_name' => $licence->first_name ?? null,
                        'last_name' => $licence->last_name ?? null,

                        'telephone' => $licence->telephone ?? null,
                        'date_naissance' => $licence->date_naissance ?? null,
                        'lieu_naissance' => $licence->lieu_naissance ?? null,
                        'genre' => $licence->genre ?? null,
                        'montant' => $licence->categorie->categorie_montant ?? null,

                        'nationalite' => $licence->nationalite,

                        'club_name' => $licence->club->club_name ?? null,
                        'ligue_name' => $licence->ligue->ligue_name ?? null,
                        'federation_name' => $licence->federation->federation_name ?? null,

                        'payment' => $licence->payment,
                        'operation_type' => $licence->operation_type,
                        'request_date' => $licence->request_date,
                        'slug' => $licence->slug,
                    ];
                });

            
            

            return $this->jsonResponseService->successResponseWithData(
                "Liste des licences récupérée avec succès",
                [
                    'nouvelleLicenceList' => $licenceDataFormated,
                    'paginations' => [
                        'total' => $licenceList->total(),
                        'per_page' => $licenceList->perPage(),
                        'current_page' => $licenceList->currentPage(),
                        'last_page' => $licenceList->lastPage(),
                    ],
                ],
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->customLogError->logError(
                'Erreur lors de la récupération de la liste des licences',
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération de la liste des licences',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    public function srv_getRenouvellementLicenceList(Request $request): JsonResponse
    {
        try {
            // pagination parameters
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 25);

            // get licence list
            $licenceList = $this->licenceModel

            ->with(['categorie', 'fonction','tranche','club','ligue','federation'])
            ->where('operation_type', 'renewal')
            ->paginate($perPage, ['*'], 'page', $page);

            $licenceDataFormated = $licenceList->getCollection()->map(function ($licence) {
                return [
                    'id' => $licence->id,
                    'licence_code_unique' => $licence->licence_code_unique,
                    'categorie_code_unique' => $licence->categorie_code_unique,
                    'fonction_code_unique' => $licence->fonction_code_unique,
                    'categorie' => $licence->categorie->categorie_name ?? null,
                    'fonction' => $licence->fonction->fonction_name ?? null,
                    'tranche' => $licence->tranche->tranche_name ?? null,
                    'num_licence' => $licence->num_licence ?? null,
                    'first_name' => $licence->first_name ?? null,
                    'last_name' => $licence->last_name ?? null,

                    'telephone' => $licence->telephone ?? null,
                    'date_naissance' => $licence->date_naissance ?? null,
                    'lieu_naissance' => $licence->lieu_naissance ?? null,
                    'genre' => $licence->genre ?? null,
                    'montant' => $licence->categorie->categorie_montant ?? null,

                    'nationalite' => $licence->nationalite ?? null,

                    'club_name' => $licence->club->club_name ?? null,
                    'ligue_name' => $licence->ligue->ligue_name ?? null,
                    'federation_name' => $licence->federation->federation_name ?? null,

                    'payment' => $licence->payment,
                    'operation_type' => $licence->operation_type,
                    'request_date' => $licence->request_date,
                    'slug' => $licence->slug,
                ];
            });

            return $this->jsonResponseService->successResponseWithData(
                "Liste des licences récupérée avec succès",
                [
                    'renouvellementLicenceList' => $licenceDataFormated,
                    'paginations' => [
                        'total' => $licenceList->total(),
                        'per_page' => $licenceList->perPage(),
                        'current_page' => $licenceList->currentPage(),
                        'last_page' => $licenceList->lastPage(),
                    ],
                ],
                Response::HTTP_OK
            );

        } catch (\Throwable $th) {
            $this->customLogError->logError(
                'Erreur lors de la récupération de la liste des licences',
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération de la liste des licences',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    public function srv_checkOrganisationCodeValidity($organisationCodeUnique, $categoryCodeUnique)
    {
        try {
            $organisation = $this->licenceModel->checkOrganisationCodeValidity($organisationCodeUnique,$categoryCodeUnique);
            return $this->jsonResponseService->successResponse(
                $organisation,
                'Organisation récupérée avec succès'
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                'Erreur lors de la récupération de l\'organisation',
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération de l\'organisation',
                $th->getMessage()
            );
        }
    }


    public function srv_getLicenceDetails($slug): JsonResponse
    {
        try {
            $licence = $this->licenceModel->where('slug', $slug)
            ->with('categorie', 'fonction','documents.document','tranche')
            ->firstOrFail();

            $organisation = $licence->organisation;

            $licenceDataFormated = [
                'licence_code_unique' => $licence->licence_code_unique,
                'categorie_code_unique' => $licence->categorie_code_unique,
                'fonction_code_unique' => $licence->fonction_code_unique,

                'categorie' => $licence->categorie->categorie_name,
                'categorie_montant' => $licence->categorie->categorie_montant,
                'fonction' => $licence->fonction->fonction_name,
                'tranche' => $licence->tranche->tranche_name ?? 'Aucun',
                'tranche_age' => $licence->tranche->tranche_age ?? 'Aucun',
                'num_licence' => $licence->num_licence,
                'first_name' => $licence->first_name,
                'last_name' => $licence->last_name,

                'telephone' => $licence->telephone,
                'email' => $licence->email,
                'gender' => $licence->genre,
                'date_naissance' => $licence->date_naissance,
                'lieu_naissance' => $licence->lieu_naissance,
                'nationalite' => $licence->nationalite,
                'groupe_sanguin' => $licence->groupe_sanguin,
                'organisation' => $organisation->organisation_name ?? 'Aucun',
                'organisation_abreviation' => $organisation->organisation_abreviation ?? 'Aucun',
                'medecin' => $licence->medecin_full_name ?? 'Aucun',
                'demandeur' => $licence->demandeur_full_name ?? 'Aucun',
                'documents' => $licence->documents,
                'payment' => $licence->payment,
                'operation_type' => $licence->operation_type,
                'request_date' => $licence->request_date,
                'slug' => $licence->slug,
            ];

            $operationCode = $this->codeGeneratorService->generateOperationCode();

            $this->licenceModel->updateOperationCodeAttribute($licence->slug, $operationCode);

            $description = $licence->operation_type === 'new'
                ? "Nouvelle licence à ". now()->format('Y-m-d H:i:s')
                : "Renouvellement de licence à ". now()->format('Y-m-d H:i:s');


            $photo = collect($licence->documents ?? [])
            ->first(function ($doc) {
                return isset($doc->type, $doc->document->document_name)
                    && stripos($doc->type, 'image') !== false
                    && stripos($doc->document->document_name, 'PHOTO') !== false;
            });

            //$paymentUrl = $this->generateCinetPayUrl($licence,$description);

            $licence_photo_base64 = "";

            if ($photo && isset($photo->document_path)) {
                $path = storage_path('app/public/' . $photo->document_path);
                
                if (file_exists($path)) {
                    // 🔑 Clé de cache basée sur le chemin + date de modification
                    $fileMTime = filemtime($path);
                    $cacheKey = "licence_photo_base64_{$slug}_v{$fileMTime}";

                    $licence_photo_base64 = Cache::remember($cacheKey, now()->addHours(24), function () use ($path) {
                        try {
                            $manager = new ImageManager(new Driver());
                            $image = $manager->read($path);
                            $image = $image->cover(300, 300);
                            $encodedImage = $image->toJpeg(85);
                            return 'data:image/jpeg;base64,' . base64_encode($encodedImage->toString());
                        } catch (\Exception $e) {
                            \Log::warning('Échec traitement photo : ' . $e->getMessage());
                            return null;
                        }
                    });
                }
            }

            

            //$licence_photo_base64 = base64_encode(file_get_contents($photo->document_path_url));


            return $this->jsonResponseService->successResponseWithData(
                "Détails de la licence récupérée avec succès",
                [
                    'licence' => $licenceDataFormated,
                    'organisation' => $organisation,
                    'photo' => $licence_photo_base64,
                    //'paymentUrl' => $paymentUrl,
                ],
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $e) {
            $this->customLogError->logError(
                'Licence non trouvée',
                $e
            );
            return $this->jsonResponseService->errorResponse(
                'Licence non trouvée',
                Response::HTTP_NOT_FOUND
            );
        }

        catch (\Throwable $th) {
            $this->customLogError->logError(
                'Erreur lors de la récupération des détails de la licence',
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération des détails de la licence',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    public function srv_updateLicence(Request $request, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $licence = $this->licenceModel->where('slug', $slug)
            ->firstOrFail();

            $licence->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'genre' => $request->genre,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'telephone' => $request->telephone,
                'nationalite' => $request->nationalite,
            ]);

            DB::commit();

            return $this->jsonResponseService->successResponse(
                'Licence mise à jour avec succès',
                Response::HTTP_OK
            );
        } catch(ModelNotFoundException $e) {
            DB::rollBack();
            $this->customLogError->logError(
                'Erreur lors de la mise à jour de la licence avec le slug fourni @slug : ' . $slug,
                $e
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la mise à jour de la licence avec le slug fourni @slug : ' . $slug,
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError(
                'Erreur lors de la mise à jour des détails de la licence',
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la mise à jour des détails de la licence',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    public function srv_getTableData() : JsonResponse
    {
        try {
            // get table data
            $listCategorie = $this->categorieModel->all();
            $listFonction = $this->fonctionModel->all();


            return $this->jsonResponseService->successResponseWithData(
                'Données de la table récupérées avec succès',
                [
                    'listCategorie' => $listCategorie,
                    'listFonction' => $listFonction,
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError('Erreur lors de la récupération des données de la table', $th);
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération des données de la table',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // edit licence
    public function srv_getCurrentLicence($slug): JsonResponse
    {
        try {
            $licence = $this->licenceModel->where('slug', $slug)
            ->with('categorie','documents.document')
            ->firstOrFail();

            $organisation = $licence->organisation;

            $documentsRequis = $this->documentsRequisModel
            ->whereIn('document_code_unique', $licence->categorie->document_code_unique)
            ->get();

            return $this->jsonResponseService->successResponseWithData(
                'Détails de la licence récupérée avec succès',
                [
                    'licence' => $licence,
                    'organisation' => $organisation,
                    'documentsRequis' => $documentsRequis,

                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                'Erreur lors de la récupération des détails de la licence',
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération des détails de la licence',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }



    // generate licence
    public function srv_generateLicence(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $code = str_replace('FIA-', '', $request->organisation_code_unique);
            // generate licence code
            $licenceCodeUnique = $this->codeGeneratorService->generateLicenceCodeUnique();
            $operationCode = $this->codeGeneratorService->generateOperationCode();

            $organisation = $this->licenceModel->getOrganisationCodeUnique($code);

            //Log::info("organisation",[$organisation]);

            $licenceGenerated = $this->licenceModel->create([
                'licence_code_unique' => $licenceCodeUnique,
                'operation_code' => $operationCode,
                'categorie_code_unique' => $request->categorie_code_unique,
                'fonction_code_unique' => $request->fonction_code_unique,
                'organisation_code_unique' => $organisation['data']->organisation_code_unique,
                'organisation_type' => $organisation['type'],
                'payment' => 'pending',
                'slug' => Str::uuid(),
            ]);

            DB::commit();

            return $this->jsonResponseService->successResponseWithData(
                "Licence générée avec succès",
                $licenceGenerated,
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError(
                'Erreur lors de la génération de la licence',
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la génération de la licence',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateLicenceInfoPersonnel(Request $request, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $licence = $this->licenceModel->where('slug', $slug)
            ->with('categorie','documents.document')
            ->firstOrFail();

            $licence->update([
                'tranche_code_unique' => $request->tranche_code_unique ?? null,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'genre' => $request->genre,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'telephone' => $request->telephone,
                'email' => $request->email,
                'nationalite' => $request->nationalite,
                'groupe_sanguin' => $request->groupe_sanguin,
                'info_state' => 'finished',
            ]);

            DB::commit();

            return $this->jsonResponseService->successResponse(
                'Détails de la licence récupérée avec succès',
                Response::HTTP_OK
            );
        } catch(ModelNotFoundException $e) {
            DB::rollBack();
            $this->customLogError->logError(
                'Erreur lors de la mise à jour de la licence avec le slug fourni @slug : ' . $slug,
                $e
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la mise à jour de la licence avec le slug fourni @slug : ' . $slug,
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError(
                'Erreur lors de la récupération des détails de la licence',
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération des détails de la licence',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateLicenceInfoOrganisation(Request $request, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $licence = $this->licenceModel->where('slug', $slug)
            ->with('categorie','documents.document')
            ->firstOrFail();

            $licence->update([
                'demandeur_full_name' => $request->demandeur_full_name,
                'medecin_full_name' => $request->medecin_full_name,
                'organisation_state' => 'finished',
            ]);

            DB::commit();

            return $this->jsonResponseService->successResponse(
                'Détails de la licence récupérée avec succès',
                Response::HTTP_OK
            );
        } catch(ModelNotFoundException $e) {
            DB::rollBack();
            $this->customLogError->logError(
                'Erreur lors de la mise à jour de la licence avec le slug fourni @slug : ' . $slug,
                $e
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la mise à jour de la licence avec le slug fourni @slug : ' . $slug,
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError(
                'Erreur lors de la mise à jour des détails de la licence',
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la mise à jour des détails de la licence',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    
    private function generateCinetPayUrl($currentOperationData, $description)
    {
        try {


            //Veuillez entrer votre apiKey
            $apikey = Marchand::getApiKey();
            //Veuillez entrer votre siteId
            $site_id = Marchand::getSiteId();

            //notify url
            $notify_url = "https://fia-base-api.fia-base.com/api/payment-notify";
            //return url
            $return_url = "https://fia-base.com";
            $channels = "ALL";

            /*information supplémentaire que vous voulez afficher
            sur la facture de CinetPay(Supporte trois variables
            que vous nommez à votre convenance)*/
            $invoice_data = array(
                "Gender" => $currentOperationData->genre,
                "Date of Birth" => $currentOperationData->date_naissance,
                "Nationality" => $currentOperationData->nationalite,
            );

            //
            $formData = array(
                "transaction_id" => $currentOperationData->operation_code,
                "amount" => $currentOperationData->categorie->categorie_montant,
                "currency" => "XOF",
                "customer_surname" => $currentOperationData->first_name,
                "customer_name" => $currentOperationData->last_name,
                "description" => $description,
                "notify_url" => $notify_url,
                "return_url" => $return_url,
                "channels" => $channels,
                "invoice_data" => $invoice_data,
                //pour afficher le paiement par carte de credit
                "customer_email" => $currentOperationData->email ?? "", //l'email du client
                "customer_phone_number" => $currentOperationData->telephone ?? "", //Le numéro de téléphone du client
                "customer_address" => "Abidjan", //l'adresse du client
                "customer_city" => "ABidjan", // ville du client
                "customer_country" => "CI", //Le pays du client, la valeur à envoyer est le code ISO du pays (code à deux chiffre) ex : CI, BF, US, CA, FR
                "customer_state" => "CI", //L’état dans de la quel se trouve le client. Cette valeur est obligatoire si le client se trouve au États Unis d’Amérique (US) ou au Canada (CA)
                "customer_zip_code" => "00225" //Le code postal du client
            );

            $CinetPay = new CinetPay($site_id, $apikey, $VerifySsl = false);//$VerifySsl=true <=> Pour activerr la verification ssl sur curl
            $result = $CinetPay->generatePaymentLink($formData);

            if ($result["code"] == '201') {
                $url = $result["data"]["payment_url"];
                //dd($url);//dd($result["code"] == '201');
                // ajouter le token à la transaction enregistré
                /* $commande->update(); */
                //redirection vers l'url de paiement
                //header('Location:'.);
                return $url;

            } else {
                return null;
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    // srv_getLicenceListFilter

    public function srv_getLicenceListFilter(Request $request): JsonResponse
    {
        try {

            // Pagination parameters
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            // Filtres dynamiques
            $gender = $request->input('gender');
            $categorieCode = $request->input('categorie_code_unique');
            $fonctionCode = $request->input('fonction_code_unique');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $licenceType = $request->input('licence_type');

            $query = $this->licenceModel->withTrashed()
            ->with(['categorie', 'fonction','tranche','club','ligue','federation'])
            ->where('operation_type', $licenceType);

            $query->when($gender, function ($query) use ($gender) {
                $query->whereHas('athlete', function ($sub) use ($gender) {
                    $sub->where('genre', $gender);
                });
            });
            $query->when($categorieCode, function ($query) use ($categorieCode) {
                $query->whereHas('categorie', function ($sub) use ($categorieCode) {
                    $sub->where('categorie_code_unique', $categorieCode);
                });
            });
            $query->when($fonctionCode, function ($query) use ($fonctionCode) {
                $query->whereHas('fonction', function ($sub) use ($fonctionCode) {
                    $sub->where('fonction_code_unique', $fonctionCode);
                });
            });
            $query->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            });

            $licenceList = $query->paginate($perPage, ['*'], 'page', $page);

            $licenceDataFormated = $licenceList->getCollection()->map(function ($licence) {
                return [
                    'id' => $licence->id,
                    'licence_code_unique' => $licence->licence_code_unique,
                    'categorie_code_unique' => $licence->categorie_code_unique,
                    'fonction_code_unique' => $licence->fonction_code_unique,
                    'categorie' => $licence->categorie->categorie_name,
                    'fonction' => $licence->fonction->fonction_name,
                    'tranche' => $licence->tranche->tranche_name ?? null,
                    'num_licence' => $licence->num_licence,
                    'first_name' => $licence->first_name,
                    'last_name' => $licence->last_name,

                    'telephone' => $licence->telephone,
                    'date_naissance' => $licence->date_naissance,
                    'genre' => $licence->genre,
                    'montant' => $licence->categorie->categorie_montant,

                    'club_name' => $licence->club->club_name ?? null,
                    'ligue_name' => $licence->ligue->ligue_name ?? null,
                    'federation_name' => $licence->federation->federation_name ?? null,

                    'payment' => $licence->payment,
                    'operation_type' => $licence->operation_type,
                    'request_date' => $licence->request_date,
                    'slug' => $licence->slug,
                ];
            });

            return $this->jsonResponseService->successResponseWithData(
                "Liste des licences récupérée avec succès",
                [
                    'licenceList' => $licenceDataFormated,
                    'paginations' => [
                        'total' => $licenceList->total(),
                        'per_page' => $licenceList->perPage(),
                        'current_page' => $licenceList->currentPage(),
                        'last_page' => $licenceList->lastPage(),
                    ],
                ],
                Response::HTTP_OK
            );


        } catch (\Throwable $th) {
            $this->customLogError->logError(
                'Erreur lors de la récupération de la liste des licences',
                $th
            );
            return $this->jsonResponseService->errorResponse(
                'Erreur lors de la récupération de la liste des licences',
                $th->getMessage()
            );
        }
    }


    // update documents
    public function srv_updateLicenceDocuments(Request $request, $slug): JsonResponse
    {
        return $this->licenceDocumentService->srv_updateLicenceDocument($request, $slug);
    }




}

