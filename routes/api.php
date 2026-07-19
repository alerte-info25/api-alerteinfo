<?php

use App\Http\Controllers\API\RH\JournalisteStatsController;
use App\Http\Controllers\API\V1\Abonnements\AbonnementsController;
use App\Http\Controllers\API\V1\Abonnements\AbonnesController;
use App\Http\Controllers\API\V1\Abonnements\ForfaitsAbonnementController;
use App\Http\Controllers\API\V1\AbonnementsWeb\AbonnementWebController;
use App\Http\Controllers\API\V1\AbonnementsWeb\AbonnesWebController;
use App\Http\Controllers\API\V1\AbonnementsWeb\CategoriesAbonnementWebController;
use App\Http\Controllers\API\V1\AbonnementsWeb\ForfaitAbonnementWebController;
use App\Http\Controllers\API\V1\AbonneMobiles\AbonneMobileController;
use App\Http\Controllers\API\V1\AbonneMobiles\ForfaitAbonnementsMobileController;
use App\Http\Controllers\API\V1\AlerteInfoWebSite\AboutsController;
use App\Http\Controllers\API\V1\AlerteInfoWebSite\OurBlogController;
use App\Http\Controllers\API\V1\AlerteInfoWebSite\OurContactsController;
use App\Http\Controllers\API\V1\AlerteInfoWebSite\OurReferencesController;
use App\Http\Controllers\API\V1\AlerteInfoWebSite\OurServicesController;
use App\Http\Controllers\API\V1\Auth\AuthentificatorController;
use App\Http\Controllers\API\V1\Auth\FrontendAuthentificatorController;
use App\Http\Controllers\API\V1\Banners\BannerController;
use App\Http\Controllers\API\V1\CinetPay\CinetPayNotifyController;
use App\Http\Controllers\API\V1\DiffusionSms\CarnetAdressController;
use App\Http\Controllers\API\V1\DiffusionSms\GestionOperatorController;
use App\Http\Controllers\API\V1\EventsKeyWords\EventsKeyWordsController;
use App\Http\Controllers\API\V1\Frontend\FooterSettingController;
use App\Http\Controllers\API\V1\Frontend\FrontendAlerteInfoController;
use App\Http\Controllers\API\V1\Frontend\FrontendMobileController;
use App\Http\Controllers\API\V1\Frontend\FrontendQuoideneufController;
use App\Http\Controllers\API\V1\Galeries\GalerieController;
use App\Http\Controllers\API\V1\GeniusPay\GeniusPayWebhookController;
use App\Http\Controllers\API\V1\PartnersNews\PartnerNewsController;
use App\Http\Controllers\API\V1\Quoideneufs\ArticlesController;
use App\Http\Controllers\API\V1\Quoideneufs\GenreJournalistiqueController;
use App\Http\Controllers\API\V1\Quoideneufs\MediaController;
use App\Http\Controllers\API\V1\Quoideneufs\RubriquesQuoideneufController;
use App\Http\Controllers\API\V1\Redactions\CountriesController;
use App\Http\Controllers\API\V1\Redactions\DepecheController;
use App\Http\Controllers\API\V1\Redactions\FlashController;
use App\Http\Controllers\API\V1\Redactions\RubriquesController;
use App\Http\Controllers\API\V1\RoleController;
use App\Http\Controllers\API\V1\SEO\OgMetaController;
use App\Http\Controllers\API\V1\Statistiques\AdminStatistiquesController;
use App\Http\Controllers\API\V1\UsersAccounts\AdministrationController;
use App\Http\Controllers\WellcomeController;
use App\Http\Middleware\ApiPartnerAuth;
use App\Http\Middleware\DynamicThrottle;
use App\Http\Middleware\VerifyJwtToken;
use Illuminate\Support\Facades\Route;
// Log::info('API CALLED', [
//     'url' => request()->fullUrl(),
//     'path' => request()->path(),
//     'method' => request()->method(),
//     'body' => request()->all(),
// ]);
Route::get('/welcome', [WellcomeController::class, 'index']);
// Route::get('/check-ip', function () {
//     return [
//         'ip' => file_get_contents('https://api.ipify.org')
//     ];
// });

// Routes pour les tests manuels
// Route::prefix('v1/test')->group(function () {
//     // Simuler un webhook
//     Route::post('/geniuspay/simulate-webhook', [ManualWebhookController::class, 'simulateWebhook']);

//     // Activer un abonnement directement
//     Route::post('/geniuspay/activate-subscription', [ManualWebhookController::class, 'activateSubscription']);

//     // Voir les abonnements en attente
//     Route::get('/geniuspay/pending-subscriptions', function () {
//         $pending = DB::table('abonnement_web_models')
//             ->where('payments', 0)
//             ->orderBy('created_at', 'desc')
//             ->get();

//         return response()->json([
//             'total' => $pending->count(),
//             'subscriptions' => $pending
//         ]);
//     });
// });

// API RH STAT JOURNALISTE
// Route::get('/journaliste/stats', function () {
//     return response()->json([
//         'success' => true,
//         'message' => 'stats route ok'
//     ]);
// });
Route::get('/journaliste/stats', [JournalisteStatsController::class, 'getStats']);
// partage d'article avec les bots de réseaux sociaux
Route::get('/og/article/{slug}', [OgMetaController::class, 'handle']);

Route::post('/login_admin', [AuthentificatorController::class, 'admin_authentificator']);
//AUTHENTIFICATED ABONNE
Route::post('/login_mobile_abonne', [AuthentificatorController::class, 'authentificated_abonne']);


// ADMINISTRATION
Route::post('/check_user_account', [AuthentificatorController::class, 'checkUserAccount']);
Route::post('/update_user_password', [AuthentificatorController::class, 'updateUserPassword']);


Route::post('/check_matricule', [AuthentificatorController::class, 'check_matricule']);

//Route::post('/login_mobile', [AbonneController::class, 'login_mobile']);

Route::get('/un_authorised', [AuthentificatorController::class, 'un_authorised'])->name('un_authorised');

// Routes pour l'authentification des utilisateurs

