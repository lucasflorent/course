<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Auth\SiteAuth;
use App\Config\Database;
use App\Support\Csrf;

require __DIR__ . '/../../../config/bootstrap.php';

AdminAuth::requireLogin();

$pdo = Database::pdo();
$erreurs = [];
$succes = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $nouveau = (string) ($_POST['nouveau_mot_de_passe'] ?? '');
    $confirmation = (string) ($_POST['confirmation'] ?? '');

    if (mb_strlen($nouveau) < 4) {
        $erreurs[] = 'Le mot de passe doit contenir au moins 4 caractères.';
    }

    if ($nouveau !== $confirmation) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if ($erreurs === []) {
        SiteAuth::setPassword($pdo, $nouveau);
        $succes = true;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mot de passe site — Administration</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<a class="retour" href="/admin/index.php">&larr; Tableau de bord</a>
<h1>Changer le mot de passe "site"</h1>
<p>Ce mot de passe protège l'accès à la saisie et aux exports pour les enseignants et élèves.</p>
<?php if ($succes): ?>
    <p class="succes">Mot de passe mis à jour.</p>
<?php endif; ?>
<?php foreach ($erreurs as $erreur): ?>
    <p class="erreur"><?= htmlspecialchars($erreur) ?></p>
<?php endforeach; ?>
<form method="post">
    <?= Csrf::champHtml() ?>
    <label for="nouveau_mot_de_passe">Nouveau mot de passe</label>
    <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" required autofocus>
    <label for="confirmation">Confirmation</label>
    <input type="password" id="confirmation" name="confirmation" required>
    <button type="submit" class="bouton-large">Enregistrer</button>
</form>
</body>
</html>
