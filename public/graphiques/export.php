<?php

declare(strict_types=1);

use App\Auth\SiteAuth;
use App\Config\Database;
use App\Graphique\GraphiqueTours;
use App\Repository\ClasseRepository;
use App\Repository\SeanceRepository;
use App\Support\AnneeScolaire;
use App\Support\Icone;
use App\Support\PrefillCookie;
use App\View\Layout;

require __DIR__ . '/../../config/bootstrap.php';

SiteAuth::requireAuth();

$pdo = Database::pdo();
$classes = ClasseRepository::findAll($pdo);
$idsClasses = array_column($classes, 'id');

$cookie = PrefillCookie::get();
$classeIdCookie = isset($cookie['classe_id']) ? (int) $cookie['classe_id'] : null;

if (isset($_GET['classe_id'])) {
    $classeId = (int) $_GET['classe_id'];
} elseif ($classeIdCookie !== null && in_array($classeIdCookie, $idsClasses, true)) {
    $classeId = $classeIdCookie;
} else {
    $classeId = null;
    foreach ($classes as $classeCandidate) {
        if ((int) $classeCandidate['annee_debut'] === AnneeScolaire::enCours()) {
            $classeId = (int) $classeCandidate['id'];
            break;
        }
    }
    $classeId ??= $classes[0]['id'] ?? null;
}

if ($classeId !== null && !in_array($classeId, $idsClasses, true)) {
    $classeId = null;
}

// Cf. saisie/index.php : la classe choisie devient la "derniere classe"
// memorisee des sa selection, sans attendre l'enregistrement d'une seance.
if ($classeId !== null && $classeId !== $classeIdCookie) {
    PrefillCookie::set(['classe_id' => $classeId] + $cookie);
}

$classe = $classeId !== null ? ClasseRepository::findById($pdo, $classeId) : null;

$datesDisponibles = $classe !== null ? SeanceRepository::listerDatesDistinctesParClasse($pdo, $classeId) : [];
$datesDisponiblesValeurs = array_column($datesDisponibles, 'date_seance');

$datesSelectionnees = [];
if (isset($_GET['date_seance'])) {
    $demandees = (array) $_GET['date_seance'];
    $datesSelectionnees = array_values(array_intersect($datesDisponiblesValeurs, $demandees));
    $datesSelectionnees = array_slice($datesSelectionnees, 0, GraphiqueTours::MAX_SERIES);
}

$parEleve = [];
if ($classe !== null && $datesSelectionnees !== []) {
    $lignes = SeanceRepository::findByClasseEtDates($pdo, $classeId, $datesSelectionnees);
    foreach ($lignes as $ligne) {
        $eid = (int) $ligne['eleve_id'];
        if (!isset($parEleve[$eid])) {
            $parEleve[$eid] = ['prenom' => $ligne['prenom'], 'nb' => 0];
        }
        $parEleve[$eid]['nb']++;
    }
    uasort($parEleve, static fn (array $a, array $b): int => $a['prenom'] <=> $b['prenom']);
}

$etape = 1;
if ($classe !== null) {
    $etape = $datesSelectionnees !== [] ? 3 : 2;
}
Layout::debut('Exporter les résultats — Course de fond CM2', ['large' => true]);
?>
<a class="retour" href="/saisie/index.php">&larr; Saisie</a>
<h1>Exporter les résultats d'une classe</h1>

<?php if ($classes === []): ?>
    <p>Aucune classe n'a encore été créée.</p>
