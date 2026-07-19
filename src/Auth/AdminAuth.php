<?php

declare(strict_types=1);

namespace App\Auth;

use PDO;

/**
 * Authentification administrateur (identifiants separes du mot de passe "site").
 */
final class AdminAuth
{
    public static function attempt(PDO $pdo, string $identifiant, string $motDePasse): bool
    {
        $stmt = $pdo->prepare('SELECT id, mot_de_passe_hash FROM administrateurs WHERE identifiant = ?');
        $stmt->execute([$identifiant]);
        $admin = $stmt->fetch();

        if ($admin === false || !password_verify($motDePasse, $admin['mot_de_passe_hash'])) {
            return false;
        }

        $_SESSION['admin_id'] = (int) $admin['id'];

        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['admin_id']);
    }

    public static function currentId(): ?int
    {
        return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
    }

    /**
     * Change le mot de passe de l'administrateur connecte, apres verification
     * de son mot de passe actuel. Retourne false si le mot de passe actuel
     * est incorrect (aucune modification n'est alors effectuee).
     */
    public static function changePassword(PDO $pdo, int $adminId, string $motDePasseActuel, string $nouveauMotDePasse): bool
    {
        $stmt = $pdo->prepare('SELECT mot_de_passe_hash FROM administrateurs WHERE id = ?');
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();

        if ($admin === false || !password_verify($motDePasseActuel, $admin['mot_de_passe_hash'])) {
            return false;
        }

        $stmt = $pdo->prepare('UPDATE administrateurs SET mot_de_passe_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($nouveauMotDePasse, PASSWORD_DEFAULT), $adminId]);

        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_id']);
    }

    public static function requireLogin(string $loginUrl = '/admin/login.php'): void
    {
        if (!self::check()) {
            header("Location: {$loginUrl}");
            exit;
        }
    }
}