Route::prefix('auth/abonne')->group(function() {
    Route::controller(FrontendAuthentificatorController::class)->group(function () {
        Route::post('/web_authentication', 'authentification');
        Route::post('/web_check_user_account', 'processUpdateUserAccountPassword');
        Route::post('/web_check_user_otp', 'verifyUserAccountOTP');
        Route::post('/web_resend_user_otp', 'resentUserAccountOTP');
        Route::post('/web_update_user_password', 'updateUserAccountPassword');
        Route::post('/web_check_abonne_account', 'checkAbonneAccount');
    });
});

// =========================== API PARTNER NEWS =======================



Route::middleware([ApiPartnerAuth::class, DynamicThrottle::class])
->prefix('partner')
->group(function ($router) {
        // PARTNER NEWS 💚
        Route::controller(PartnerNewsController::class)->group(function () {
            // PARTNER NEWS 💚
            Route::get('/news', 'ctrl_getPartnerNews');
            Route::get('/partner_token', 'ctrl_getPartnerToken');
            Route::post('/create_partner_token', 'ctrl_createPartnerToken');
            Route::post('/update_partner_token/{slug}', 'ctrl_updatePartnerToken');
            Route::get('/delete_partner_token/{slug}', 'ctrl_deletePartnerToken');
        });
    }
);

// ===========================END API PARTNER NEWS =======================


/**
 * =========================== START QUOIDENEUF ===============================
 * ++++++++++++++++++++++++++++++ ********************* +++++++++++++++++++++++++++++++++++++++++++++
*/

// ====== RUBRIQUE QUOIDENEUF 💚====================

Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Quoideneufs',
    ],
    function ($router)
    {
        Route::controller(RubriquesQuoideneufController::class)->group(function () {
            // RUBRIQUE QUOIDENEUF 💚
            Route::get('/get_rubrique_quoideneuf',  'index');
            Route::get('/get_news_rubrique',  'get_news_rubriques');
            Route::get('/get_others_rubrique_quoideneuf',  'get_other_rubrique');
            Route::post('/add_rubrique_quoideneuf',  'store');
            Route::post('/update_rubrique_quoideneuf/{slug}',  'update');
            Route::get('/delete_rubrique_quoideneuf/{slug}',  'destroy');
        });

    }
);

// ====== GENRE JOURNALISTIQUE QUOIDENEUF 💚====================

Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Quoideneufs',
    ],
    function ($router)
    {
        Route::controller(GenreJournalistiqueController::class)->group(function () {
            // GENRE JOURNALISTIQUE QUOIDENEUF 💚
            Route::get('/get_genre_journalistique', 'index');
            Route::post('/add_genre_journalistique', 'store');
            Route::post('/update_genre_journalistique/{slug}', 'update');
            Route::get('/delete_genre_journalistique/{slug}', 'destroy');
        });
    }
);

//======= ARTICLES QUOIDENEUF  💚====================

Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Quoideneufs',
    ],
    function ($router) {
        // ARTICLES QUOIDENEUF 💚
        Route::controller(ArticlesController::class)->group(function () {


            // BACKEND
            Route::get('/get_articles', 'index');
            Route::get('/get_recente_articles', 'get_recente_article');
            Route::get('/get_customer_news/{customer}', 'get_customer_news');
            Route::get('/single_articles/{slug}', 'detail_article_on_backend');
            Route::get('/get_articles_hebdo_statistique', 'get_article_hebdo_statistique');
            Route::post('/add_articles', 'store');
            Route::post('/update_articles/{slug}', 'update');
            Route::get('/destroy_articles/{slug}', 'destroy');

            Route::get('/push_article/{slug}', 'push_article');
            Route::post('/filter_on_news', 'filter_on_news');
            Route::post('/filter_on_customer_news', 'filter_on_customer_news');
        });
    }
);


// ======= MEDIA QUOIDENUF 💚=====================

Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Quoideneufs',
    ],
    function ($router)
    {

        Route::controller(MediaController::class)->group(function () {
             // MEDIA QUOIDENUF 💚
            Route::get('/get_media', 'index');
            Route::get('/get_recente_media', 'get_recente_media');
            Route::post('/add_media', 'store');
            Route::get('/view_media/{id}', 'view');
            Route::post('/update_media/{slug}', 'update');
            Route::get('/destroy_media/{slug}', 'destroy');
            Route::post('/filter_on_media', 'filter_on_media');
        });

    }
);



/**
 *      =========================== END QUOIDENEUF ===============================
 * ++++++++++++++++++++++++++++++ ********************* +++++++++++++++++++++++++++++++++++++++++++++
 */


 //==================================== ADMIN STATISTIQUES 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Statistiques',
    ],
    function ($router)
    {
        Route::controller(AdminStatistiquesController::class)->group(function () {
            // ADMIN STATISTIQUES 💚
            Route::get('/get_default_statistiques', 'default_statistiques');
            Route::get('/get_all_journaliste', 'get_all_journaliste');
            Route::get('/get_quoideneuf_productions', 'get_quoideneuf_productions');
            Route::post('/get_filter_on_quoideneuf_productions', 'get_filter_on_quoideneuf_productions');


            Route::get('/get_alerte_info_production', 'get_alerte_info_production');
            Route::post('/get_filter_on_alerte_info_productions', 'get_filter_on_alerte_info_productions');
        });
    }
);
//==================================== END ADMIN STATISTIQUES 💚====================





/**
 *      =========================== START REDACTION ===============================
 * ++++++++++++++++++++++++++++++++ ***************** +++++++++++++++++++++++++++++++++++++++++++++
 */

//======= RUBRIQUE 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Redactions',
    ],
    function ($router)
    {

        Route::controller(RubriquesController::class)->group(function () {
            // RUBRIQUE 💚
            Route::get('/get_rubrique', 'index');
            Route::post('/add_rubrique', 'store');
            Route::post('/update_rubrique/{slug}', 'update');
            Route::get('/delete_rubrique/{slug}', 'destroy');
        });

    }
);


Route::group([

    'middleware' => 'api',
    'namespace' => 'App\Http\Controllers\API\V1\Redactions',

], function ($router)
{
    Route::controller(CountriesController::class)->group(function () {
        // PAYS 💚
        Route::get('/get_pays', 'index');
        Route::post('/add_pays', 'store');
        Route::post('/update_pays/{slug}', 'update');
        Route::get('/destroy_pays/{slug}', 'destroy');
    });
});


