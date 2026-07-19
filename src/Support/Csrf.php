<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Jeton anti-CSRF, un seul par session (pas un par formulaire, pour que
 * plusieurs formulaires affiches simultanement - ex. une ligne "supprimer"
 * par eleve dans une liste - restent tous valides).
 */
final class Csrf
{
    public const CHAMP = 'csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function check(string $soumis): bool
    {
        $attendu = $_SESSION['csrf_token'] ?? '';

        return $attendu !== '' && hash_equals($attendu, $soumis);
    }

    /**
     * A appeler en tete de traitement de chaque POST. Interrompt la requete
     * (400) sans effet de bord si le jeton est absent ou invalide.
     */
    public static function requireValid(): void
    {
        $soumis = (string) ($_POST[self::CHAMP] ?? '');

        if (!self::check($soumis)) {
            http_response_code(400);
            echo 'Requete invalide (jeton de securite manquant ou expire). Rechargez la page et reessayez.';
            exit;
        }
    }

    public static function champHtml(): string
    {
        return '<input type="hidden" name="' . self::CHAMP . '" value="' . htmlspecialchars(self::token()) . '">';
    }
}
