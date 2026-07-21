<?php

declare(strict_types=1);

use App\Auth\SiteAuth;
use App\Config\Database;
use App\Repository\EleveRepository;
use App\Repository\SeanceRepository;
use App\Support\Temps;
use App\View\Layout;

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

Layout::debut($eleve['prenom'] . ' — Course de fond CM2', ['eleveId' => $eleveId, 'navActive' => 'saisie']);
?>
<a class="retour" href="/saisie/index.php">&larr; Changer d'élève</a>
<div class="hdr">
    <div class="hdr-txt">
        <div class="hdr-name"><?= htmlspecialchars($eleve['prenom']) ?></div>
        <div class="hdr-sub"><?= htmlspecialchars($eleve['enseignant']) ?> — <?= (int) $eleve['annee_debut'] ?>/<?= (int) $eleve['annee_debut'] + 1 ?></div>
    </div>
</div>

<div class="footer-actions" style="margin-bottom:1rem">
    <a class="btn" href="/saisie/seance_form.php?eleve_id=<?= $eleveId ?>">+ Nouvelle séance</a>
    <?php if ($seances !== []): ?>
        <a class="btn btn-secondary" href="/graphiques/eleve.php?eleve_id=<?= $eleveId ?>">Voir le graphique</a>
    <?php endif; ?>
</div>

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
                <td><a class="btn btn-secondary" href="/saisie/seance_form.php?id=<?= (int) $seance['id'] ?>">Modifier</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php Layout::fin(); ?>