// ==================================== FLASHES 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Redactions',
    ],
    function ($router)
    {

        Route::controller(FlashController::class)->group(function () {
           // FLASHES 💚
            Route::get('/get_flash', 'index');
            Route::get('/get_recente_flash', 'get_recente_flash');
            Route::get('/single_flash/{slug}', 'single_flash');

            Route::post('/add_flash', 'store');
            Route::post('/update_flash/{slug}', 'update');
            Route::get('/destroy_flash/{slug}', 'destroy');
            Route::get('/push_flash/{slug}', 'push_flash');
            Route::get('/get_flash_hebdo_statistique', 'get_flash_hebdo_statistique');
            Route::post('/filter_on_flash', 'filter_on_flash');
        });
    }
);



// ====== DEPECHES 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Redactions',
    ],
    function ($router)
    {

        Route::controller(DepecheController::class)->group(function () {
            // DEPECHES 💚
            Route::get('/get_depeche', 'index');
            // get depeche customer news
            Route::get('/get_depeche_customer_news/{customer}', 'get_customer_news');
            Route::get('/get_recente_depeche', 'get_recente_depeche');
            Route::get('/get_detail_depeche_on_backend/{slug}', 'get_detail_depeche_on_backend');
            Route::get('/get_depeche_hebdo_statistique', 'get_depeche_hebdo_statistique');
            Route::post('/add_depeche', 'store');
            Route::post('/change_depeche_status/{slug}', 'pushing_depeche');
            Route::post('/update_depeche/{slug}', 'update');
            Route::get('/destroy_depeche/{slug}', 'destroy');
            Route::get('/push_depeche/{slug}', 'push_depeche');
            Route::post('/filter_depeche_on_news', 'filter_on_depeche');
            Route::post('/filter_depeche_on_customer_news', 'filter_on_customer_news');
        });

    }
);



/**
 *      =========================== END REDACTION ===============================
 * ++++++++++++++++++++++++++++++++ ***************** +++++++++++++++++++++++++++++++++++++++++++++
 */




/**
 *      =========================== START MAIN CONTENTS ===============================
 * ++++++++++++++++++++++++++++++++ ***************** +++++++++++++++++++++++++++++++++++++++++++++
 */


// ======= EVENTS KEYS WORD 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\EventsKeyWords',
    ],
    function ($router)
    {
        Route::controller(EventsKeyWordsController::class)->group(function () {
            // EVENTS KEYS WORD 💚
            Route::get('/get_event_keywords', 'get');
            Route::get('/get_news_by_event_keywords/{keywords}', 'get_news_by_event_keywords');
            Route::post('/add_event_key_words', 'store');
            Route::get('/active_event_key_words/{slug}', 'active_event_key_words');
            Route::post('/update_event_key_words/{slug}', 'update');
            Route::get('/delete_event_key_words/{slug}', 'destroy');
        });
    }
);




// ========= BANNERS 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1',
    ],
    function ($router)
    {
        Route::controller(BannerController::class)->group(function () {
            // BANNER 💚
            Route::get('/get_banner_728X90', 'get_728X90');
            Route::get('/get_banner_1920X309', 'get_1920X309');
            Route::get('/get_banner_1200X1500', 'get_1200X1500');
            Route::get('/get_on_backend', 'index');
            Route::post('/add_banner', 'store');
            Route::post('/update_banner/{slug}', 'update');
            Route::get('/delete_banner/{slug}', 'destroy');
            Route::get('/enable_or_disable_banner/{slug}', 'enable_or_disable_banner');
        });
    }
);


/**
 *      =========================== END MAIN CONTENTS ===============================
 * ++++++++++++++++++++++++++++++++ ***************** +++++++++++++++++++++++++++++++++++++++++++++
 */


/**
 *      =========================== START DIFFUSION SMS ===============================
 * ++++++++++++++++++++++++++++++++ ***************** +++++++++++++++++++++++++++++++++++++++++++++
 */

//======= CARNET ADDRESS 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\DiffusionSms',
    ],
    function ($router)
    {

        Route::controller(CarnetAdressController::class)->group(function () {
            // SINGLE CARNET ADDRESS 💚
            Route::get('/get_all_group_contact', 'index_group_contact');
            Route::post('/store_group_contact', 'store_group_contact');
            Route::post('/update_group_contact/{id}', 'update_group_contact');
            Route::get('/delete_group_contact/{id}', 'destroy_group_contact');

            // GROUP CARNET ADDRESS 💚
            Route::get('/get_all_single_contact', 'index_single_contact');
            Route::post('/store_single_contact', 'store_single_contact');
            Route::post('/update_single_contact/{id}', 'update_single_contact');
            Route::get('/delete_single_contact/{id}', 'destroy_single_contact');

            // FILE CARNET ADDRESS 💚
            Route::post('/store_document_contact', 'store_document_contact');
        });

    }
);


//======= GESTION OPERATOR 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\DiffusionSms'
    ],
    function ($router)
    {

        Route::controller(GestionOperatorController::class)->group(function () {

            // GESTION OPERATOR 💚
            Route::get('/get_all_operator', 'index');
            Route::post('/store_operator', 'store');
            Route::post('/update_operator/{id}', 'update');
            Route::get('/delete_operator/{id}', 'destroy');
            Route::get('/check_status_operator/{id}', 'check_status');
        });

    }
);

/**
 *      =========================== END DIFFUSION SMS ===============================
 * ++++++++++++++++++++++++++++++++ ***************** +++++++++++++++++++++++++++++++++++++++++++++
 */




/**
 *      =========================== START USER ACCOUNTS ===============================
 * ++++++++++++++++++++++++++++++ ********************* +++++++++++++++++++++++++++++++++++++++++++++
 */







