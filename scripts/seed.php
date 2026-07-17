<?php

declare(strict_types=1);

use App\Config\Database;
use App\Config\Env;

require __DIR__ . '/../config/bootstrap.php';

$pdo = Database::pdo();

// --- Premier compte administrateur -----------------------------------
$nbAdmins = (int) $pdo->query('SELECT COUNT(*) FROM administrateurs')->fetchColumn();

if ($nbAdmins === 0) {
    $identifiant = Env::get('ADMIN_INITIAL_USER');
    $motDePasse = Env::get('ADMIN_INITIAL_PASSWORD');

    if ($identifiant === null || $motDePasse === null) {
        fwrite(STDERR, "ADMIN_INITIAL_USER / ADMIN_INITIAL_PASSWORD manquants dans .env\n");
        exit(1);
    }

    $stmt = $pdo->prepare('INSERT INTO administrateurs (identifiant, mot_de_passe_hash) VALUES (?, ?)');
    $stmt->execute([$identifiant, password_hash($motDePasse, PASSWORD_DEFAULT)]);
    echo "Compte administrateur '{$identifiant}' cree.\n";
} else {
    echo "Un administrateur existe deja, rien a faire.\n";
}

// --- Mot de passe "site" ----------------------------------------------
$stmt = $pdo->prepare('SELECT id FROM parametres_site WHERE id = 1');
$stmt->execute();
$existe = $stmt->fetch() !== false;

if (!$existe) {
    $motDePasseSite = Env::get('SITE_INITIAL_PASSWORD');

    if ($motDePasseSite === null) {
        fwrite(STDERR, "SITE_INITIAL_PASSWORD manquant dans .env\n");
        exit(1);
    }

    $stmt = $pdo->prepare('INSERT INTO parametres_site (id, mot_de_passe_hash) VALUES (1, ?)');
    $stmt->execute([password_hash($motDePasseSite, PASSWORD_DEFAULT)]);
    echo "Mot de passe 'site' initialise.\n";
} else {
    echo "Le mot de passe 'site' est deja initialise, rien a faire.\n";
}
