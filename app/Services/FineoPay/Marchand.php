<?php

namespace App\Services\FineoPay;

class Marchand
{
    // ===== ENVIRONNEMENT =====
    // Mettre à false pour la production
    public static function isSandbox(): bool
    {
        return false;
    }

    // ===== AUTHENTIFICATION =====
    public static function getBusinessCode(): string
    {
        // À remplacer par les vraies clés fournies par FineoPay
        return "federation_ivoirienne_dathletisme_";
    }

    public static function getApiKey(): string
    {
        // Clé secrète de 64 caractères
        return "fpay_46b55ebc785154f9072dd1543f7410298e16ea575c2b3989bf74e546db43";
    }

    // ===== URLS =====
    public static function getCallbackUrl(): string
    {
        // À adapter selon ton domaine
        return "https://api-fia-base-pro.alerteinfo-mairie.com/api/fineo-payment-notify";
        // return "http://127.0.0.1:8000/api/fineo-payment-notify";
    }

    public static function getReturnUrl(): string
    {
        // Page de redirection après paiement (optionnel avec FineoPay)
        return "https://fia-base.com/payment-status";
    }

    // ===== TARIFS PAR CANAL (comme avant) =====
    public static function getOrangeMoneyTarif(): float
    {
        return 0.03; // 3%
    }

    public static function getMTNMoneyTarif(): float
    {
        return 0.018; // 1.8%
    }

    public static function getMoovMoneyTarif(): float
    {
        return 0.025; // 2.5%
    }

    public static function getWaveTarif(): float
    {
        return 0.02; // 2%
    }

    public static function getFineoTarif(): float
    {
        return 0.01; // 1% (à ajuster selon contrat)
    }

    private static array $paymentMethods = ["orange", "mtn", "moov", "wave", "fineo"];

    private static function getPaymentMethodsTarif(): array
    {
        return [
            "orange" => self::getOrangeMoneyTarif(),
            "mtn"    => self::getMTNMoneyTarif(),
            "moov"   => self::getMoovMoneyTarif(),
            "wave"   => self::getWaveTarif(),
            "fineo"  => self::getFineoTarif(),
        ];
    }

    public static function getCurrentTarif(string $currentMethod): ?float
    {
        if (!in_array($currentMethod, self::$paymentMethods)) {
            return null;
        }
        return self::getPaymentMethodsTarif()[$currentMethod];
    }
}
