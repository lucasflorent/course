<?php

declare(strict_types=1);

namespace App\Auth;

use PDO;

/**
 * Mot de passe unique "site" protegeant l'acces a la saisie et aux exports.
 */
final class SiteAuth
{
    public static function attempt(PDO $pdo, string $motDePasse): bool
    {
        $stmt = $pdo->prepare('SELECT mot_de_passe_hash FROM parametres_site WHERE id = 1');
        $stmt->execute();
        $parametres = $stmt->fetch();

        if ($parametres === false || !password_verify($motDePasse, $parametres['mot_de_passe_hash'])) {
            return false;
        }

        $_SESSION['site_authenticated'] = true;

        return true;
    }

    public static function check(): bool
    {
        return !empty($_SESSION['site_authenticated']);
    }

    public static function logout(): void
    {
        unset($_SESSION['site_authenticated']);
    }

    public static function setPassword(PDO $pdo, string $nouveauMotDePasse): void
    {
        $stmt = $pdo->prepare('UPDATE parametres_site SET mot_de_passe_hash = ? WHERE id = 1');
        $stmt->execute([password_hash($nouveauMotDePasse, PASSWORD_DEFAULT)]);
    }

    public static function requireAuth(string $loginUrl = '/index.php'): void
    {
        if (!self::check()) {
            header("Location: {$loginUrl}");
            exit;
        }
    }
}
