<?php

namespace App\Services;

class FlashMessages

{
    public  static function isEmpty()
    {
        return "Les champs sont obligatoires";
    }


    public  static function isAdded()
    {
        return "Opération effectuée 💚";
    }



    public  static function isDeleted()
    {
        return "Suppression effectuée 💚";
    }

    public  static function isUpdated()
    {
        return "Modification effectuée 💚";
    }

    public  static function isWarning()
    {
        return "Opération échouée 😂";
    }

    public  static function notFound()
    {
        return "Oups!! Aucun élément trouvé 🤣";
    }

    public  static function notUpload()
    {
        return "Oups!! Image non enrégistrée 😉";
    }

    public  static function emailReady()
    {
        return "Oups!! l'adresse email existe déjà 🤗";
    }

    public  static function paysReady()
    {
        return "Oups!! le pays existe déjà 🤗";
    }

    public  static function isLogged()
    {
        return "Ok! connexion établie 😎";
    }

    public  static function passwordIsInValid()
    {
        return "Oups! Mot de passe incorrect 🤪";
    }
    public  static function isLoggedOut()
    {
        return "Oups! connexion échouée 🤪";
    }

    public  static function isLogOut()
    {
        return "🤪! Vous vous êtes déconnecté !!";
    }
    public  static function unAuthorised()
    {
        return "Oups! accès interdit !👺, Email ou mot de passe introuvable";
    }


    public  static function abonneNotFound()
    {
        return "Oups! Abonné introuvable !👺";
    }
    public  static function tokenExpired()
    {
        return "Oups! accès interdit !👺. Le token n'est plus valable ou une connexion est nécessaire";
    }


    public  static function isActive()
    {

        return "Oups!! Activation éffectuée 🥰";

    }

    public  static function abonnementIsInspired()
    {

        return "Oups!! Votre abonnement a expiré!  ,réabonnez-vous! 🤪";

    }

    public  static function abonnementNotActive()
    {

        return "Oups!! Votre abonnement n'est pas actif  , Vous n'avez pas finalisé votre paiement 🤪! ";

    }

    public  static function abonnementNotFound()
    {

        return "Oups!! Cette adresse email ne correspond pas à un compte d'abonnement 👺";

    }


    public  static function isInactive()
    {
        return "Ok!! Désactivation éffectuée 😎";
    }

    public  static function isWithDrawing()
    {
        return "Ok!! Retrait éffectué 😎";
    }

    public  static function isPushing()
    {
        return "Ok!! Publication éffectuée 😎";
    }

    public static function serverError()
    {
        return "Oups!! La connexion au server a échouée";
    }

}