// ==================================== COMPTE ADMIN 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\UsersAccounts',
    ],
    function ($router)
    {
        Route::controller(AdministrationController::class)->group(function () {
            // COMPTE ADMIN 💚
            Route::get('get_admin', 'index');
            Route::post('/add_admin', 'store');
            Route::get('/show_admin/{slug}', 'show');
            Route::post('/update_admin/{slug}', 'update');
            Route::get('/delete_admin/{slug}', 'destroy');
            Route::get('/enable_or_disable_admin_account/{slug}', 'enable_or_disable_account');
            Route::post('/update_administration_photo', 'update_administration_photo');
        });

    }

);

/**
 *      =========================== END USER ACCOUNTS ===============================
 * ++++++++++++++++++++++++++++++++ ***************** +++++++++++++++++++++++++++++++++++++++++++++
 */







// ==================================== LOGOUT 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Auth',
    ],
    function ($router)
    {
        // LOGOUT 💚
        Route::get('/logout_users/{user_id}', [AuthentificatorController::class, 'logout']);
    }
);














// ==================================== ROLES 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1',
    ],
    function ($router)
    {
        // ROLES 💚
        Route::get('/get_role', [RoleController::class, 'get']);
        Route::post('/add_role', [RoleController::class, 'add']);
        Route::post('/update_role/{slug}', [RoleController::class, 'update']);
        Route::get('/delete_role/{slug}', [RoleController::class, 'destroy']);


    }
);



 // ======= GALERIE 💚=====================

Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Galeries',
    ],
    function ($router)
    {

        Route::controller(GalerieController::class)->group(function () {
             // GALERIE 💚
            Route::get('/get_galerie', 'index');
            Route::get('/get_galerie_limited', 'get_galerie_limited');
            Route::get('/get_recente_galerie', 'get_recente_galerie');
            Route::get('/checked_img/{query}', 'check');
            Route::post('/add_galerie', 'store');
            Route::get('/view_galerie/{id}', 'view');
            Route::post('/update_galerie/{slug}', 'update');
            Route::get('/destroy_galerie/{slug}', 'destroy');
            Route::get('/filter_on_galerie/{query}', 'filter_on_galerie');
        });

    }
);



// ==================================== FORFAITS 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Abonnements',
    ],
    function ($router)
    {
        // FORFAITS 💚
        Route::get('/get_forfaits', [ForfaitsAbonnementController::class, 'index']);
        Route::post('/add_forfaits', [ForfaitsAbonnementController::class, 'store']);
        Route::get('/edit_forfaits/{slug}', [ForfaitsAbonnementController::class, 'edit']);
        Route::post('/update_forfaits/{slug}', [ForfaitsAbonnementController::class, 'update']);
        Route::get('/destroy_forfaits/{slug}', [ForfaitsAbonnementController::class, 'destroy']);

    }
);



// ==================================== FORFAITS 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\AbonneMobiles',
    ],
    function ($router)
    {
        // FORFAITS 💚
        Route::get('/get_forfaits_mobile', [ForfaitAbonnementsMobileController::class, 'index']);
        Route::post('/add_forfaits_mobile', [ForfaitAbonnementsMobileController::class, 'store']);
        Route::get('/edit_forfaits_mobile/{slug}', [ForfaitAbonnementsMobileController::class, 'edit']);
        Route::post('/update_forfaits_mobile/{slug}', [ForfaitAbonnementsMobileController::class, 'update']);
        Route::get('/destroy_forfaits_mobile/{slug}', [ForfaitAbonnementsMobileController::class, 'destroy']);

    }
);


// ==================================== ABONNE 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Abonnements',
    ],
    function ($router)
    {
        Route::controller(AbonnesController::class)->group(function () {

            // ABONNE 💚
            Route::get('/get_local_abonne_mobile', 'index');
            Route::get('/profil_local_abonne_mobile/{id}', 'profil_abonne');
            Route::post('/store_local_abonne_mobile', 'store');
            Route::get('/edit_local_abonne_mobile/{slug}', 'edit');
            Route::put('/update_local_abonne_mobile/{slug}', 'update');
            Route::post('/reset_password', 'reset_password');
            Route::post('/find_local_abonne_mobile', 'find_email');


            //Route::get('/destroy_abonne/{id}/{author}', [AbonneController::class, 'destroy']);
        });

    }
);

// ==================================== ABONNE 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\AbonneMobiles',
    ],
    function ($router)
    {
        Route::controller(AbonneMobileController::class)->group(function () {

            // ABONNE 💚
            Route::get('/get_abonne_mobile', 'index');
            Route::get('/profil_abonne/{id}', 'profil_abonne');
            Route::post('/add_abonne_mobile', 'store');
            Route::get('/edit_abonne_mobile/{id}', 'edit');
            Route::put('/update_abonne_mobile', 'update');
            Route::post('/reset_password_mobile', 'reset_password');
            Route::post('/find_abonn_mobilee', 'find_email');


            //Route::get('/destroy_abonne/{id}/{author}', [AbonneController::class, 'destroy']);
        });

    }
);



// ==================================== ABONNEMENT MOBILE 💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Abonnements',
    ],
    function ($router)
    {
        Route::controller(AbonnementsController::class)->group(function () {
            // ABONNEMENT 💚
            Route::get('/get_abonnements_mobile', 'index');
            Route::get('/get_abonnement_actif_mobile', 'get_abonnement_actif');
            Route::post('/create_local_abonnements_mobile', 'create_local_abonnements_mobile');
            Route::get('/edit_abonnement_mobile/{id}', 'edit');
            Route::post('/update_abonnement_mobile/{slug}', 'update');
            Route::get('/destroy_abonnement_mobile/{slug}', 'destroy');
            Route::get('/lock_abonnement_mobile/{id}/{author}', 'lock_abonnement');
            Route::get('/unlock_abonnement_mobile/{id}/{author}', 'unlock_abonnement');
            Route::put('/reabonnement_mobile', 'reabonnement');
            Route::put('/update_abonnements_mobile_validation_date', '__update_abonnements_validation_date');
            Route::get('/filter_by_abonnements_mobile_payment/{payments}', 'filter_by_abonnements_payment');
        });
    }
);

// =================================== END ABONNEMENT MOBILE 💚====================


//********************************** WEBSITE PAGE ROUTE **************************** */

