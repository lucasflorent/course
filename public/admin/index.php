<?php

declare(strict_types=1);

use App\Auth\AdminAuth;

require __DIR__ . '/../../config/bootstrap.php';

AdminAuth::requireLogin();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tableau de bord admin — Course de fond CM2</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<a class="retour" href="/index.php">&larr; Menu principal</a>
<h1>Tableau de bord administrateur</h1>
<p class="actions">
    <a class="bouton" href="/admin/classes/index.php">Classes</a>
    <a class="bouton" href="/admin/parametres/mot_de_passe.php">Mot de passe site</a>
    <a class="bouton" href="/admin/parametres/mon_mot_de_passe.php">Mon mot de passe</a>
</p>
<p><a href="/admin/logout.php">Se deconnecter</a></p>
</body>
</html>
