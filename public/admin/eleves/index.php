<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Repository\ClasseRepository;
use App\Repository\EleveRepository;

require __DIR__ . '/../../../config/bootstrap.php';

AdminAuth::requireLogin();

$pdo = Database::pdo();
$classeId = (int) ($_GET['classe_id'] ?? 0);
$classe = $classeId > 0 ? ClasseRepository::findById($pdo, $classeId) : null;

if ($classe === null) {
    header('Location: /admin/classes/index.php');
    exit;
}

$eleves = EleveRepository::findByClasse($pdo, $classeId);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Élèves — Administration</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="large">
<a class="retour" href="/admin/classes/index.php">&larr; Classes</a>
<h1>Élèves — <?= htmlspecialchars($classe['enseignant']) ?> (<?= (int) $classe['annee_debut'] ?>/<?= (int) $classe['annee_debut'] + 1 ?>)</h1>
<?php if (isset($_GET['importes'])): ?>
    <p class="succes"><?= (int) $_GET['importes'] ?> élève(s) importé(s).</p>
<?php endif; ?>
<p class="actions">
    <a class="bouton" href="/admin/eleves/form.php?classe_id=<?= $classeId ?>">+ Ajouter un élève</a>
    <a class="bouton bouton-secondaire" href="/admin/eleves/import.php?classe_id=<?= $classeId ?>">Importer un CSV</a>
</p>
<?php if ($eleves === []): ?>
    <p>Aucun élève dans cette classe pour le moment.</p>
<?php else: ?>
    <table>
        <thead>
        <tr><th>Prénom</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($eleves as $eleve): ?>
            <tr>
                <td><?= htmlspecialchars($eleve['prenom']) ?></td>
                <td class="actions">
                    <a class="bouton bouton-secondaire" href="/admin/eleves/form.php?id=<?= (int) $eleve['id'] ?>">Modifier</a>
                    <a class="bouton bouton-danger" href="/admin/eleves/supprimer.php?id=<?= (int) $eleve['id'] ?>">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>