Route::group([

    'middleware' => 'api',
    'namespace' => 'App\Http\Controllers\API\V1\AlerteInfoWebSite',

], function ($router)
{
    Route::controller(AboutsController::class)->group(function () {
        // Abouts 💚
        Route::get('/get_abouts_data', 'getAboutsData');
        Route::post('/store_abouts_data', 'storeAboutData');
        Route::post('/update_abouts_data/{slug}', 'updateAboutData');
        Route::delete('/destroy_abouts_data/{slug}', 'deleteAboutData');
    });
});


Route::group([

    'middleware' => 'api',
    'namespace' => 'App\Http\Controllers\API\V1\AlerteInfoWebSite',

], function ($router)
{
    Route::controller(OurBlogController::class)->group(function () {
        // OurBlog 💚
        Route::get('/get_blog_data', 'getBlogsData');
        Route::get('/get_blog_details/{slug}', 'getBlogDetails');
        Route::post('/store_blog_data', 'storeBlogsData');
        Route::post('/update_blog_data/{slug}', 'updateBlogsData');
        Route::delete('/destroy_blog_data/{slug}', 'deleteBlogsData');
    });
});

Route::group([

    'middleware' => 'api',
    'namespace' => 'App\Http\Controllers\API\V1\AlerteInfoWebSite',

], function ($router)
{
    Route::controller(OurContactsController::class)->group(function () {
        // OurContacts 💚
        Route::get('/get_contacts_data', 'getContactsData');
        Route::post('/store_contacts_data', 'storeContactsData');
        Route::post('/update_contacts_data/{slug}', 'updateContactsData');
        Route::delete('/destroy_contacts_data/{slug}', 'deleteContactsData');
    });
});


Route::group([

    'middleware' => 'api',
    'namespace' => 'App\Http\Controllers\API\V1\AlerteInfoWebSite',

], function ($router)
{
    Route::controller(OurReferencesController::class)->group(function () {
        // References 💚
        Route::get('/get_reference_data', 'getReferencesData');
        Route::post('/store_reference_data', 'storeReferencesData');
        Route::post('/update_reference_data/{slug}', 'updateReferencesData');
        Route::delete('/destroy_reference_data/{slug}', 'deleteReferencesData');
    });
});


Route::group([

    'middleware' => 'api',
    'namespace' => 'App\Http\Controllers\API\V1\AlerteInfoWebSite',

], function ($router)
{
    Route::controller(OurServicesController::class)->group(function () {
        // Services 💚
        Route::get('/get_service_data', 'getServicesData');
        Route::get('/get_service_details/{slug}', 'getServicesDetails');
        Route::post('/store_service_data', 'storeServicesData');
        Route::post('/update_service_data/{slug}', 'updateServicesData');
        Route::delete('/destroy_service_data/{slug}', 'deleteServicesData');
    });
});

//********************************** END WEBSITE PAGE ROUTE **************************** */



//======= FRONTEND ARTICLES QUOIDENEUF  💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Frontend',
    ],
    function ($router) {
        // ARTICLES QUOIDENEUF 💚
        Route::controller(FrontendQuoideneufController::class)->group(function () {

            // FRONTEND



            Route::get('/get_quoideneuf_home_data',  'get_quoideneuf_home_data');
            Route::get('/get_quoideneuf_footer_data',  'get_quoideneuf_home_data');
            Route::get('/get_frontend_all_archive_data',  'get_frontend_all_archive_data');

            Route::get('/get_frontend_all_current_date_news_data', 'get_frontend_all_current_date_news_data');

            Route::get('/get_frontend_news_rubrique',  'get_frontend_news_rubriques');

            Route::get('/get_frontend_news_details/{slug}', 'get_frontend_news_details');


            Route::get('/get_frontend_others_rubrique_quoideneuf',  'get_frontend_other_rubrique');

            //Route::get('/get_frontend_une_news_article', 'get_frontend_une_news_article');
            Route::get('/get_frontend_archive_article', 'get_frontend_archive_article');
            //Route::get('/get_frontend_politique_article', 'get_frontend_politique_article');
            //Route::get('/get_frontend_economie_article', 'get_frontend_economie_article');
            //Route::get('/get_frontend_popular_article', 'get_frontend_popular_article');
            Route::get('/get_frontend_media_news', 'get_frontend_media_news');
            Route::get('/get_frontend_article_with_rubrique/{slug}', 'get_frontend_article_with_rubrique');
            //Route::get('/get_frontend_flash_info', 'get_frontend_flash_info');
            Route::get('/get_frontend_articles_archive', 'get_frontend_articles_archive');
            Route::get('/get_frontend_archive_data/{mounth}/{year}', 'get_frontend_archive_data');
            //Route::get('/get_frontend_similar_article/{rubrique_id}/{slug}', 'get_frontend_similar_article');

            //Route::post('/like_frontend_news', 'like_frontend_news');
            Route::get('/get_frontend_media', 'get_frontend_media');
            //Route::post('/dislike_frontend_news', 'dislike_frontend_news');

            Route::get('/get_frontend_event_keywords', 'get_frontend_event_keywords');
            Route::get('/get_frontend_news_by_event_keywords/{keywords}', 'get_frontend_news_by_event_keywords');

            // search
            Route::get('/filter_on_news_with_query/{query}', 'filter_on_news_with_query');

            Route::get('/get_frontend_banner_728X90', 'get_frontend_728X90');
            Route::get('/get_frontend_banner_1920X309', 'get_frontend_1920X309');
            Route::get('/get_frontend_banner_1200X1500', 'get_frontend_1200X1500');

            // Route::any('/notify', [App\Http\Controllers\API\V1\Transactions\NotifyController::class, 'notify'])->name('paiement-notify');

            //********************************************* NOUVELLE VERSION QUOIDENEUF *******************/

            // ROUTE HOME DATA
            Route::get('/get_v2_quoideneuf_home_data', 'ctrl_getQuoideneufHomeData');

            // ROUTE NEWS DETAILS DATA
            Route::get('/get_v2_quoideneuf_news_details/{slug}', 'ctrl_getNewsDetails');

            // ROUTE NEWS BY RUBRIQUE DATA
            Route::get('/get_v2_quoideneuf_news_by_rubrique/{slug}', 'ctrl_getNewsByRubrique');

            // COUNTRY LIST
            Route::get('/get_v2_quoideneuf_country_list', 'ctrl_getCountryList');

            // BANNER
            Route::get('/get_v2_quoideneuf_banner_728X90', 'ctrl_getBanner728X90');
            Route::get('/get_v2_quoideneuf_banner_1920X309', 'ctrl_getBanner1920X309');
            Route::get('/get_v2_quoideneuf_banner_1200X1500', 'ctrl_getBanner1200X1500');

            // ROUTE POPULAR NEWS
            Route::get('/get_v2_quoideneuf_popular_news', 'ctrl_getPopularNews');

            // video route
            Route::get('/get_v2_quoideneuf_video', 'ctrl_getVideo');

            // ROUTE ARCHIVE
            Route::get('/get_v2_quoideneuf_archive', 'ctrl_getArchive');

            // ROUTE POUR FILTRER LES NEWS
            Route::get('/get_v2_quoideneuf_filter_news', 'ctrl_getFilterNews');

            // ROUTE POUR CREER UN PIGISTE
            Route::post('/create_v2_quoideneuf_pigiste', 'ctrl_createPigiste');

            // ROUTE POUR CHARGER LES RUBRIQUES
            Route::get('/get_v2_quoideneuf_rubrique_list', 'ctrl_getRubriqueList');

            // ROUTE POUR CHARGER LA BANNIERE 1200X1500
            Route::get('/get_v2_quoideneuf_banner_1200X1500', 'ctrl_getBanner1200X1500');




        });
        Route::get('/footer-settings', [FooterSettingController::class, 'show']);
        Route::put('/update-footer-settings', [FooterSettingController::class, 'update']);
    }
);


