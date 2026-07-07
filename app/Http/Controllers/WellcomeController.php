<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WellcomeController extends Controller
{

    public string $BASE_URL = "https://api-alerteinfo.alerteinfo-mairie.com/api/v1/";

    public function index()
    {
        
        return response()->json(
            [
                "app_name" => env('APP_NAME'),
                "message" => "Bienvenue à ".env('APP_NAME'),
                "sujet"   => "API REST",
                'BASE_URL' => "https://api-alerteinfo.alerteinfo-mairie.com/api/v1/",


                
                
                'MOBILE APP  ROUTING' => [

                    //login abonne
                    'login_mobile_abonne' => [
                        'description' => "Authentifie un abonné mobile",
                        'url'         => $this->BASE_URL.'login_mobile_abonne',
                        'method'         => 'POST',
                        'form_data_fields' => [
                            'email',
                            'password',
                        ],
                        'response' => [
                            'nom' => "//abonne_fname",
                            'prenom' => "//abonne_lname",
                            'email' => "//abonne_email",
                            'contact' => "//abonne_phone_number",
                            'account_id' => "//id",
                            'token' => "//token",
                            'token_type' => "Bearer",
                            'expires_in' => "2592000 secondes == 30Jours"
                        ]
                    ],



                    // store abonné abonnement data to database
                    'store_mobile_abonnement_data' => [
                        'description' => "Crée un nouvel abonnement pour un abonné",
                        'url'         => $this->BASE_URL.'store_mobile_abonnement_data',
                        'method'         => 'POST',
                        'form_data_fields' => [
                            'forfait_id',
                            'country_id',
                            'abonne_id',
                        ],
                        "// si code == 200 alors il renvoit un data contenant les informations de l'abonnement",
                        'abonnement_data' => [
                            'abonnement_code' => "//abonnement_code",
                            'abonne_fname' => "//abonne_fname",
                            'abonne_lname' => "//abonne_lname",
                            'abonne_email' => "//abonne_email",
                            'abonne_contact' => "//abonne_phone_number",
                            'montant_abonnement' => "//montant_abonnements"
                        ],
                    ],
                    
                    

                    // store mobile abonné data in database

                    'store_mobile_abonne_data' => [

                        'description' => "Crée un nouvel utilisateur pour un abonné mobile",
                        'url'         => $this->BASE_URL.'store_mobile_abonne_data',
                        'method'         => 'POST',
                        'form_data_fields' => [
                            'nom',
                            'prenom',
                            'contact',
                            'email',
                            'password',
                            'password_confirmation',
                            'device_info',
                        ],
                        'response' => [
                            'nom' => "//store_abonne->abonne_fname",
                            'prenom' => "//store_abonne->abonne_lname",
                            'email' => "//store_abonne->abonne_email",
                            'contact' => "//store_abonne->abonne_phone_number",
                            'account_id' => "//store_abonne->id",
                            'token' => "//token",
                            'token_type' => "Bearer",
                            'expires_in' => "2592000 secondes == 30Jours"
                        ]
                    ],


                    'get_mobile_recents_depeches_and_flashes' => [


                        'description' => "Retourne la liste complète des dépêches et flashes publies selon l'abonnement de l'utilisateur",
                        'url'         => $this->BASE_URL.'get_mobile_recents_depeches_and_flashes',
                        'method'         => 'POST',

                        'form_data_fields' => [
                            'customer_countries_id',


                            "/////// La variable peut avoir comme valeur 'undifined' si l'utilisateur n'est pas connecté.//////////",
                            "/////// Mais lorsqu'il est connecté, elle prend sa vrai valeur et retournera les données en fonction d'elle////////",
                        ],


                        '///// En cas de connexion ////////',
                        'authorization' => [
                            'Authorization' => 'Bearer {token}', 
                            '////// replace {token} with actual token ////////'
                        ],
                    ],



                    //

                    'get_mobile_depeche_details_by_slug' => [

                        "// Peut être utilisé pour afficher le detail de la dépêche ///////",
                        'description' => 'Retourne les détails d\'une dépêche par son slug',
                        'url'         => $this->BASE_URL.'get_mobile_depeche_details_by_slug/{item_slug}',
                        'method'         => 'GET',
                        'form_data_fields' => [
                            'item_slug',
                        ],
                        "// En cas de connexion /////////",
                        'authorization' => [
                            'Authorization' => 'Bearer {token}', // replace {token} with actual token
                        ],
                    ],


                    'get_mobile_depeches' => [

                        'description' => 'Retourne la liste complète des dépêches',
                        'url'         => $this->BASE_URL.'get_mobile_depeches',
                        'type'         => 'GET',
                    ],

                    'get_mobile_flashes' => [

                        'description' => 'Retourne la liste complète des flashes',
                        'url'         => $this->BASE_URL.'get_mobile_flashes',
                        'type'         => 'GET',
                    ],


                    'get_mobile_countries_list' => [ 
                        
                        // When users are not logged in
                        'description' => 'Retourne la liste complète des pays',
                        'url'         => $this->BASE_URL.'get_mobile_countries_list',
                        'method'         => 'GET',
                    ],


                    'get_mobile_customer_countries_list' => [ 
                        
                        // When users are  logged in
                        'description' => 'Retourne la liste complète des pays',
                        'url'         => $this->BASE_URL.'get_mobile_customer_countries_list',
                        'method'         => 'POST',
                        'form_data_fields' => [
                            'customer_countries_id',
                        ],

                        // En cas de connexion
                        'authorization' => [
                            'Authorization' => 'Bearer {token}', // replace {token} with actual token
                        ],
                    ],



                    'get_mobile_rubriques' => [
                        'description' => 'Retourne la liste complète des rubriques',
                        'url'         => $this->BASE_URL.'get_mobile_rubriques',
                        'type'         => 'GET',
                    ],



                    'get_mobile_banner_728X90' => [
                        'description' => 'Retourne le bannière 728x90',
                        'url'         => $this->BASE_URL.'get_mobile_banner_728X90',
                        'type'         => 'GET',
                    ],


                ],

                'FRONTEND APP ROUTING' => [
                    '// Home page data ',
                    'get_alerteinfo_home_page_data' => [
                        'description' => "Retourne la liste complète des données sur la page d'accueil ",
                        'url'         => $this->BASE_URL.'get_alerteinfo_home_page_data',
                        'method'         => 'GET',
                        'response' => [
                            'fil_actualite_data' => '//$fil_actualite_data',
                            'event_keyword_data' => '//$event_keyword_data',
                            'recents_depeche_data' => '//$recents_depeche_data',
                            'politique_news_data' => '//$politique_news_data',
                            'economie_news_data' => '//$economie_news_data',
                            'societe_news_data' => '//$societe_news_data',
                            'securite_news_data' => '//$securite_news_data',
                            'flash_data' => '//$flash_data',
                            'banner_728X90' => '//$banner_728X90',
                            'banner_1920X309' => '//$banner_1920X309',
                            'banner_1200X1500' => '//$banner_1200X1500',

                        ],
                    ],
                    'get_alerteinfo_depeche_details_by_slug' => [
                        'description' => "Retourne les détails d'une dépêche par son slug",
                        'url'         => $this->BASE_URL.'get_alerteinfo_depeche_details_by_slug/{item_slug}',
                        'method'         => 'GET',
                    ],

                    'get_alerteinfo_depeche_archives' => [
                        'description' => "Retourne la liste complète des dépêches archivées",
                        'url'         => $this->BASE_URL.'get_alerteinfo_depeche_archives',
                        'type'         => 'GET',
                    ],
                    'get_alerteinfo_depeche_archives_data_by_mounth_and_year' => [
                        'description' => "Retourne la liste complète des dépêches archivées par mois et année",
                        'url'         => $this->BASE_URL.'get_alerteinfo_depeche_archives_data_by_mounth_and_year/{mounth}/{year}',
                        'method'         => 'GET',
                    ],
                    ''
                ],
            ]
        );
    }
}

