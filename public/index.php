<?php

declare(strict_types=1);

use App\Auth\SiteAuth;
use App\Config\Database;

require __DIR__ . '/../config/bootstrap.php';

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');

    if (SiteAuth::attempt(Database::pdo(), $motDePasse)) {
        header('Location: /index.php');
        exit;
    }

    $erreur = 'Mot de passe incorrect.';
}

$authentifie = SiteAuth::check();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Course de fond CM2</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<?php if ($authentifie): ?>
    <h1>Bienvenue !</h1>
    <p><a class="bouton bouton-large" href="/saisie/index.php">Saisir des temps de passage</a></p>
    <p><a href="/logout.php">Se deconnecter</a></p>
<?php else: ?>
    <h1>Course de fond CM2</h1>
    <p>Merci de saisir le mot de passe pour continuer.</p>
    <?php if ($erreur): ?><p class="erreur"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
    <form method="post">
        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe" required autofocus>
        <button type="submit" class="bouton-large">Valider</button>
    </form>
<?php endif; ?>
</body>
</html>
