<?php

declare(strict_types=1);

use App\Auth\SiteAuth;
use App\Config\Database;
use App\Repository\ClasseRepository;
use App\Repository\EleveRepository;
use App\Support\AnneeScolaire;
use App\Support\PrefillCookie;

require __DIR__ . '/../../config/bootstrap.php';

SiteAuth::requireAuth();

$pdo = Database::pdo();
$annees = ClasseRepository::listeAnnees($pdo);

$cookie = PrefillCookie::get();
$classeCookie = isset($cookie['classe_id']) ? ClasseRepository::findById($pdo, (int) $cookie['classe_id']) : null;

if (isset($_GET['annee'])) {
    $anneeSelectionnee = (int) $_GET['annee'];
} elseif ($classeCookie !== null) {
    $anneeSelectionnee = (int) $classeCookie['annee_debut'];
} elseif (in_array(AnneeScolaire::enCours(), $annees, true)) {
    $anneeSelectionnee = AnneeScolaire::enCours();
} else {
    $anneeSelectionnee = $annees[0] ?? null;
}

$classes = $anneeSelectionnee !== null ? ClasseRepository::findByAnnee($pdo, $anneeSelectionnee) : [];
$idsClasses = array_column($classes, 'id');

if (isset($_GET['classe_id'])) {
    $classeIdSelectionnee = (int) $_GET['classe_id'];
} elseif ($classeCookie !== null && (int) $classeCookie['annee_debut'] === $anneeSelectionnee) {
    $classeIdSelectionnee = (int) $classeCookie['id'];
} elseif ($classes !== []) {
    $classeIdSelectionnee = (int) $classes[0]['id'];
} else {
    $classeIdSelectionnee = null;
}

if ($classeIdSelectionnee !== null && !in_array($classeIdSelectionnee, $idsClasses, true)) {
    $classeIdSelectionnee = null;
}

$eleves = $classeIdSelectionnee !== null ? EleveRepository::findByClasse($pdo, $classeIdSelectionnee) : [];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choisir un élève — Course de fond CM2</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<a class="retour" href="/index.php">&larr; Accueil</a>
<h1>Qui es-tu ?</h1>

<?php if ($annees === []): ?>
    <p>Aucune classe n'a encore été créée. Demande à ton enseignant de contacter l'administrateur.</p>
<?php else: ?>
    <form method="get" action="/saisie/index.php">
        <label for="annee">Année scolaire</label>
        <select id="annee" name="annee" onchange="this.form.submit()">
            <?php foreach ($annees as $annee): ?>
                <option value="<?= $annee ?>" <?= $annee === $anneeSelectionnee ? 'selected' : '' ?>>
                    <?= $annee ?>/<?= $annee + 1 ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($classes !== []): ?>
            <label for="classe_id">Classe</label>
            <select id="classe_id" name="classe_id" onchange="this.form.submit()">
                <?php foreach ($classes as $classe): ?>
                    <option value="<?= (int) $classe['id'] ?>" <?= (int) $classe['id'] === $classeIdSelectionnee ? 'selected' : '' ?>>
                        <?= htmlspecialchars($classe['enseignant']) ?><?= $classe['libelle_classe'] ? ' — ' . htmlspecialchars($classe['libelle_classe']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <p>Aucune classe pour cette année.</p>
        <?php endif; ?>

        <noscript><button type="submit">Valider</button></noscript>
    </form>

    <?php if ($classeIdSelectionnee !== null): ?>
        <form method="get" action="/saisie/eleve.php">
            <label for="eleve_id">Élève</label>
            <?php if ($eleves === []): ?>
                <p>Aucun élève dans cette classe.</p>
            <?php else: ?>
                <select id="eleve_id" name="eleve_id">
                    <?php foreach ($eleves as $eleve): ?>
                        <option value="<?= (int) $eleve['id'] ?>"><?= htmlspecialchars($eleve['prenom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bouton-large">C'est parti !</button>
            <?php endif; ?>
        </form>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>
