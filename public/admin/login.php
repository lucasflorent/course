<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;

require __DIR__ . '/../../config/bootstrap.php';

if (AdminAuth::check()) {
    header('Location: /admin/index.php');
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = (string) ($_POST['identifiant'] ?? '');
    $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');

    if (AdminAuth::attempt(Database::pdo(), $identifiant, $motDePasse)) {
        header('Location: /admin/index.php');
        exit;
    }

    $erreur = 'Identifiant ou mot de passe incorrect.';
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration — Course de fond CM2</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<h1>Administration</h1>
<?php if ($erreur): ?><p class="erreur"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
<form method="post">
    <label for="identifiant">Identifiant</label>
    <input type="text" id="identifiant" name="identifiant" required autofocus>
    <label for="mot_de_passe">Mot de passe</label>
    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
    <button type="submit">Se connecter</button>
</form>
</body>
</html>
