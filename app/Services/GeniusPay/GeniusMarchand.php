<?php

namespace App\Services\GeniusPay;

class GeniusMarchand
{
    /**
     * Clé publique GeniusPay
     */
    public static function getApiKey()
    {
        return env('GENIUSPAY_API_KEY');
    }

    /**
     * Clé secrète GeniusPay
     */
    public static function getApiSecret()
    {
        return env('GENIUSPAY_API_SECRET');
    }

    /**
     * Secret utilisé pour vérifier les webhooks
     */
    public static function getWebhookSecret()
    {
        return env('GENIUSPAY_WEBHOOK_SECRET');
    }

    /**
     * URL du site
     */
    public static function getSiteWebUrl()
    {
         return "https://www.alerte-info.net";
    }

    /**
     * URL de succès
     */
    public static function getSuccessUrl()
    {
        return env('GENIUSPAY_SUCCESS_URL');
    }

    public static function getErrorUrl()
    {
        return env('GENIUSPAY_ERROR_URL');
    }

    /**
     * URL du webhook
     */
    public static function getWebhookUrl()
    {
        return 'https://api-alerteinfo.alerteinfo-mairie.com/api/v1/geniuspay/webhook';
    }

    /**
     * URL API GeniusPay
     */
    public static function getBaseUrl()
    {
        return env(
            'GENIUSPAY_BASE_URL',
            'https://geniuspay.ci/api/v1/merchant'
        );
    }
}
