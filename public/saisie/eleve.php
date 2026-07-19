<?php

declare(strict_types=1);

use App\Auth\SiteAuth;
use App\Config\Database;
use App\Repository\EleveRepository;
use App\Repository\SeanceRepository;
use App\Support\Temps;

require __DIR__ . '/../../config/bootstrap.php';

SiteAuth::requireAuth();

$pdo = Database::pdo();
$eleveId = (int) ($_GET['eleve_id'] ?? 0);
$eleve = $eleveId > 0 ? EleveRepository::findByIdAvecClasse($pdo, $eleveId) : null;

if ($eleve === null) {
    header('Location: /saisie/index.php');
    exit;
}

$seances = SeanceRepository::findByEleve($pdo, $eleveId);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($eleve['prenom']) ?> — Course de fond CM2</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<a class="retour" href="/saisie/index.php">&larr; Changer d'élève</a>
<h1><?= htmlspecialchars($eleve['prenom']) ?></h1>
<p><?= htmlspecialchars($eleve['enseignant']) ?> — <?= (int) $eleve['annee_debut'] ?>/<?= (int) $eleve['annee_debut'] + 1 ?></p>

<p><a class="bouton bouton-large" href="/saisie/seance_form.php?eleve_id=<?= $eleveId ?>">+ Nouvelle séance</a></p>

<?php if ($seances === []): ?>
    <p>Aucune séance saisie pour le moment.</p>
<?php else: ?>
    <table>
        <thead>
        <tr><th>Date</th><th>Longueur tour</th><th>Temps saisis</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($seances as $seance): ?>
            <tr>
                <td><?= htmlspecialchars((new DateTimeImmutable($seance['date_seance']))->format('d-m-Y')) ?></td>
                <td><?= $seance['longueur_tour_m'] !== null ? htmlspecialchars((string) $seance['longueur_tour_m']) . ' m' : '—' ?></td>
                <td><?= (int) $seance['nb_temps'] ?></td>
                <td><a class="bouton bouton-secondaire" href="/saisie/seance_form.php?id=<?= (int) $seance['id'] ?>">Modifier</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>