<?php else: ?>
    <div class="section-lbl">Classe</div>
    <form method="get" action="/graphiques/export.php">
        <div class="selectbox" style="max-width:420px;margin-bottom:16px">
            <select id="classe_id" name="classe_id" onchange="this.form.submit()">
                <?php foreach ($classes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $classeId ? 'selected' : '' ?>>
                        <?= $c['annee_debut'] ?>/<?= $c['annee_debut'] + 1 ?> - <?= htmlspecialchars($c['enseignant']) ?><?= $c['libelle_classe'] ? ' - ' . htmlspecialchars($c['libelle_classe']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= Icone::svg('caret-down') ?>
        </div>
        <noscript><button type="submit" class="btn-secondary">Valider</button></noscript>
    </form>

    <?php if ($etape >= 2 && $classe !== null): ?>
        <?php if ($datesDisponibles === []): ?>
            <p>Aucune séance saisie pour cette classe.</p>
        <?php else: ?>
            <h2>Dates de séance (<?= GraphiqueTours::MAX_SERIES ?> maximum)</h2>
            <form method="get" action="/graphiques/export.php">
                <input type="hidden" name="classe_id" value="<?= $classeId ?>">
                <div id="cases-dates">
                    <?php foreach ($datesDisponibles as $i => $d): ?>
                        <label class="checkrow">
                            <input type="checkbox" name="date_seance[]" value="<?= htmlspecialchars($d['date_seance']) ?>"
                                <?= ($etape === 3 && in_array($d['date_seance'], $datesSelectionnees, true)) || ($etape === 2 && $i === 0) ? 'checked' : '' ?>>
                            <span class="checkname">
                                <?= htmlspecialchars((new DateTimeImmutable($d['date_seance']))->format('d-m-Y')) ?>
                                — <?= (int) $d['nb_eleves'] ?> élève(s)
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn-secondary" style="width:auto">Continuer</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($etape === 3): ?>
        <?php if ($parEleve === []): ?>
            <p>Aucun élève n'a de séance parmi les dates choisies.</p>
        <?php else: ?>
            <h2>Élèves à inclure</h2>
            <form method="get">
                <input type="hidden" name="classe_id" value="<?= $classeId ?>">
                <?php foreach ($datesSelectionnees as $d): ?>
                    <input type="hidden" name="date_seance[]" value="<?= htmlspecialchars($d) ?>">
                <?php endforeach; ?>

                <label class="checkrow"><input type="checkbox" id="tout-selectionner" checked> <span class="checkname">Tout sélectionner</span></label>
                <div id="cases-eleves">
                    <?php foreach ($parEleve as $eid => $info): ?>
                        <label class="checkrow">
                            <input type="checkbox" name="eleve_id[]" value="<?= $eid ?>" checked>
                            <span class="checkname"><?= htmlspecialchars($info['prenom']) ?> — <?= (int) $info['nb'] ?>/<?= count($datesSelectionnees) ?> séance(s)</span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="actions">
                    <button type="submit" formaction="/graphiques/pdf.php" class="btn-block"><?= Icone::svg('file-pdf') ?> Exporter en PDF</button>
                    <button type="submit" formaction="/graphiques/csv.php" class="btn-secondary btn-block">Exporter en CSV</button>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<script>
(function () {
    var conteneurDates = document.getElementById('cases-dates');
    if (conteneurDates) {
        var max = <?= GraphiqueTours::MAX_SERIES ?>;
        var casesDates = conteneurDates.querySelectorAll('input[type="checkbox"]');
        var majDates = function () {
            var coches = conteneurDates.querySelectorAll('input[type="checkbox"]:checked').length;
            casesDates.forEach(function (c) { c.disabled = !c.checked && coches >= max; });
        };
        casesDates.forEach(function (c) { c.addEventListener('change', majDates); });
        majDates();
    }

    var toutSelectionner = document.getElementById('tout-selectionner');
    var conteneurEleves = document.getElementById('cases-eleves');
    if (toutSelectionner && conteneurEleves) {
        var casesEleves = conteneurEleves.querySelectorAll('input[type="checkbox"]');
        toutSelectionner.addEventListener('change', function () {
            casesEleves.forEach(function (c) { c.checked = toutSelectionner.checked; });
        });
        conteneurEleves.addEventListener('change', function () {
            var tous = Array.prototype.every.call(casesEleves, function (c) { return c.checked; });
            toutSelectionner.checked = tous;
        });
    }
})();
</script>
<?php Layout::fin(); ?>
