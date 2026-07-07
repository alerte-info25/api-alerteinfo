<?php
namespace App\Services\CinetPay;

class Marchand
{

    public static function get_apikey () {
        return  "983356407574c21f3171d98.51492430";
    }
    public static function get_apikey2 () {
        return "sk_live_51JtN3EaXPWTFiM3fHcrx9Ef";
    }

    public static function get_api_password () {
        return "CinetPayAlerteinfo@2025";
    }

    public static function get_site_id () {
        return "508313";
    } //Entrer votre site_ID

    public static function get_secret_key () {
        return  "274786085fabb1b57e4d98.56426971";
    } //


    //*******************$ SITE WEB     **************** */

    // site web site id
    public static function getSiteWebSiteId () {
        return "479184";
    } //Entrer votre site_ID
    // site web secret key
    public static function getSiteWebSecretKey () {
        return "650566613588868dcdce761.91104987";
    } //Entrer votre secret_key

    public static function getSiteWebUrl () {
        return "https://www.alerte-info.net";
    } //Entrer votre site web

    // site web notify url
    public static function getSiteWebNotifyUrl () {
        return "https://api-alerteinfo.alerteinfo-mairie.com/api/v1/cinetpay/notify";
    } //Entrer votre notify url
}
