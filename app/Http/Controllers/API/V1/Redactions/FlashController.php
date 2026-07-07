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
use App\Models\Redactions\FlashesModels;
use App\Services\NotificationPushService;
use App\Models\Redactions\CountriesModels;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AbonnesMobileModels\AbonnesMobileModels;
use App\Models\AbonnementsMobileModels\AbonnementsMobileModels;

class FlashController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        try {

            // pagination variables
            $perpage = $request->input('perPage', 20);
            $page = $request->input('page', 1);


            $flashes = DB::table('flashes_models')
                ->join('countries_models', 'flashes_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'flashes_models.rubrique_id', '=', 'rubrique_models.id')
                ->select(
                    'countries_models.pays',
                    'countries_models.flag',
                    'rubrique_models.rubrique',
                    'flashes_models.*'
                )
                ->orderByDesc('id')
                ->paginate($perpage, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'flashData' => $flashes->items(),
                'pagination' => [
                    'total' => $flashes->total(),
                    'per_page' => $flashes->perPage(),
                    'current_page' => $flashes->currentPage(),
                    'last_page' => $flashes->lastPage(),
                ],
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 302,
                    'message' => $e->getMessage()
                ]
            );
        }
    }

    public function single_flash($slug)
    {

        try {
            return DB::table('flashes_models')
                ->join('countries_models', 'flashes_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'flashes_models.rubrique_id', '=', 'rubrique_models.id')
                ->select(
                    'countries_models.pays',
                    'countries_models.flag',
                    'rubrique_models.rubrique',
                    'flashes_models.*'
                )
                ->where('flashes_models.slug', $slug)->first();
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

    public function filter_on_flash(Request $request)
    {
        try {
            $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
            $dateFin   = Carbon::parse($request->date_fin)->endOfDay();

            $query = DB::table('flashes_models')
                ->leftJoin('countries_models', 'flashes_models.pays_id', '=', 'countries_models.id')
                ->leftJoin('rubrique_models', 'flashes_models.rubrique_id', '=', 'rubrique_models.id')
                ->select(
                    'rubrique_models.rubrique',
                    'countries_models.flag',
                    'countries_models.pays',
                    'flashes_models.*'
                )
                ->whereBetween('flashes_models.created_at', [$dateDebut, $dateFin]);

            // Filtre par rubrique si fourni
            if (!empty($request->rubrique_id) && is_array($request->rubrique_id)) {
                $query->whereIn('flashes_models.rubrique_id', $request->rubrique_id);
            }

            // Filtre par pays si fourni
            if (!empty($request->pays_id) && is_array($request->pays_id)) {
                $query->whereIn('flashes_models.pays_id', $request->pays_id);
            }

            return $query->orderBy('flashes_models.id', 'desc')->get();

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'erreur',
                'code' => 300,
                'message' => $e->getMessage(),
            ]);
        }
    }



    public function get_recente_flash()
    {

        try {
            $get_flash = DB::table('flashes_models')
                ->join('countries_models', 'flashes_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'flashes_models.rubrique_id', '=', 'rubrique_models.id')
                ->select(
                    'countries_models.pays',
                    'countries_models.flag',
                    'rubrique_models.rubrique',
                    'flashes_models.*'
                )
                ->orderBy('flashes_models.id', 'desc')->limit(20)->get();

            $count_flash = DB::table('flashes_models')->count();

            return response()->json(
                [
                    'flash' => $get_flash,
                    'flash_number' => $count_flash,
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validation des données
            if (empty($request->rubrique_id)) {
                return response()->json([
                    'code' => 302,
                    'status' => 'erreur',
                    'message' => "La rubrique du flash est obligatoire"
                ]);
            }
            if (empty($request->pays_id)) {
                return response()->json([
                    'code' => 302,
                    'status' => 'erreur',
                    'message' => "Le pays du flash est obligatoire"
                ]);
            }
            if (empty($request->contents)) {
                return response()->json([
                    'code' => 302,
                    'status' => 'erreur',
                    'message' => "Le message du flash est obligatoire"
                ]);
            }

            // Création du flash
            $add_flash = new FlashesModels();
            $add_flash->rubrique_id = $request->rubrique_id;
            $add_flash->pays_id = $request->pays_id;
            $add_flash->author = $request->author;
            $add_flash->contenus = $request->contents;
            $add_flash->slug = CodeGenerator::generateRfk();

            if ($add_flash->save()) {
                return response()->json([
                    'status' => 'succès',
                    'code' => 200,
                    'slug' => $add_flash->slug,
                    'message' => "Ok ! Le flash a été enregistré avec succès."
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'code' => 300,
                    'message' => "Erreur ! Échec de l'enregistrement du flash, veuillez réessayer!"
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 300,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function sendFlashNotification($deviceToken, $body, $paysId, $flashId)
    {
        try {
            // Récupérer les détails du pays
            $pays = CountriesModels::find($paysId);
            $notificationTitle = 'Flash - ' . ($pays ? $pays->pays : 'N/A');
            $notificationBody = Str::limit($body, 80, '...');

            // Préparer les données de la notification
            $notificationData = [
                'device_id' => $deviceToken,
                'type' => 'flash',
                'title' => $notificationTitle,
                'pays_id' => $paysId,
                'body' => $notificationBody,
                'sent' => false,
            ];

            // Préparer les données pour OneSignal
            $data = [
                'app_id' => env('ONESIGNAL_APP_ID'),
                'include_player_ids' => [$deviceToken],
                'headings' => ['en' => $notificationTitle],
                'contents' => ['en' => $notificationBody],
                'small_icon' => 'ic_stat_icon_monochrome',
                'data' => [
                    'type' => 'flash',
                    'paysId' => $paysId,
                    'flashId' => $flashId,
                    'flashBody' => $notificationBody
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
            Log::error("Erreur lors de l'envoi de la notification Flash : " . $e->getMessage());

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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $slug)
    {
        try {
            if (empty($request->rubrique_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "La rubrique du flash est obligatoire"
                    ]
                );
            endif;
            if (empty($request->pays_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le pays du flash est obligatoire"
                    ]
                );
            endif;

            if (empty($request->contents)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le message du flash est obligatoire"
                    ]
                );
            endif;


            $update_flash = FlashesModels::where('slug', $slug)->first();

            $update_flash->rubrique_id = $request->rubrique_id;
            $update_flash->pays_id = $request->pays_id;
            $update_flash->contenus = $request->contents;

            if ($update_flash->save()):

                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'slug' => $update_flash->slug,
                        'message' => "Ok ! Le flash a été modifié avec succès."
                    ]
                );
            else:

                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modification du flash, veuillez réessayer!"
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
                        'message' => "Erreur!, Aucun element trouvé"
                    ]
                );
            else:

                DB::table('flashes_models')->where('slug', $slug)->delete();
                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => "Ok!, Suppression éffectuée"
                    ]
                );

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



    protected function get_date_debut()
    {
        $current_date = new DateTime();
        $date_debut = $current_date->sub(new DateInterval('P9D'));
        $date_debut = $date_debut->format('Y-m-d');
        return $date_debut;
    }


    protected function get_date_fin()
    {
        $current_date = new DateTime();
        $date_fin = $current_date->format('Y-m-d');
        return $date_fin;
    }



    public function push_flash($slug)
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
                $status = FlashesModels::where('slug', $slug)->value('status');
                if ($status == 1):
                    FlashesModels::where('slug', $slug)->update(['status' => 0]);
                    return response()->json(
                        [
                            'status' => 'succès',
                            'code' => 200,
                            'message' => "Ok ! Votre flash a été retiré."
                        ]
                    );
                else:
                    FlashesModels::where('slug', $slug)->update(['status' => 1]);
                    return response()->json(
                        [
                            'status' => 'succès',
                            'code' => 200,
                            'message' => "Ok ! Votre flash a été publié."
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


    public function get_customer_flash($customer)
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
                return DB::table('flashes_models')
                    ->join('countries_models', 'flashes_models.pays_id', '=', 'countries_models.id')
                    ->select('countries_models.pays', 'countries_models.flag', 'flashes_models.*')
                    ->where('flashes_models.author', $customer)
                    ->orderByDesc('flashes_models.id')
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




    public function get_flash_hebdo_statistique(Request $request)
    {
        try {
            // 📆 Traitement de la période
            $date_debut = $request->filled('startDate') ? Carbon::parse($request->startDate)->startOfDay() : now()->subDays(6)->startOfDay();
            $date_fin = $request->filled('endDate') ? Carbon::parse($request->endDate)->endOfDay() : now()->endOfDay();

            // Tous les flashes validés
            $count_all = DB::table('flashes_models')
                ->whereBetween('created_at', [$date_debut, $date_fin])
                ->where('status', 1)
                ->count();

            // Compter les flashes par pays (CI, BF, ML)
            $counts_by_country = DB::table('flashes_models')
                ->join('countries_models', 'flashes_models.pays_id', '=', 'countries_models.id')
                ->whereBetween('flashes_models.created_at', [$date_debut, $date_fin])
                ->where('flashes_models.status', 1)
                ->selectRaw('countries_models.iso_code, COUNT(*) as total')
                ->groupBy('countries_models.iso_code')
                ->pluck('total', 'iso_code'); // → ['CI' => 4, 'BF' => 2, ...]

            return response()->json([
                'tout' => $count_all,
                'ci' => $counts_by_country['CI'] ?? 0,
                'bf' => $counts_by_country['BF'] ?? 0,
                'ml' => $counts_by_country['ML'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 300,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
