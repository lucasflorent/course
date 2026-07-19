<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Repository\ClasseRepository;

require __DIR__ . '/../../../config/bootstrap.php';

AdminAuth::requireLogin();

$pdo = Database::pdo();
$classes = ClasseRepository::findAll($pdo);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Classes — Administration</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="large">
<a class="retour" href="/admin/index.php">&larr; Tableau de bord</a>
<h1>Classes</h1>
<p><a class="bouton" href="/admin/classes/form.php">+ Nouvelle classe</a></p>
<?php if ($classes === []): ?>
    <p>Aucune classe pour le moment.</p>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>Enseignant</th>
            <th>Année scolaire</th>
            <th>Libellé</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($classes as $classe): ?>
            <tr>
                <td><?= htmlspecialchars($classe['enseignant']) ?></td>
                <td><?= (int) $classe['annee_debut'] ?>/<?= (int) $classe['annee_debut'] + 1 ?></td>
                <td><?= htmlspecialchars($classe['libelle_classe'] ?? '') ?></td>
                <td class="actions">
                    <a class="bouton" href="/admin/eleves/index.php?classe_id=<?= (int) $classe['id'] ?>">Élèves</a>
                    <a class="bouton bouton-secondaire" href="/admin/classes/form.php?id=<?= (int) $classe['id'] ?>">Modifier</a>
                    <a class="bouton bouton-danger" href="/admin/classes/supprimer.php?id=<?= (int) $classe['id'] ?>">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>
