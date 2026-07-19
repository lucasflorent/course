<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Support\Csrf;

require __DIR__ . '/../../../config/bootstrap.php';

AdminAuth::requireLogin();

$pdo = Database::pdo();
$adminId = AdminAuth::currentId();
$erreurs = [];
$succes = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $actuel = (string) ($_POST['mot_de_passe_actuel'] ?? '');
    $nouveau = (string) ($_POST['nouveau_mot_de_passe'] ?? '');
    $confirmation = (string) ($_POST['confirmation'] ?? '');

    if (mb_strlen($nouveau) < 4) {
        $erreurs[] = 'Le nouveau mot de passe doit contenir au moins 4 caractères.';
    }

    if ($nouveau !== $confirmation) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if ($erreurs === []) {
        if (AdminAuth::changePassword($pdo, $adminId, $actuel, $nouveau)) {
            $succes = true;
        } else {
            $erreurs[] = 'Mot de passe actuel incorrect.';
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mon mot de passe — Administration</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script src="/assets/app.js" defer></script>
</head>
<body>
<a class="retour" href="/admin/index.php">&larr; Tableau de bord</a>
<h1>Changer mon mot de passe administrateur</h1>
<?php if ($succes): ?>
    <p class="succes">Mot de passe mis à jour.</p>
<?php endif; ?>
<?php foreach ($erreurs as $erreur): ?>
    <p class="erreur"><?= htmlspecialchars($erreur) ?></p>
<?php endforeach; ?>
<form method="post">
    <?= Csrf::champHtml() ?>
    <label for="mot_de_passe_actuel">Mot de passe actuel</label>
    <input type="password" id="mot_de_passe_actuel" name="mot_de_passe_actuel" required autofocus>
    <label for="nouveau_mot_de_passe">Nouveau mot de passe</label>
    <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" required>
    <label for="confirmation">Confirmation</label>
    <input type="password" id="confirmation" name="confirmation" required>
    <button type="submit" class="bouton-large">Enregistrer</button>
</form>
</body>
</html>