// ===========================END FRONTEND ARTICLES QUOIDENEUF ========================




//======= FRONTEND ARTICLES ARLERTE INFO  💚====================
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Frontend',
    ],
    function ($router) {
        // ARTICLES ARLERTE INFO 💚
        Route::controller(FrontendAlerteInfoController::class)->group(function () {

            // FRONTEN
            //// get web abonnement categorie
            Route::get('/get_alerteinfo_web_abonnement_categorie',  'get_alerteinfo_web_abonnement_categorie');

            // get_alerteinfo_web_abonnement_form_data
            Route::get('/get_alerteinfo_web_abonnement_form_data/{slug}',  'get_alerteinfo_web_abonnement_form_data');

            //store alerteinfo web abonne data
            Route::post('/store_alerteinfo_web_abonne_data',  'store_alerteinfo_web_abonne_data');

            // store alerte-info web abonnement data
            Route::post('/store_alerteinfo_web_abonnement_data',  'store_alerteinfo_web_abonnement_data2');

            // get alerte-info w    eb abonnement data
            Route::get('/get_alerteinfo_web_abonnement_data/{account_code_unique}',  'checkSubscriberSubscriptionData');

            // get alerte-info web about data
            Route::get('/get_alerteinfo_web_about_data',  'get_alerteinfo_web_about_data');
            // get alerte-info web our_contacts
            Route::get('/get_alerteinfo_web_our_contacts_data',  'get_alerteinfo_web_our_contacts_data');
            // get alerte-info web our_reference
            Route::get('/get_alerteinfo_web_our_reference_data',  'get_alerteinfo_web_our_reference_data');
            // get alerte-info web our_blog
            Route::get('/get_alerteinfo_web_our_blog_data',  'get_alerteinfo_web_our_blog_data');
            // get_alerteinfo_web_blog_detail
            Route::get('/get_alerteinfo_web_blog_detail/{slug}',  'get_alerteinfo_web_blog_detail');
            // get alerte-info web our_services
            Route::get('/get_alerteinfo_web_our_service_data',  'get_alerteinfo_web_our_services_data');


            // store alerte-info web app fcm tokens
            Route::post('/store_alerteinfo_webapp_fcm_tokens',  'storeAlerteInfoWebappFcmTokens');
            Route::post('/delete_alerteinfo_webapp_fcm_token',  'deleteAlerteInfoWebappFcmTokens');

            // test FCM Notification
            Route::post('/send_fcm_notifications',  'sendFCMNotification');

        });


        Route::middleware([VerifyJwtToken::class])
        ->group(function () {
            Route::controller(FrontendAlerteInfoController::class)->group(function () {
                Route::get('/get_alerteinfo_home_page_data/{account_code_unique}', 'get_alerteinfo_home_page_data');
                // get alerte-info
                Route::get('/get_alerteinfo_news_details/{item_slug}',  'get_alerteinfo_news_details');
                // get alerte-info depeche archives
                Route::get('/get_alerteinfo_depeche_archives',  'get_alerteinfo_depeche_archives');
                // get alerte-info depeche archives data
                Route::get('/get_alerteinfo_depeche_archives_data_by_mounth_and_year/{mounth}/{year}', 'get_alerteinfo_depeche_archives_data_by_mounth_and_year');

                // get alerte-info web depeche filtered data
                Route::get('/get_alerteinfo_web_depeche_filtered_data/{account_code_unique}/{query}',  'getAlerteinfoWebDepecheFilteredData');


                // get alerte-info depeche by country
                Route::get('/get_alerteinfo_depeche_by_country/{country_id}', 'get_alerteinfo_depeche_by_country');
                // get alerte-info depeche by rubrique
                Route::get('/get_alerteinfo_depeche_by_rubrique/{rubrique_slug}', 'get_alerteinfo_depeche_by_rubrique');

                // get alerte-info depeche open access data
                Route::get('/get_alerteinfo_depeche_open_access_data',  'get_alerteinfo_depeche_open_access_data');
                // get alerte-info depeche archives data
                Route::get('/get_alerteinfo_depeche_archives_data',  'get_alerteinfo_depeche_archives_data');


                //get_alerteinfo_web_abonne_dashboard_data
                Route::get('/get_alerteinfo_web_abonne_dashboard_data/{account_code_unique}',  'getAlerteinfoWebAbonneDashboardData');

                // logout
                Route::post('/logout_alerteinfo_web', 'logoutAlerteInfoWeb');

            });
        });
    }
);

