<?php

declare(strict_types=1);

use App\Auth\SiteAuth;
use App\Config\Database;
use App\Graphique\GraphiqueTours;
use App\Repository\EleveRepository;
use App\Repository\SeanceRepository;
use App\Repository\TempsPassageRepository;
use App\Support\Statistiques;
use App\Support\Temps;
use App\Support\TourDerivation;
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
$idsSeances = array_column($seances, 'id');

if (isset($_GET['seance_id'])) {
    $demandes = array_map('intval', (array) $_GET['seance_id']);
    $idsSelectionnes = array_values(array_intersect($idsSeances, $demandes));
    $idsSelectionnes = array_slice($idsSelectionnes, 0, GraphiqueTours::MAX_SERIES);
} else {
    // Par defaut : la seance la plus recente seule.
    $idsSelectionnes = $seances !== [] ? [(int) $seances[0]['id']] : [];
}

$seancesSelectionnees = array_values(array_filter(
    $seances,
    static fn (array $s): bool => in_array((int) $s['id'], $idsSelectionnes, true)
));

$analyses = [];
foreach ($seancesSelectionnees as $s) {
    $temps = TempsPassageRepository::findBySeance($pdo, (int) $s['id']);
    $tours = TourDerivation::deriver($temps);
    $stats = Statistiques::calculer($tours);
    $vitesse = Statistiques::vitesseMoyenneKmh(
        $s['longueur_tour_m'] !== null ? (float) $s['longueur_tour_m'] : null,
        $stats['moyenne_s']
    );
    $analyses[] = ['seance' => $s, 'stats' => $stats, 'vitesse' => $vitesse];
}

$urlImage = null;
if ($seancesSelectionnees !== []) {
    $params = ['eleve_id' => $eleveId, 'seance_id' => array_column($seancesSelectionnees, 'id')];
    $urlImage = '/graphiques/png.php?' . http_build_query($params);
}
Layout::debut('Graphique — ' . $eleve['prenom'] . ' — Course de fond CM2', [
    'large' => true,
    'eleveId' => $eleveId,
    'navActive' => 'graphique',
]);
?>
<a class="retour" href="/saisie/eleve.php?eleve_id=<?= $eleveId ?>">&larr; <?= htmlspecialchars($eleve['prenom']) ?></a>
<div class="hdr">
    <div class="hdr-txt">
        <div class="hdr-name"><?= htmlspecialchars($eleve['prenom']) ?></div>
        <div class="hdr-sub"><?= htmlspecialchars($eleve['enseignant']) ?> — <?= (int) $eleve['annee_debut'] ?>/<?= (int) $eleve['annee_debut'] + 1 ?></div>
    </div>
</div>

<?php if ($seances === []): ?>
    <p>Aucune séance saisie pour le moment.</p>
<?php else: ?>
    <div class="section-lbl">Séances à comparer (<?= GraphiqueTours::MAX_SERIES ?> maximum)</div>
    <form method="get" action="/graphiques/eleve.php">
        <input type="hidden" name="eleve_id" value="<?= $eleveId ?>">
        <div id="cases-seances">
            <?php foreach ($seances as $s): ?>
                <label class="checkrow">
                    <input type="checkbox" name="seance_id[]" value="<?= (int) $s['id'] ?>"
                        <?= in_array((int) $s['id'], $idsSelectionnes, true) ? 'checked' : '' ?>>
                    <span class="checkname">
                        <?= htmlspecialchars((new DateTimeImmutable($s['date_seance']))->format('d-m-Y')) ?>
                        <?= $s['longueur_tour_m'] !== null ? '(' . htmlspecialchars((string) $s['longueur_tour_m']) . ' m)' : '' ?>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="btn-secondary" style="margin-top:8px">Actualiser le graphique</button>
    </form>

    <?php if ($urlImage !== null): ?>
        <div class="chartwrap" style="margin-top:16px">
            <img src="<?= htmlspecialchars($urlImage) ?>" alt="Graphique des durées de tour">
        </div>

        <?php foreach ($analyses as $analyse): ?>
            <?php $stats = $analyse['stats']; $s = $analyse['seance']; ?>
            <div class="card">
                <strong><?= htmlspecialchars((new DateTimeImmutable($s['date_seance']))->format('d-m-Y')) ?></strong>
                <div class="statgrid" style="margin-top:10px">
                    <div class="statcard"><div class="statval"><?= $stats['meilleur_s'] !== null ? htmlspecialchars(Temps::format($stats['meilleur_s'])) : '—' ?></div><div class="statlbl">Meilleur tour</div></div>
                    <div class="statcard"><div class="statval"><?= $stats['pire_s'] !== null ? htmlspecialchars(Temps::format($stats['pire_s'])) : '—' ?></div><div class="statlbl">Tour le plus lent</div></div>
                    <div class="statcard"><div class="statval"><?= $stats['moyenne_s'] !== null ? htmlspecialchars(Temps::format((int) round($stats['moyenne_s']))) : '—' ?></div><div class="statlbl">Moyenne / tour</div></div>
                    <div class="statcard"><div class="statval"><?= $stats['ecart_type_s'] !== null ? '± ' . htmlspecialchars(number_format($stats['ecart_type_s'], 1)) . ' s' : '—' ?></div><div class="statlbl">Écart-type</div></div>
                </div>
                <p style="margin-top:10px"><span class="text-muted">Vitesse moyenne :</span> <?= $analyse['vitesse'] !== null ? htmlspecialchars(number_format($analyse['vitesse'], 1)) . ' km/h' : 'Non renseignée' ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

<script>
(function () {
    var conteneur = document.getElementById('cases-seances');
    if (!conteneur) return;
    var max = <?= GraphiqueTours::MAX_SERIES ?>;
    var cases = conteneur.querySelectorAll('input[type="checkbox"]');

    function maj() {
        var coches = conteneur.querySelectorAll('input[type="checkbox"]:checked').length;
        cases.forEach(function (c) {
            c.disabled = !c.checked && coches >= max;
        });
    }

    cases.forEach(function (c) { c.addEventListener('change', maj); });
    maj();
})();
</script>
<?php Layout::fin(); ?>
