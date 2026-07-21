<?php

declare(strict_types=1);

use App\Auth\SiteAuth;
use App\Config\Database;
use App\Repository\ClasseRepository;
use App\Repository\EleveRepository;
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
    $classeIdSelectionnee = (int) $_GET['classe_id'];
} elseif ($classeIdCookie !== null && in_array($classeIdCookie, $idsClasses, true)) {
    $classeIdSelectionnee = $classeIdCookie;
} else {
    $classeIdSelectionnee = null;
    foreach ($classes as $classe) {
        if ((int) $classe['annee_debut'] === AnneeScolaire::enCours()) {
            $classeIdSelectionnee = (int) $classe['id'];
            break;
        }
    }
    $classeIdSelectionnee ??= isset($classes[0]['id']) ? (int) $classes[0]['id'] : null;
}

if ($classeIdSelectionnee !== null && !in_array($classeIdSelectionnee, $idsClasses, true)) {
    $classeIdSelectionnee = null;
}

// La classe choisie (dropdown ou lien direct) devient la "derniere classe"
// memorisee pour les prochaines visites, sans attendre qu'une seance soit
// enregistree — sinon revenir sur cette page (nav basse, "Changer d'eleve")
// retombe sur l'ancienne classe du cookie au lieu de celle qu'on vient de
// choisir.
if ($classeIdSelectionnee !== null && $classeIdSelectionnee !== $classeIdCookie) {
    PrefillCookie::set(['classe_id' => $classeIdSelectionnee] + $cookie);
}

$eleves = $classeIdSelectionnee !== null ? EleveRepository::findByClasse($pdo, $classeIdSelectionnee) : [];
$classeSelectionnee = null;
foreach ($classes as $classe) {
    if ((int) $classe['id'] === $classeIdSelectionnee) {
        $classeSelectionnee = $classe;
        break;
    }
}

Layout::debut('Choisir un élève — Course de fond CM2');
?>
<a class="retour" href="/index.php">&larr; Accueil</a>
<div class="hdr">
    <div class="hdr-txt">
        <div class="hdr-name">Qui court aujourd'hui ?</div>
        <?php if ($classeSelectionnee !== null): ?>
            <div class="hdr-sub"><?= htmlspecialchars($classeSelectionnee['enseignant']) ?><?= $classeSelectionnee['libelle_classe'] ? ' - ' . htmlspecialchars($classeSelectionnee['libelle_classe']) : '' ?></div>
        <?php endif; ?>
    </div>
</div>

<?php if ($classes === []): ?>
    <p>Aucune classe n'a encore été créée. Demande à ton enseignant de contacter l'administrateur.</p>
<?php else: ?>
    <div class="section-lbl">Classe</div>
    <form method="get" action="/saisie/index.php">
        <div class="selectbox" style="margin-bottom:16px">
            <select id="classe_id" name="classe_id" onchange="this.form.submit()">
                <?php foreach ($classes as $classe): ?>
                    <option value="<?= (int) $classe['id'] ?>" <?= (int) $classe['id'] === $classeIdSelectionnee ? 'selected' : '' ?>>
                        <?= $classe['annee_debut'] ?>/<?= $classe['annee_debut'] + 1 ?> - <?= htmlspecialchars($classe['enseignant']) ?><?= $classe['libelle_classe'] ? ' - ' . htmlspecialchars($classe['libelle_classe']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= Icone::svg('caret-down') ?>
        </div>
        <noscript><button type="submit" class="btn-secondary">Valider</button></noscript>
    </form>

    <?php if ($classeIdSelectionnee !== null): ?>
        <div class="section-lbl">Élève</div>
        <?php if ($eleves === []): ?>
            <p>Aucun élève dans cette classe.</p>
        <?php else: ?>
            <div class="stugrid">
                <?php foreach ($eleves as $eleve): ?>
                    <a class="stubtn" href="/saisie/eleve.php?eleve_id=<?= (int) $eleve['id'] ?>">
                        <span class="stuname"><?= htmlspecialchars($eleve['prenom']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
<?php Layout::fin(); ?>
