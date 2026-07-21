<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Repository\ClasseRepository;
use App\Support\AnneeScolaire;
use App\Support\Csrf;
use App\View\Layout;

require __DIR__ . '/../../../config/bootstrap.php';

AdminAuth::requireLogin();

$pdo = Database::pdo();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$classeExistante = $id !== null ? ClasseRepository::findById($pdo, $id) : null;

if ($id !== null && $classeExistante === null) {
    header('Location: /admin/classes/index.php');
    exit;
}

$valeurs = [
    'enseignant' => $classeExistante['enseignant'] ?? '',
    'annee_debut' => $classeExistante['annee_debut'] ?? AnneeScolaire::enCours(),
    'libelle_classe' => $classeExistante['libelle_classe'] ?? '',
];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $valeurs['enseignant'] = trim((string) ($_POST['enseignant'] ?? ''));
    $valeurs['annee_debut'] = trim((string) ($_POST['annee_debut'] ?? ''));
    $valeurs['libelle_classe'] = trim((string) ($_POST['libelle_classe'] ?? ''));

    if ($valeurs['enseignant'] === '') {
        $erreurs[] = "Le nom de l'enseignant est obligatoire.";
    }

    if (!preg_match('/^\d{4}$/', $valeurs['annee_debut'])) {
        $erreurs[] = "L'année scolaire doit être une année à 4 chiffres (ex. 2025).";
    }

    if ($erreurs === []) {
        $libelle = $valeurs['libelle_classe'] !== '' ? $valeurs['libelle_classe'] : null;
        $anneeDebut = (int) $valeurs['annee_debut'];

        if ($id !== null) {
            ClasseRepository::update($pdo, $id, $valeurs['enseignant'], $anneeDebut, $libelle);
        } else {
            ClasseRepository::create($pdo, $valeurs['enseignant'], $anneeDebut, $libelle);
        }

        header('Location: /admin/classes/index.php');
        exit;
    }
}

$enseignants = ClasseRepository::listeEnseignants($pdo);
Layout::debut(($id !== null ? 'Modifier' : 'Nouvelle') . ' classe — Administration', ['admin' => true, 'adminNav' => 'teachers']);
?>
<a class="retour" href="/admin/classes/index.php">&larr; Classes</a>
<h3><?= $id !== null ? 'Modifier la classe' : 'Nouvelle classe' ?></h3>
<?php foreach ($erreurs as $erreur): ?>
    <p class="erreur"><?= htmlspecialchars($erreur) ?></p>
<?php endforeach; ?>
<form method="post" style="max-width:380px">
    <?= Csrf::champHtml() ?>
    <div class="field">
        <label for="enseignant">Enseignant</label>
        <input type="text" id="enseignant" name="enseignant" list="liste-enseignants"
               value="<?= htmlspecialchars($valeurs['enseignant']) ?>" required autofocus>
        <datalist id="liste-enseignants">
            <?php foreach ($enseignants as $nom): ?>
                <option value="<?= htmlspecialchars($nom) ?>">
            <?php endforeach; ?>
        </datalist>
    </div>

    <div class="field">
        <label for="annee_debut">Année scolaire (année de début, ex. 2025 pour 2025/2026)</label>
        <input type="number" id="annee_debut" name="annee_debut" min="2000" max="2100"
               value="<?= htmlspecialchars((string) $valeurs['annee_debut']) ?>" required>
    </div>

    <div class="field">
        <label for="libelle_classe">Libellé (optionnel)</label>
        <input type="text" id="libelle_classe" name="libelle_classe" placeholder="ex. CM2 A"
               value="<?= htmlspecialchars($valeurs['libelle_classe']) ?>">
    </div>

    <button type="submit" class="btn-block">Enregistrer</button>
</form>
<?php Layout::fin(); ?>
