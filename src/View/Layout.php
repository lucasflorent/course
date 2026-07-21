<?php

declare(strict_types=1);

namespace App\View;

use App\Support\Icone;

/**
 * Squelette HTML commun (tete + pied de page) partage par toutes les pages
 * publiques, pour eviter de dupliquer le <head>/<body> ~20 fois et pour
 * appliquer de facon coherente la nav laterale admin (.dnav) et la nav basse
 * eleve (.bignav).
 */
final class Layout
{
    private static bool $admin = false;
    private static ?int $eleveId = null;
    private static ?string $navActive = null;

    /**
     * @param array{admin?:bool, adminNav?:?string, large?:bool, eleveId?:?int, navActive?:?string, js?:?string} $options
     */
    public static function debut(string $titre, array $options = []): void
    {
        $admin = $options['admin'] ?? false;
        $large = $options['large'] ?? false;

        self::$admin = $admin;
        self::$eleveId = $options['eleveId'] ?? null;
        self::$navActive = $options['navActive'] ?? null;

        $classesBody = [];
        if ($admin) {
            $classesBody[] = 'admin';
        } elseif ($large) {
            $classesBody[] = 'large';
        }

        echo '<!doctype html>' . "\n";
        echo '<html lang="fr">' . "\n";
        echo '<head>' . "\n";
        echo '<meta charset="utf-8">' . "\n";
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
        echo '<title>' . htmlspecialchars($titre) . '</title>' . "\n";
        echo '<link rel="stylesheet" href="/assets/style.css">' . "\n";
        if (!empty($options['js'])) {
            echo '<script src="' . htmlspecialchars($options['js']) . '" defer></script>' . "\n";
        }
        echo '</head>' . "\n";
        echo '<body' . ($classesBody !== [] ? ' class="' . implode(' ', $classesBody) . '"' : '') . '>' . "\n";

        if ($admin) {
            echo '<div class="admin-shell">' . "\n";
            self::navAdmin($options['adminNav'] ?? null);
            echo '<div class="dmain">' . "\n";
        }
    }

    private static function navAdmin(?string $actif): void
    {
        $liens = [
            'teachers' => ['/admin/classes/index.php', 'chalkboard-teacher', 'Enseignants & classes'],
            'students' => ['/admin/eleves/index.php', 'users-three', 'Élèves'],
            'pwSite' => ['/admin/parametres/mot_de_passe.php', 'lock-key', 'Mot de passe site'],
            'pwAdmin' => ['/admin/parametres/mon_mot_de_passe.php', 'key', 'Mon mot de passe'],
        ];

        echo '<nav class="dnav">' . "\n";
        echo '<div class="dnav-brand">Administration</div>' . "\n";
        foreach ($liens as $cle => [$href, $icone, $libelle]) {
            $classe = $cle === $actif ? 'dnavitem active' : 'dnavitem';
            echo '<a class="' . $classe . '" href="' . htmlspecialchars($href) . '">'
                . Icone::svg($icone) . '<span>' . htmlspecialchars($libelle) . '</span></a>' . "\n";
        }
        echo '<a class="dnavitem" href="/admin/logout.php" style="margin-top:auto">'
            . Icone::svg('box-arrow-left') . '<span>Quitter l\'espace admin</span></a>' . "\n";
        echo '</nav>' . "\n";
    }

    public static function fin(): void
    {
        if (self::$eleveId !== null) {
            self::navBasse((int) self::$eleveId, self::$navActive);
        }

        if (self::$admin) {
            echo '</div></div>' . "\n"; // .dmain, .admin-shell
        }

        echo '</body>' . "\n" . '</html>' . "\n";
    }

    private static function navBasse(int $eleveId, ?string $actif): void
    {
        $liens = [
            'accueil' => ['/saisie/index.php', 'house', 'Accueil'],
            'saisie' => ['/saisie/eleve.php?eleve_id=' . $eleveId, 'timer', 'Saisie'],
            'graphique' => ['/graphiques/eleve.php?eleve_id=' . $eleveId, 'chart-line', 'Graphique'],
        ];

        echo '<nav class="bignav">' . "\n";
        foreach ($liens as $cle => [$href, $icone, $libelle]) {
            $classe = $cle === $actif ? 'navitem active' : 'navitem';
            echo '<a class="' . $classe . '" href="' . htmlspecialchars($href) . '">'
                . Icone::svg($icone) . '<span>' . htmlspecialchars($libelle) . '</span></a>' . "\n";
        }
        echo '</nav>' . "\n";
    }
}
