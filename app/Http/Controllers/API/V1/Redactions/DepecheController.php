<?php

namespace App\Http\Controllers\API\V1\Redactions;


use DateTime;
use DateInterval;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\CodeGenerator;
use App\Models\NotificationPush;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\Redactions\DepecheModels;
use App\Services\NotificationPushService;
use App\Models\DepecheViews\DepecheViewsModels;
use App\Models\AbonnesMobileModels\AbonnesMobileModels;
use Illuminate\Broadcasting\Broadcasters\NullBroadcaster;
use App\Models\AbonnementsMobileModels\AbonnementsMobileModels;

class DepecheController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $get_depeche = DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select(
                    'countries_models.pays',
                    'countries_models.flag',
                    'rubrique_models.rubrique',
                    'genre_journalistique_models.genre',
                    'depeche_models.*'
                )
                ->orderBy('depeche_models.id', 'desc')
                ->limit(100)
                ->get();

            $count_depeche = DB::table('depeche_models')->count();

            return response()->json(
                [
                    'depeche' => $get_depeche,
                    'depeche_number' => $count_depeche,
                ]
            );
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }


    public static function get_article_with_rubrique($rubrique)
    {

        try {
            $article = DB::table('depeche_models')
                ->join('countries_models', 'depeches.pays_id', '=', 'countries_models.id')
                ->select('countries_models.pays', 'countries_models.flag', 'depeche_models.*')
                ->where('depeche_models.rubrique_libelle', $rubrique)
                ->where('articles_models.status', 1)
                ->orderBy('articles_models.id', 'desc')
                ->limit(50)
                ->get();

            return [
                'depeche' => $article,
                'categorie' => $rubrique
            ];
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }

    }

    public function store(Request $request)
    {
        try {
            if (empty($request->titre)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le titre de la dépêche est obligatoire"
                    ]
                );
            endif;
            if (empty($request->rubrique_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "La rubrique de la dépêche est obligatoire"
                    ]
                );
            endif;
            if (empty($request->pays_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le pays de la dépêche est obligatoire"
                    ]
                );
            endif;
            if (empty($request->genre_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le genre journalistique de la dépêche est obligatoire"
                    ]
                );
            endif;
            if (empty($request->lead)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le lead de l'article est obligatoire"
                    ]
                );
            endif;
            if (empty($request->contenus)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le contenu de l'article est obligatoire"
                    ]
                );
            endif;
            if (empty($request->media_url)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Vous n'avez pas choisi d'image"
                    ]
                );
            endif;

            $add_depeche = new DepecheModels();
            $add_depeche->titre = $request->titre;
            $add_depeche->rubrique_id = $request->rubrique_id;
            $add_depeche->pays_id = $request->pays_id;
            $add_depeche->genre_id = $request->genre_id;
            $add_depeche->author = $request->author;
            $add_depeche->lead = $request->lead;
            $add_depeche->legende = $request->legende;
            $add_depeche->contenus = $request->contenus;
            $add_depeche->media_url = $request->media_url;
            $timeStamp = date('His', strtotime(now()));
            $add_depeche->slug = $timeStamp . '-' . Str::slug($request->titre);

            if ($add_depeche->save()) {

                try {

                    $devices = UserDevice::all();

                    foreach ($devices as $device) {
                        try {
                            // Envoi de la notification seulement si l'appareil a un token
                            if ($device->device_id) {
                                $this->sendDepecheNotification(
                                    $device->device_id,
                                    $add_depeche->titre,
                                    $add_depeche->pays_id,
                                    $add_depeche->lead,
                                    $add_depeche->media_url,
                                    $add_depeche->slug
                                );
                            }
                        } catch (\Throwable $e) {
                            // Log de l'erreur en cas de problème lors de l'envoi de la notification
                            Log::error("Erreur lors de l'envoi de la notification au device_id: {$device->id} : " . $e->getMessage());
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error(message: 'Erreur lors de l\'envoi des notifications : ' . $e->getMessage());
                }


                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'slug' => $add_depeche->slug,
                        'message' => "Ok ! La dépêche a été enregistrée avec succès."
                    ]
                );

                // $notificationService = new NotificationPushService();
                // $notificationService->sendBulkNotifications($add_depeche, 'depeche');

                // return response()->json(
                //     [
                //         'status' => 'succès',
                //         'code' => 200,
                //         'slug' => $add_depeche->slug,
                //         'message' => "Ok ! La dépêche a été enregistrée avec succès."
                //     ]
                // );
            } else {
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de l'enregistrement de la dépêche, veuillez réessayer!"
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::error(message: 'Une erreur est surevue : ' . $e->getMessage());

            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage()
                ]
            );
        }
    }

    private function sendDepecheNotification($deviceToken, $title, $paysId, $body, $media, $slug)
    {
        try {
            // Préparer les données de la notification
            $notificationData = [
                'device_id' => $deviceToken,
                'type' => 'depeche',
                'title' => $title,
                'pays_id' => $paysId,
                'body' => $body,
                'media' => $media,
                'slug' => $slug,
                'sent' => false,
            ];

            // Préparer les données pour OneSignal
            $data = [
                'app_id' => env('ONESIGNAL_APP_ID'),
                'include_player_ids' => [$deviceToken],
                'headings' => ['en' => $title],
                'contents' => ['en' => $body],
                'large_icon' => $media,
                'big_picture' => $media,
                'small_icon' => 'ic_stat_icon_monochrome',
                'data' => [
                    'type' => 'depeche',
                    'slug' => $slug,
                    'paysId' => $paysId
                ],
            ];

            // Envoyer la notification via OneSignal
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . env('ONESIGNAL_REST_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', $data);

            if ($response->successful()) {
                $notificationData['sent'] = true;
            }

            // Sauvegarder les données de la notification dans la base de données
            NotificationPush::create($notificationData);

            return $response->json();

        } catch (\Throwable $e) {
            // Log de l'erreur en cas de problème lors de l'envoi de la notification
            Log::error("Erreur lors de l'envoi de la notification : " . $e->getMessage());

            // Sauvegarder les données de la notification dans la base de données avec le statut d'erreur
            NotificationPush::create(array_merge($notificationData, [
                'sent' => false,
            ]));

            return response()->json([
                'status' => 'error',
                'message' => "Une erreur est survenue lors de l'envoi de la notification.",
                'error' => $e->getMessage(),
            ]);
        }
    }

    // DETAIL ARTICLE SUR ALERTE INFO
    public function get_detail_depeche_on_backend($slug)
    {
        try {
            if (!isset($slug)):
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Erreur!, Aucun élément trouvé"
                    ]
                );
            else:

                return DB::table('depeche_models')
                    ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                    ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                    ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
                    ->select(
                        'countries_models.pays',
                        'countries_models.flag',
                        'rubrique_models.rubrique',
                        'genre_journalistique_models.genre',
                        'depeche_models.*'
                    )
                    ->where('depeche_models.slug', $slug)
                    ->first();
            endif;
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $slug)
    {
        try {
            if (empty($request->titre)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le titre de l'article est obligatoire"
                    ]
                );
            endif;
            if (empty($request->rubrique_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "La rubrique de la dépêche est obligatoire"
                    ]
                );
            endif;
            if (empty($request->pays_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le pays de la dépêche est obligatoire"
                    ]
                );
            endif;

            if (empty($request->genre_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le genre journalistique de la dépêche est obligatoire"
                    ]
                );
            endif;
            if (empty($request->lead)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le lead de l'article est obligatoire"
                    ]
                );
            endif;
            if (empty($request->contenus)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le contenu de l'article est obligatoire"
                    ]
                );
            endif;


            if (empty($request->media_url)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Vous n'avez pas choisir d'image"
                    ]
                );
            endif;

            $timeStamp = date('His', strtotime(now()));

            $newsSlug = '';

            $prefix = $this->hasSixDigitPrefix($slug);

            if ($prefix != null) {
                $newsSlug = $prefix . '-' . Str::slug($request->titre);
            } else {
                $newsSlug = $timeStamp . '-' . Str::slug($request->titre);
            }

            $update_depeche = DepecheModels::where('slug', $slug)->first();

            $update_depeche->titre = $request->titre;
            $update_depeche->rubrique_id = $request->rubrique_id;
            $update_depeche->pays_id = $request->pays_id;
            $update_depeche->genre_id = $request->genre_id;
            $update_depeche->lead = $request->lead;
            $update_depeche->legende = $request->legende;
            $update_depeche->contenus = $request->contenus;
            $update_depeche->media_url = $request->media_url;
            $update_depeche->slug = $newsSlug;

            if ($update_depeche->save()):

                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'slug' => $update_depeche->slug,
                        'message' => "Ok ! La dépêche a été modifiée avec succès."
                    ]
                );
            else:

                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modification de la dépêche, veuillez réessayer!"
                    ]
                );
            endif;


        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }

    public function hasSixDigitPrefix(string $slug)
    {
        $pattern = '/^(\d{6})-/'; // Capture les 6 chiffres avant le tiret
        $matches = [];

        if (preg_match($pattern, $slug, $matches)) {
            return $matches[1]; // Retourne les 6 chiffres
        }

        return null; // Aucun préfixe trouvé
    }
    
    public function pushing_depeche($slug)
    {
        try {
            if (!$slug):
                return response()->json([
                    'code' => 404,
                    'status' => 'erreur',
                    'message' => "Erreur! Aucun élément trouvé"
                ], 404);
            endif;
    
            // Récupérer la dépêche
            $depeche = DepecheModels::where('slug', $slug)->first();
            
            if (!$depeche) {
                return response()->json([
                    'code' => 404,
                    'status' => 'erreur',
                    'message' => "Dépêche non trouvée"
                ], 404);
            }
    
            // Vérifier si la dépêche peut être publiée
            if ($depeche->status == 0) {
                // Vérifications avant publication
                if (empty($depeche->titre)) {
                    return response()->json([
                        'code' => 422,
                        'status' => 'erreur',
                        'message' => "Impossible de publier : le titre est manquant"
                    ], 422);
                }
                
                if (empty($depeche->contenus)) {
                    return response()->json([
                        'code' => 422,
                        'status' => 'erreur',
                        'message' => "Impossible de publier : le contenu est manquant"
                    ], 422);
                }
                
                if (empty($depeche->rubrique_id)) {
                    return response()->json([
                        'code' => 422,
                        'status' => 'erreur',
                        'message' => "Impossible de publier : la rubrique est manquante"
                    ], 422);
                }
            }
    
            // Inverser le statut
            $newStatus = $depeche->status == 1 ? 0 : 1;
            $depeche->status = $newStatus;
            
            $depeche->save();
    
            $action = $newStatus == 1 ? 'publiée' : 'dépubliée';
    
            return response()->json([
                'code' => 200,
                'status' => 'succès',
                'message' => "Votre dépêche a été {$action} en ligne.",
            ], 200);
    
        } catch (\Throwable $e) {
            Log::error('Erreur dans push_depeche : ' . $e->getMessage());
            
            return response()->json([
                'code' => 500,
                'status' => 'erreur',
                'message' => 'Une erreur est survenue : ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $slug)
    {
        try {
            if (!$slug):
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Erreur!, Aucun élément trouvé"
                    ]
                );
            else:
                DepecheModels::where('slug', $slug)->delete();

                return response()->json(
                    [
                        'status' => 'success',
                        'code' => 200,
                        'message' => "Ok!,Suppression effectuée"
                    ]
                );

            endif;
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }


    public function push_depeche($slug)
    {
        try {
            if (!$slug):
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 404,
                        'message' => "Erreur!, Aucun élément trouvé"
                    ]
                );
            else:
                $status = DepecheModels::where('slug', $slug)->value('status');
                if ($status == 1):
                    DepecheModels::where('slug', $slug)->update(['status' => 0]);
                    return response()->json(
                        [
                            'status' => 'succès',
                            'code' => 200,
                            'message' => "Ok ! Votre dépêche a été retirée en ligne."
                        ]
                    );
                else:
                    DepecheModels::where('slug', $slug)->update(['status' => 1]);
                    return response()->json(
                        [
                            'status' => 'succès',
                            'code' => 200,
                            'message' => "Ok ! Votre dépêche a été publiée en ligne."
                        ]
                    );
                endif;
            endif;
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 302,
                    'message' => $e->getMessage()
                ]
            );
        }
    }


    // GET CUSTOMER ARTICLES
    public function get_customer_depeche($customer)
    {

        try {
            if (!isset($customer)):
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Erreur!, Aucun élément trouvé"
                    ]
                );
            else:
                return DB::table('depeche_models')
                    ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                    ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                    ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
                    ->select(
                        'countries_models.pays',
                        'countries_models.flag',
                        'rubrique_models.rubrique',
                        'genre_journalistique_models.genre',
                        'depeche_models.*'
                    )
                    ->where('depeche_models.author', $customer)
                    ->orderByDesc('depeche_models.id')
                    ->get();

            endif;
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }







    // FILTER ARTICLE

    public function filter_on_depeche(Request $request)
    {

        try {
            // Récupération des dates
            $date_debut = date('Y-m-d', strtotime($request->date_debut));
            $date_fin_formatted = date('Y-m-d', strtotime($request->date_fin));

            // Initialisation des critères de filtrage
            $rubrique_ids = $request->rubrique_id ?? [];
            $genre_ids = $request->genre_id ?? [];
            $pays_ids = $request->pays_id ?? [];

            // Requête de base avec jointure pays
            $query = DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select(
                    'countries_models.pays',
                    'countries_models.flag',
                    'countries_models.iso_code',
                    'rubrique_models.rubrique',
                    'genre_journalistique_models.genre',
                    'depeche_models.*'
                )
                ->whereBetween('depeche_models.created_at', [$date_debut, $date_fin_formatted])
                ->orderByDesc('depeche_models.id');

            // Gestion des différentes combinaisons de filtres
            if (!empty($rubrique_ids) && !empty($genre_ids) && !empty($pays_ids)) {
                // Cas : rubrique + genre + pays
                $query->whereIn('depeche_models.rubrique_id', $rubrique_ids)
                    ->whereIn('depeche_models.genre_id', $genre_ids)
                    ->whereIn('depeche_models.pays_id', $pays_ids);

            } elseif (!empty($rubrique_ids) && !empty($genre_ids)) {
                // Cas : rubrique + genre
                $query->whereIn('depeche_models.rubrique_id', $rubrique_ids)
                    ->whereIn('depeche_models.genre_id', $genre_ids);

            } elseif (!empty($rubrique_ids) && !empty($pays_ids)) {
                // Cas : rubrique + pays
                $query->whereIn('depeche_models.rubrique_id', $rubrique_ids)
                    ->whereIn('depeche_models.pays_id', $pays_ids);

            } elseif (!empty($pays_ids)) {
                // Cas : uniquement pays
                $query->whereIn('depeche_models.pays_id', $pays_ids);

            }

            // Exécution de la requête
            $results = $query->get();

            return $results;

        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }

    // GET FLASH INFO ARTICLE

    public function get_depeche_flash_info()
    {
        try {
            return DB::table('depeche_models')->orderBy('id', 'desc')->where('status', 1)->limit(8)->get();
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }

    // RECENTE ARTICLES

    public function get_recente_depeche()
    {

        try {
            $get_depeches = DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->select('countries_models.pays', 'countries_models.flag', 'depeche_models.*')
                ->orderBy('id', 'desc')->limit(20)->get();

            $count_depeches = DB::table('depeche_models')->count();

            return response()->json(
                [
                    'depeches' => $get_depeches,
                    'depeche_number' => $count_depeches,
                ]
            );
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }

    // STATISTIQUE

    public function get_depeche_hebdo_statistique(Request $request)
    {

        // 📆 Traitement de la période
        $date_debut = $request->filled('startDate') 
        ? Carbon::parse($request->startDate)->startOfDay() 
        : now()->subDays(6)->startOfDay();
        $date_fin = $request->filled('endDate') 
        ? Carbon::parse($request->endDate)->endOfDay() 
        : now()->endOfDay();

        try {

            // === Dépêches (ont titre, slug, rubrique, genre) ===
            $depecheTotal = $this->getTotal('depeche_models', $date_debut, $date_fin);
            $depecheByCountry = $this->getByCountry('depeche_models', $date_debut, $date_fin);
            $depecheByAuthor = $this->getDepechesByAuthor($date_debut, $date_fin);
            $depecheByRubrique = $this->getDepechesByRubrique($date_debut, $date_fin);
            $depecheByGenre = $this->getDepechesByGenre($date_debut, $date_fin);

            // === Flashes (ont contenu, rubrique, mais PAS de slug ni de genre) ===
            $flashTotal = $this->getTotal('flashes_models', $date_debut, $date_fin);
            $flashByCountry = $this->getByCountry('flashes_models', $date_debut, $date_fin);
            $flashByAuthor = $this->getFlashesByAuthor($date_debut, $date_fin);
            $flashByRubrique = $this->getFlashesByRubrique($date_debut, $date_fin);

            return response()->json([
                'depecheData' => [
                    'total' => $depecheTotal,
                    'byCountry' => $depecheByCountry,
                    'byAuthor' => $depecheByAuthor,
                    'byRubrique' => $depecheByRubrique,
                    'byGenre' => $depecheByGenre,
                ],
                'flashData' => [
                    'total' => $flashTotal,
                    'byCountry' => $flashByCountry,
                    'byAuthor' => $flashByAuthor,
                    'byRubrique' => $flashByRubrique,
                    // Pas de byGenre
                ]
            ]);

            /** 
            // Toutes les dépêches validées
            $count_all = DB::table('depeche_models')->whereBetween('created_at', [$date_debut, $date_fin])
                ->where('status', 1)
                ->count();

            // Tous les flashes validées
            $count_all_flashes = DB::table('flashes_models')->whereBetween('created_at', [$date_debut, $date_fin])
                ->where('status', 1)
                ->count();

            // Récupérer les dépêches et les comptes par pays
            $counts_by_country = DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->whereBetween('depeche_models.created_at', [$date_debut, $date_fin])
                ->where('depeche_models.status', 1)
                ->selectRaw('countries_models.iso_code, COUNT(*) as total')
                ->groupBy('countries_models.iso_code')
                ->pluck('total', 'iso_code'); // → ['CI' => 10, 'BF' => 8, etc.]

            // 
            $counts_by_country_flashes = DB::table('flashes_models')
                ->join('countries_models', 'flashes_models.pays_id', '=', 'countries_models.id')
                ->whereBetween('flashes_models.created_at', [$date_debut, $date_fin])
                ->where('flashes_models.status', 1)
                ->selectRaw('countries_models.iso_code, COUNT(*) as total')
                ->groupBy('countries_models.iso_code')
                ->pluck('total', 'iso_code'); // → ['CI' => 10, 'BF' => 8, etc.]


            // get all depeches count and group by author between date_debut and date_fin and transform to array
            $counts_by_author = DB::table('depeche_models')
                ->whereBetween('created_at', [$date_debut, $date_fin])
                ->where('status', 1)
                ->selectRaw('author, COUNT(*) as total')
                ->groupBy('author')
                ->get()
                ->map(function ($item) {
                    return [
                        'author' => $item->author,
                        'total' => $item->total,
                    ];
                })
                ->toArray(); // → ['author1' => 10, 'author2' => 8, etc.]

            // get all flashe count and group by author between date_debut and date_fin and transform to array
            $counts_by_author_flashes = DB::table('flashes_models')
                ->whereBetween('created_at', [$date_debut, $date_fin])
                ->where('status', 1)
                ->selectRaw('author, COUNT(*) as total')
                ->groupBy('author')
                ->get()
                ->map(function ($item) {
                    return [
                        'author' => $item->author,
                        'total' => $item->total,
                    ];
                })
                ->toArray(); // → ['author1' => 10, 'author2' => 8, etc.]

            return response()->json([
                'depecheData' => [
                    'toutDepeches' => $count_all,
                    'ciDepeches' => $counts_by_country['CI'] ?? 0,
                    'bfDepeches' => $counts_by_country['BF'] ?? 0,
                    'mlDepeches' => $counts_by_country['ML'] ?? 0,
                    'journalisteDepeches' => $counts_by_author,
                ],
                'flashData' => [
                    'toutFlashes' => $count_all_flashes,
                    'ciFlashes' => $counts_by_country_flashes['CI'] ?? 0,
                    'bfFlashes' => $counts_by_country_flashes['BF'] ?? 0,
                    'mlFlashes' => $counts_by_country_flashes['ML'] ?? 0,
                    'journalisteFlashes' => $counts_by_author_flashes,
                ]
            ]);
            */
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }






    // GET CUSTOMER ARTICLES
    public function get_customer_news($customer)
    {
        try {
            if (!isset($customer)):
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Erreur!, Aucun élément trouvé"
                    ]
                );
            else:
                $customer_news = DB::table('depeche_models')
                    ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                    ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                    ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
                    ->select(
                        'rubrique_models.rubrique',
                        'genre_journalistique_models.genre',
                        'countries_models.pays',
                        'countries_models.flag',
                        'depeche_models.*'
                    )
                    ->where('depeche_models.author', 'LIKE', '%' . $customer . '%')
                    ->orderByDesc('depeche_models.id')
                    ->get();



                $count_article = DB::table('depeche_models')->where('author', 'LIKE', '%' . $customer . '%')->count();

                $depeche_ispublished = DB::table('depeche_models')->where('author', 'LIKE', '%' . $customer . '%')->where('status', 1)->count();
                $depeche_nopublished = DB::table('depeche_models')->where('author', 'LIKE', '%' . $customer . '%')->where('status', 0)->count();

                return response()->json(
                    [
                        'customer_list_news' => $customer_news,
                        'customer_total_news' => $count_article,
                        'customer_news_ispublished' => $depeche_ispublished,
                        'customer_news_nopublished' => $depeche_nopublished,
                    ]
                );
            endif;
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }


    public function filter_on_customer_news(Request $request)
    {

        //return $request->all();
        try {
            // Récupération des dates
            //$date_debut = date('Y-m-d', strtotime($request->date_debut));
            //$date_fin_formatted = date('Y-m-d', strtotime($request->date_fin));

            // 📆 Traitement de la période
            $date_debut = $request->filled('startDate') ? Carbon::parse($request->startDate)->startOfDay() : now()->subDays(6)->startOfDay();
            $date_fin_formatted = $request->filled('endDate') ? Carbon::parse($request->endDate)->endOfDay() : now()->endOfDay();

            // Initialisation des critères de filtrage
            $rubrique_ids = $request->rubrique_id ?? [];
            $genre_ids = $request->genre_id ?? [];
            $pays_ids = $request->pays_id ?? [];
            $author = $request->customer ?? '';

            // Requête de base avec jointure pays
            $query = DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select(
                    'countries_models.pays',
                    'countries_models.flag',
                    'countries_models.iso_code',
                    'rubrique_models.rubrique',
                    'genre_journalistique_models.genre',
                    'depeche_models.*'
                )
                ->whereBetween('depeche_models.created_at', [$date_debut, $date_fin_formatted])
                ->where('depeche_models.author', 'LIKE', '%' . $request->customer . '%')
                ->orderByDesc('depeche_models.id');

            // Gestion des différentes combinaisons de filtres
            if (!empty($rubrique_ids) && !empty($genre_ids) && !empty($pays_ids)) {
                // Cas : rubrique + genre + pays
                $query->whereIn('depeche_models.rubrique_id', $rubrique_ids)
                    ->whereIn('depeche_models.genre_id', $genre_ids)
                    ->whereIn('depeche_models.pays_id', $pays_ids);

            } elseif (empty($rubrique_ids) && empty($pays_ids) && !empty($genre_ids)) {
                // Cas : genre
                $query->whereIn('depeche_models.genre_id', $genre_ids);
            } elseif (!empty($rubrique_ids) && !empty($genre_ids)) {
                // Cas : rubrique + genre
                $query->whereIn('depeche_models.rubrique_id', $rubrique_ids)
                    ->whereIn('depeche_models.genre_id', $genre_ids);

            } elseif (!empty($rubrique_ids) && !empty($pays_ids)) {
                // Cas : rubrique + pays
                $query->whereIn('depeche_models.rubrique_id', $rubrique_ids)
                    ->whereIn('depeche_models.pays_id', $pays_ids);

            } elseif (!empty($pays_ids)) {
                // Cas : uniquement pays
                $query->whereIn('depeche_models.pays_id', $pays_ids);

            }

            // Exécution de la requête
            $results = $query->get();

            return $results;
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }







    // ----------------------------
    // SOUS-FONCTIONS PRIVÉES
    // ----------------------------

    private function getTotal(string $table, $date_debut, $date_fin)
    {
        return DB::table($table)
            ->whereBetween('created_at', [$date_debut, $date_fin])
            ->where('status', 1)
            ->count();
    }

    private function getByCountry(string $table, $date_debut, $date_fin)
    {
        return DB::table($table)
            ->join('countries_models', "{$table}.pays_id", '=', 'countries_models.id')
            ->whereBetween("{$table}.created_at", [$date_debut, $date_fin])
            ->where("{$table}.status", 1)
            ->select('countries_models.iso_code', 'countries_models.pays', DB::raw('COUNT(*) as total'))
            ->groupBy('countries_models.iso_code', 'countries_models.pays')
            ->get()
            ->map(fn($item) => [
                'iso_code' => $item->iso_code,
                'pays' => $item->pays,
                'total' => (int) $item->total,
            ])
            ->values()
            ->all();
    }

    // ----------------------------
    // DÉPÊCHES (complètes)
    // ----------------------------

    
    private function getDepechesByAuthor($date_debut, $date_fin)
    {
        $items = DB::table('depeche_models as d')
            ->join('countries_models as c', 'd.pays_id', '=', 'c.id')
            ->leftJoin('rubrique_models as r', 'd.rubrique_id', '=', 'r.id')
            ->leftJoin('genre_journalistique_models as g', 'd.genre_id', '=', 'g.id')
            ->whereBetween('d.created_at', [$date_debut, $date_fin])
            ->where('d.status', 1)
            ->select(
                'd.author',
                'd.id',
                'd.titre',
                'd.slug',
                'd.created_at',
                'd.status',
                'c.flag',
                DB::raw("COALESCE(r.rubrique, 'Non spécifié') as rubrique"),
                DB::raw("COALESCE(g.genre, 'Non spécifié') as genre")
            )
            ->get();

        return $items->groupBy('author')->map(function ($group, $author) {
            return [
                'author' => $author,
                'total' => $group->count(),
                'articles' => $group->map(fn($a) => [
                    'id' => $a->id,
                    'titre' => $a->titre,
                    'slug' => $a->slug,
                    'created_at' => $a->created_at,
                    'rubrique' => $a->rubrique,
                    'genre' => $a->genre,
                    'status' => $a->status,
                    'flag' => $a->flag,
                ])->values()->all(),
            ];
        })->values()->all();
    }


    private function getDepechesByRubrique($date_debut, $date_fin)
    {
        $items = DB::table('depeche_models as d')
            ->join('countries_models as c', 'd.pays_id', '=', 'c.id')
            ->leftJoin('rubrique_models as r', 'd.rubrique_id', '=', 'r.id')
            ->leftJoin('genre_journalistique_models as g', 'd.genre_id', '=', 'g.id')
            ->whereBetween('d.created_at', [$date_debut, $date_fin])
            ->where('d.status', 1)
            ->select(
                DB::raw("COALESCE(r.rubrique, 'Non spécifié') as rubrique"),
                'd.id',
                'd.titre',
                'd.slug',
                'd.created_at',
                'd.status',
                'c.flag',
                DB::raw("COALESCE(g.genre, 'Non spécifié') as genre")
            )
            ->get();

        return $items->groupBy('rubrique')->map(function ($group, $rubrique) {
            return [
                'rubrique' => $rubrique,
                'total' => $group->count(),
                'articles' => $group->map(fn($a) => [
                    'id' => $a->id,
                    'titre' => $a->titre,
                    'slug' => $a->slug,
                    'created_at' => $a->created_at,
                    'rubrique' => $rubrique,
                    'genre' => $a->genre,
                    'status' => $a->status,
                    'flag' => $a->flag,
                ])->values()->all(),
            ];
        })->values()->all();
    }

    private function getDepechesByGenre($date_debut, $date_fin)
    {
        $items = DB::table('depeche_models as d')
            ->join('countries_models as c', 'd.pays_id', '=', 'c.id')
            ->leftJoin('rubrique_models as r', 'd.rubrique_id', '=', 'r.id')
            ->leftJoin('genre_journalistique_models as g', 'd.genre_id', '=', 'g.id')
            ->whereBetween('d.created_at', [$date_debut, $date_fin])
            ->where('d.status', 1)
            ->select(
                DB::raw("COALESCE(g.genre, 'Non spécifié') as genre"),
                'd.id',
                'd.titre',
                'd.slug',
                'c.flag',
                'd.created_at',
                'd.status',
                'c.flag',
                DB::raw("COALESCE(r.rubrique, 'Non spécifié') as rubrique")
            )
            ->get();

        return $items->groupBy('genre')->map(function ($group, $genre) {
            return [
                'genre' => $genre,
                'total' => $group->count(),
                'articles' => $group->map(fn($a) => [
                    'id' => $a->id,
                    'titre' => $a->titre,
                    'slug' => $a->slug,
                    'flag' => $a->flag,
                    'created_at' => $a->created_at,
                    'rubrique' => $a->rubrique,
                    'genre' => $genre,
                    'status' => $a->status,
                ])->values()->all(),
            ];
        })->values()->all();
    }


    // ----------------------------
    // FLASHES (sans slug, sans genre)
    // ----------------------------

    private function getFlashesByAuthor($date_debut, $date_fin)
    {
        $items = DB::table('flashes_models as f')
            ->join('countries_models as c', 'f.pays_id', '=', 'c.id')
            ->leftJoin('rubrique_models as r', 'f.rubrique_id', '=', 'r.id')
            ->whereBetween('f.created_at', [$date_debut, $date_fin])
            ->where('f.status', 1)
            ->select(
                'f.author',
                'f.id',
                'f.contenus', // on normalise sous "titre"
                'f.created_at',
                'f.status',
                'c.flag',
                'f.slug',
                DB::raw("COALESCE(r.rubrique, 'Non spécifié') as rubrique")
            )
            ->get();

        return $items->groupBy('author')->map(function ($group, $author) {
            return [
                'author' => $author,
                'total' => $group->count(),
                'articles' => $group->map(fn($a) => [
                    'id' => $a->id,
                    'contenus' => $a->contenus,
                    'slug' => $a->slug, // pas de slug
                    'created_at' => $a->created_at,
                    'rubrique' => $a->rubrique,
                    'status' => $a->status,
                    'flag' => $a->flag,
                ])->values()->all(),
            ];
        })->values()->all();
    }

    private function getFlashesByRubrique($date_debut, $date_fin)
    {
        $items = DB::table('flashes_models as f')
            ->join('countries_models as c', 'f.pays_id', '=', 'c.id')
            ->leftJoin('rubrique_models as r', 'f.rubrique_id', '=', 'r.id')
            ->whereBetween('f.created_at', [$date_debut, $date_fin])
            ->where('f.status', 1)
            ->select(
                DB::raw("COALESCE(r.rubrique, 'Non spécifié') as rubrique"),
                'f.id',
                'f.contenus',
                'f.created_at',
                'f.status',
                'f.slug',
                'c.flag',
            )
            ->get();

        return $items->groupBy('rubrique')->map(function ($group, $rubrique) {
            return [
                'rubrique' => $rubrique,
                'total' => $group->count(),
                'articles' => $group->map(fn($a) => [
                    'id' => $a->id,
                    'contenus' => $a->contenus,
                    'slug' => $a->slug,
                    'created_at' => $a->created_at,
                    'rubrique' => $rubrique,
                    'status' => $a->status,
                    'flag' => $a->flag,
                ])->values()->all(),
            ];
        })->values()->all();
    }

}
