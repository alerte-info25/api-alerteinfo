<?php

namespace App\Services\MailSenderServices;

use App\Mail\SenderMailer;
use Illuminate\Support\Facades\Mail;

class MailSenderService
{
    public static $__mailView = "mails.send_simple_notification";
    public static function srv_sendEmail($to, $cc, $subject, $view, $mailContent)
    {
        try {
            // send with cc if cc is specified
            if (!empty($cc)) {
                Mail::to($to)
                    ->cc($cc)
                    ->send(new SenderMailer($view, $mailContent, $subject));
            } else {
                Mail::to($to)
                    ->send(new SenderMailer($view, $mailContent, $subject));
            }
            return true;

        } catch (\Exception $e) {
            // Log the error message
            \Log::error('Mail sending failed: ' . $e->getMessage());
            return false;
        }
    }



    // Méthode pour envoyer la notification par email lors de la modfification d'un mot de passe
    public static function srv_sendPasswordUpdateNotification($data, $password)
    {

        $subject = "ALERTE INFO - MISE A JOUR DE MOT DE PASSE.";
        $notifications = "
            <p>Bonjour, "
            . $data->first_name
            . " " . $data->last_name . "</p>

            <p><strong>Votre mot de passe a été modifié avec succès.</strong></p>

            <p><strong style='color: red;'>Adresse email: </strong><br>
            <strong style='color:red; font-size: 1.7rem;'>" . $data->email . "</strong></p>

            <p><strong style='color: red;'>Nouveau mot de passe: </strong><br>
            <strong style='color: red;'>" . $password . "</strong></p>

            <p>Cordialement,<br>L'équipe de ALERTE INFO</p>
        ";

        return self::srv_sendEmail(
            $data->email,
            "",
            $subject,
            self::$__mailView,
            $notifications
        );
    }


    // Méthode pour envoyer la notification par email lors de la création d'un compte
    public static function srv_sendAccountCreationNotification($createUser, $password)
    {
        $subject = "ALERTE INFO - CREATION DE COMPTE ";
        $notifications = "
            <p>Bonjour <strong>{$createUser->first_name} {$createUser->last_name}</strong>,</p>

            <p><strong>Votre compte a bien été créé avec succès.</strong> Vous pouvez à présent vous connecter.</p>

            <p style='color:red'><strong>Votre adresse email :</strong> {$createUser->email}</p>
            <p style='color:red'><strong>Votre mot de passe :</strong> {$password}</p>

            <p style='color:red'><strong>Lien de connexion :</strong></p>

            <p>
                <strong style='color:red; font-size: 1.7rem;'>
                    <span style='color: red; font-size: 1.5rem;'>
                        <a href='https://admin-redaction.alerte-info.net/' target='_blank'>Administration ALERTE INFO</a>
                    </span>
                </strong>
            </p>

            <p><strong>Date de création du compte :</strong> " . date('d/m/Y') . "</p>

            <p>Cordialement,<br>L'équipe de ALERTE INFO</p>
        ";

        return self::srv_sendEmail(
            $createUser->email,
            "",
            $subject,
            self::$__mailView,
            $notifications
        );
    }

    // Méthode pour envoyer la notification OTP par email lors de la demande de modification de mot de passe
    public static function srv_sendOTPForPasswordUpdate($data, $otp_code)
    {
        $subject = "ALERTE INFO - OTP DE MISE A JOUR DE MOT DE PASSE.";
        $notifications = "<p>Bonjour, "
            . $data->first_name
            . " " . $data->last_name
            . "</p>"
            . "<p><strong>Vous tentez de modifier votre mot de passe. S'il s'agit bien de vous, utilisez le code OTP suivant pour le modifier.</strong></p>"
            . "<p><strong style='color: red;'>Voici votre code d'accès (OTP) pour mettre à jour votre mot de passe:</strong></p>"
            . "<p><strong style='color:red; font-size: 1.7rem;'>" . $otp_code . "</strong></p>"
            . "<p><strong style='color: red;'>Cet OTP expirera dans 5 minutes.</strong></p>"
            . "<p>Cordialement,<br>L'équipe de ALERTE INFO</p>"
        ;

        return self::srv_sendEmail(
            $data->email,
            "",
            $subject,
            self::$__mailView,
            $notifications
        );
    }


    // Méthode pour envoyer la notification par email lors de la soumission du formulaire des pigistes
    public static function srv_sendPigisteFormNotification($data)
    {
        $subject = "NOUVELLE SOUMISSION DU FORMULAIRE PIGISTE – QUOIDENEUF";

        $notifications = "
            <p>Bonjour,</p>

            <p>
                <strong>
                    Une nouvelle soumission du formulaire des pigistes vient d’être effectuée.
                    Veuillez trouver ci-dessous les informations transmises par le candidat.
                </strong>
            </p>

            <p><strong style='color: red;'>Informations de la soumission :</strong></p>

            <p>
                <strong>Nom :</strong> {$data->pigiste_first_name} {$data->pigiste_last_name}<br>
                <strong>Email :</strong> <span style='color: red; font-size: 1.2rem;'>{$data->pigiste_email}</span>
            </p>

            <br>

            <p>
                <strong>Cordialement,<br>L'équipe QUOIDENEUF / ALERTE INFO</strong>
            </p>
        ";

        //direction@alerte-info.net

        return self::srv_sendEmail(
            "brou4859@gmail.com", // destinataire admin
            "",
            $subject,
            self::$__mailView,
            $notifications
        );
    }


}