Route::post('/geniuspay/webhook', [GeniusPayWebhookController::class, 'handleWebhook'])->name('geniuspay.webhook');
Route::get('/geniuspay/webhook', function () {
    return response()->json([
        'success' => true,
        'message' => 'Bienvenue au webhook GeniusPay'
    ]);
});
Route::any('/cinetpay/notify', [CinetPayNotifyController::class, 'cinetNotify']);

//======= FRONTEND MOBILE CONTROLLER  💚====================FrontendMobileController
Route::group(
    [
        'middleware' => 'api',
        'namespace' => 'App\Http\Controllers\API\V1\Frontend',
    ],
    function ($router) {
        // MOBILE CONTROLLER 💚
        Route::controller(FrontendMobileController::class)->group(function () {

            // FRONTEND


            Route::post('/get_mobile_recents_depeches_and_flashes', 'get_mobile_recents_depeches_and_flashes');

            // DEPECHE REQUEST
            Route::get('/get_mobile_depeches',  'get_mobile_depeches');
            Route::get('/get_mobile_depeche_details_by_slug/{item_slug}/{paysId}',  'get_mobile_depeche_details_by_slug');
            Route::get('/get_mobile_depeche_by_rubrique/{rubrique_id}',  'get_mobile_depeche_by_rubrique');
            Route::get('/get_mobile_depeche_by_customer_country/{country_id}',  'get_mobile_depeche_by_country');
            Route::get('/get_mobile_depeche_archives', 'get_mobile_depeche_archives');
            Route::get('/get_mobile_depeche_archives_data/{mounth}/{year}', 'get_mobile_depeche_archives_data');

            // FLASHES REQUEST
            Route::get('/get_mobile_flashes', 'get_mobile_flashes');
            Route::get('/get_mobile_recents_flashes', 'get_mobile_recents_flashes');
            Route::get('/get_mobile_flashes_by_rubrique/{rubrique_id}', 'get_mobile_flashes_by_rubrique');
            Route::get('/get_mobile_flashes_by_country/{country_id}', 'get_mobile_flashes_by_country');

            Route::get('/get_mobile_flashes_archives', 'get_mobile_flashes_archives');
            Route::get('/get_mobile_flashes_archives_data/{mounth}/{year}', 'get_mobile_flashes_archives_data');

            Route::get('/get_mobile_countries_list', 'get_mobile_countries_list');


            Route::get('/get_mobile_banner_728X90', 'get_mobile_banner_728X90');

            Route::get('/get_mobile_countries', 'get_mobile_countries');
            Route::get('/get_mobile_rubriques', 'get_mobile_rubriques');



            Route::get('/get_mobile_forfait_abonnements', 'get_mobile_forfait_abonnements');

            /// ABONNE MOBILE AND ABONNEMENTS

            Route::post('/store_mobile_abonne_data', 'store_mobile_abonne_data');
            Route::post('/store_mobile_abonnement_data', 'store_mobile_abonnement_data');

            Route::get('/forfaits_list', 'forfaitsList');
            Route::post('/add_user_abonnement', 'store_abonne_abonnements2');
            Route::post('/confirm_geniuspay_mobile_payment', 'confirmGeniusPayMobilePayment');
            // Route::any('/notify', 'notify');

            // Route pour obtenir les détails d'un abonnement via le code d'abonnement
            Route::get('/abonnement/details/{abonnement_code}','getAbonnementDetails');

            // Route pour obtenir les détails du dernier abonnement valide ou le plus récent pour l'utilisateur authentifié
            Route::get('/get-current-abonnement', 'getLatestAbonnementDetails');
            // Route::post('/update-device-token', action: 'updateDeviceToken');

            Route::put('/update-profile', 'updateProfile');
            Route::put('/change-password',  'changePassword');

            Route::post('/report-problem',  'storeProblemeReport');
            Route::get('/list-report-problem', 'listProblemeReport');
            Route::get('/report-problem/{id}', 'showProblemeReport');

            Route::post('/contact-form', 'storeContactForm');

        });
    }
);

Route::post('/check-session', [AuthentificatorController::class, 'checkSession']);

Route::post('/password/mobile-reset', [AuthentificatorController::class, 'sendResetLinkEmailByMobile']);


Route::any('/notify', [FrontendMobileController::class, 'notify']);
Route::post('/send-notifications', [FrontendMobileController::class, 'sendNotificationsToAllUsers']);

Route::post('/update-device-token',action: [FrontendMobileController::class, 'updateDeviceToken'] );

Route::post('/logout-device',  [FrontendMobileController::class, 'logoutDevice']);


Route::get('/search_mobile_depeches', [FrontendMobileController::class, 'search_mobile_depeches']);

Route::get('/contact-info', function () {
    return response()->json([
        'phone' => '+225 01 02 500 320 / +225 07 09 62 06 06',
        'whatsapp' => '+225 01 02 500 320',
        'email' => 'direction@alerte-info.net',
        'facebook' => 'https://web.facebook.com/ALERTEINFOCIV'
    ]);
});

Route::post('/notifications-list', [FrontendMobileController::class, 'getUserNotifications']);

Route::post('/get-archives-data', [FrontendMobileController::class, 'getDepechesAndFlashesForMonth']);

Route::post('search-depeches', [FrontendMobileController::class, 'searchDepeches']);

Route::post('search-flashes', [FrontendMobileController::class, 'searchFlashes']);

Route::post('/send_otp', [AuthentificatorController::class, 'sendOTP']);
Route::post('/verify-otp', [AuthentificatorController::class, 'verifyOTP']);
Route::post('/reset-password-with-otp', [AuthentificatorController::class, 'resetPasswordWithOTP']);

// ===========================END FRONTEND DEPECHES ========================





// ********************************************* NOUVELLE VERSION ROUTING ALERTE INFO REDACTION *******************/

