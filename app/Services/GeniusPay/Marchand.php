<?php
// config/GeniusPay/Marchand.php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration Marchand GeniusPay
    |--------------------------------------------------------------------------
    | Ce fichier contient toutes les configurations spécifiques à votre
    | compte marchand GeniusPay.
    */

    'api' => [
        // URLs
        'base_url' => 'https://geniuspay.ci/api/v1/merchant',
        'sandbox_url' => 'https://geniuspay.ci/api/v1/merchant',
        'live_url' => 'https://geniuspay.ci/api/v1/merchant',

        // Clés d'authentification
        'public_key' => env('GENIUSPAY_PUBLIC_KEY', 'pk_sandbox_votre_cle_publique'),
        'secret_key' => env('GENIUSPAY_SECRET_KEY', 'sk_sandbox_votre_cle_secrete'),

        // Mode de test
        'sandbox_mode' => env('GENIUSPAY_SANDBOX_MODE', true),
    ],

    'webhook' => [
        'secret' => env('GENIUSPAY_WEBHOOK_SECRET', 'whsec_sandbox_votre_secret'),
        'url' => env('GENIUSPAY_WEBHOOK_URL', '/api/webhooks/geniuspay'),
        'timeout' => 300, // 5 minutes pour la validation du timestamp
    ],

    'business' => [
        'name' => env('GENIUSPAY_BUSINESS_NAME', 'Ma Boutique'),
        'email' => env('GENIUSPAY_BUSINESS_EMAIL', 'contact@maboutique.com'),
        'phone' => env('GENIUSPAY_BUSINESS_PHONE', '+22500000000'),
    ],

    'payment' => [
        'min_amount' => 200,
        'max_amount' => 1000000,
        'default_currency' => 'XOF',
        'allowed_currencies' => ['XOF', 'EUR', 'USD'],

        // Configuration par défaut
        'default_description' => 'Paiement sur ma boutique',
        'default_success_url' => env('GENIUSPAY_SUCCESS_URL', '/payment/success'),
        'default_error_url' => env('GENIUSPAY_ERROR_URL', '/payment/error'),

        // Méthodes de paiement disponibles
        'methods' => [
            'wave' => [
                'enabled' => true,
                'name' => 'Wave',
                'icon' => 'wave.png',
                'min_amount' => 200,
            ],
            'orange_money' => [
                'enabled' => true,
                'name' => 'Orange Money',
                'icon' => 'orange.png',
                'min_amount' => 200,
            ],
            'mtn_money' => [
                'enabled' => true,
                'name' => 'MTN Mobile Money',
                'icon' => 'mtn.png',
                'min_amount' => 200,
            ],
            'pawapay' => [
                'enabled' => true,
                'name' => 'PawaPay',
                'icon' => 'pawapay.png',
                'min_amount' => 200,
            ],
            'paystack' => [
                'enabled' => true,
                'name' => 'Carte bancaire',
                'icon' => 'paystack.png',
                'min_amount' => 500,
            ],
            'card' => [
                'enabled' => true,
                'name' => 'Carte bancaire',
                'icon' => 'card.png',
                'min_amount' => 500,
            ],
        ],
    ],

    'features' => [
        // Activer/désactiver certaines fonctionnalités
        'checkout_page' => true, // Utiliser la page de checkout GeniusPay
        'direct_payment' => true, // Permettre les paiements directs
        'auto_convert_currency' => true, // Conversion automatique des devises
        'save_customer_data' => true, // Sauvegarder les données client
    ],

    'notifications' => [
        'send_email_on_payment' => true,
        'send_sms_on_payment' => false,
        'admin_notification_email' => env('GENIUSPAY_ADMIN_EMAIL', 'admin@maboutique.com'),
    ],

    // Configuration des métadonnées par défaut
    'metadata' => [
        'platform' => 'laravel_angular_app',
        'version' => '1.0.0',
    ],

    // Logging
    'logging' => [
        'enabled' => true,
        'channel' => 'geniuspay',
        'level' => env('GENIUSPAY_LOG_LEVEL', 'info'),
    ],
];
