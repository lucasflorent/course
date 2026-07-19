<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Cookie de preremplissage (dernier enseignant/classe, derniere date de
 * seance, derniere longueur de tour) pour accelerer la saisie de l'eleve
 * suivant. Les valeurs restent toujours visibles et modifiables dans les
 * formulaires : ce cookie ne fait que suggerer des valeurs par defaut.
 */
final class PrefillCookie
{
    private const NOM = 'cf_prefs';
    private const DUREE_JOURS = 200;

    /**
     * @return array{classe_id?:int, date_seance?:string, longueur_tour_m?:float}
     */
    public static function get(): array
    {
        $brut = $_COOKIE[self::NOM] ?? null;

        if (!is_string($brut)) {
            return [];
        }

        $donnees = json_decode($brut, true);

        return is_array($donnees) ? $donnees : [];
    }

    public static function set(array $donnees): void
    {
        setcookie(self::NOM, json_encode($donnees), [
            'expires' => time() + (self::DUREE_JOURS * 86400),
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
    }
}