Route::prefix('admin')->group(function ($router) {

    // *************************** ROUTE POUR LES ABONNEMENTS MOBILE ***************************
    Route::prefix('/abonnement_mobile')->group(function ($router) {
        Route::controller(AbonnementsController::class)->group(function () {
            // get abonnement mobile data
            Route::get('/get_abonnement_mobile', 'ctrl_getAbonnementMobile');
            // get abonnement mobile form data
            Route::get('/get_abonnement_mobile_form_data', 'ctrl_getAbonnementMobileFormData');
            // store abonnement mobile data
            Route::post('/create_local_abonnement_mobile', 'ctrl_createLocalAbonnementMobile');
            // update abonnement mobile data
            Route::post('/update_abonnement_mobile_validity_date/{slug}', 'ctrl_updateAbonnementMobileValidityDate');
            // delete abonnement mobile data
            Route::delete('/delete_abonnement_mobile/{slug}', 'ctrl_deleteAbonnementMobile');
            // restore abonnement mobile data
            Route::delete('/restore_abonnement_mobile/{slug}', 'ctrl_restoreAbonnementMobile');
        });
    });


    // *************************** ROUTE POUR LES ABONNEMENTS WEB SITE ***************************
    Route::prefix('/abonnement_web')->group(function ($router) {
        Route::controller(AbonnementWebController::class)->group(function () {
            // get abonnement web site data
            Route::get('/get_abonnement_web_site', 'ctrl_getAbonnementWebSite');
            // get abonnement web form data
            Route::get('/get_abonnement_web_form_data', 'ctrl_getAbonnementWebFormData');
            // store abonnement web site data
            Route::post('/store_abonnement_web_site', 'ctrl_storeAbonnementWebSite');
            // update abonnement web site data
            Route::put('/update_abonnements_web_validation_date', 'ctrl_updateAbonnementWebValidityDate');
            Route::post('/update_abonnement_web_site/{slug}', 'ctrl_updateAbonnementWebSite');
            // delete abonnement web site data
            Route::delete('/delete_abonnement_web_site/{slug}', 'ctrl_deleteAbonnementWebSite');
            // restore abonnement web site data
            Route::delete('/restore_abonnement_web_site/{slug}', 'ctrl_restoreAbonnementWebSite');
        });
    });


    // *************************** ROUTE POUR LES ABONNES WEB SITE ***************************
    Route::prefix('abonne_web')->group(function ($router) {
        Route::controller(AbonnesWebController::class)->group(function () {
            // get abonne web site data
            Route::get('/get_abonne_web_site', 'ctrl_getAbonneWebSite');
            // get abonne web category
            Route::get('/abonne_web_categorie', 'ctrl_getAbonneWebCategory');
            // store abonne web site data
            Route::post('/store_abonne_web_site', 'ctrl_storeAbonneWebSite');
            // update abonne web site data
            Route::post('/update_abonne_web_site/{slug}', 'ctrl_updateAbonneWebSite');
            // delete abonne web site data
            Route::delete('/delete_abonne_web_site/{slug}', 'ctrl_deleteAbonneWebSite');
            // restore abonne web site data
            Route::delete('/restore_abonne_web_site/{slug}', 'ctrl_restoreAbonneWebSite');
        });
    });

    // *************************** ROUTE POUR LES ABONNES MOBILE ***************************
    Route::prefix('abonne_mobile')->group(function ($router) {
        Route::controller(AbonnesController::class)->group(function () {
            // get abonne mobile data
            Route::get('/get_abonne_mobile', 'ctrl_getAbonneMobile');
            // store abonne mobile data
            Route::post('/store_abonne_mobile', 'ctrl_storeAbonneMobile');
            // update abonne mobile data
            Route::post('/update_abonne_mobile/{slug}', 'ctrl_updateAbonneMobile');
            // delete abonne mobile data
            Route::delete('/delete_abonne_mobile/{slug}', 'ctrl_deleteAbonneMobile');
            // restore abonne mobile data
            Route::delete('/restore_abonne_mobile/{slug}', 'ctrl_restoreAbonneMobile');
        });
    });

    // *************************** ROUTE POUR LES CATEGORIE WEB ***************************
    Route::prefix('categorie_abonne_web')->group(function ($router) {
        Route::controller(CategoriesAbonnementWebController::class)->group(function () {
            // get categorie web site data
            Route::get('/get_categorie_abonne_web_list', 'ctrl_getCategorieAbonneWebSite');
            // store categorie web site data
            Route::post('/create_categorie_abonne_web_site', 'ctrl_createCategorieAbonneWebSite');
            // update categorie web site data
            Route::post('/update_categorie_abonne_web_site/{slug}', 'ctrl_updateCategorieAbonneWebSite');
            // delete categorie web site data
            Route::delete('/destroy_categorie_abonne_web_site/{slug}', 'ctrl_destroyCategorieAbonneWebSite');
            // restore categorie web site data
            Route::delete('/restore_categorie_abonne_web_site/{slug}', 'ctrl_restoreCategorieAbonneWebSite');
        });
    });


    // *************************** ROUTE POUR LES FORFAITS ABONNEMENTS WEB ***************************
    Route::prefix('forfait_abonnement_web')->group(function ($router) {
        Route::controller(ForfaitAbonnementWebController::class)->group(function () {
            // get forfait abonnement web site data
            Route::get('/get_forfait_abonnement_web_list', 'ctrl_getForfaitAbonnementWebSite');
            // get forfait abonnement web site form data
            Route::get('/get_forfait_abonnement_web_form_data', 'ctrl_getForfaitAbonnementWebSiteFormData');
            Route::get('/get_all_forfait_abonnement_web_list', 'ctrl_getForfaitAbonnementWebSiteFormData2');
            // store forfait abonnement web site data
            Route::post('/store_forfait_abonnement_web_site', 'ctrl_storeForfaitAbonnementWebSite');
            // update forfait abonnement web site data
            Route::post('/update_forfait_abonnement_web_site/{slug}', 'ctrl_updateForfaitAbonnementWebSite');
            // delete forfait abonnement web site data
            Route::delete('/delete_forfait_abonnement_web_site/{slug}', 'ctrl_deleteForfaitAbonnementWebSite');
            // restore forfait abonnement web site data
            Route::delete('/restore_forfait_abonnement_web_site/{slug}', 'ctrl_restoreForfaitAbonnementWebSite');
        });
    });




});
