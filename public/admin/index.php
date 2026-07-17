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
    <style>
        body { font-family: system-ui, sans-serif; max-width: 480px; margin: 3rem auto; padding: 0 1rem; }
    </style>
</head>
<body>
<h1>Tableau de bord administrateur</h1>
<p>Gestion des enseignants, annees scolaires, classes et eleves : a venir.</p>
<p><a href="/admin/logout.php">Se deconnecter</a></p>
</body>
</html>
