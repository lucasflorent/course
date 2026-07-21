<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Repository\ClasseRepository;
use App\Repository\EleveRepository;
use App\Support\Csrf;
use App\View\Layout;

require __DIR__ . '/../../../config/bootstrap.php';

AdminAuth::requireLogin();

$pdo = Database::pdo();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$eleveExistant = $id !== null ? EleveRepository::findById($pdo, $id) : null;

if ($id !== null && $eleveExistant === null) {
    header('Location: /admin/classes/index.php');
    exit;
}

$classeId = $eleveExistant['classe_id'] ?? (int) ($_GET['classe_id'] ?? 0);
$classe = $classeId > 0 ? ClasseRepository::findById($pdo, $classeId) : null;

if ($classe === null) {
    header('Location: /admin/classes/index.php');
    exit;
}

$prenom = $eleveExistant['prenom'] ?? '';
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $prenom = trim((string) ($_POST['prenom'] ?? ''));

    if ($prenom === '') {
        $erreurs[] = 'Le prénom est obligatoire.';
    }

    if ($erreurs === []) {
        if ($id !== null) {
            EleveRepository::update($pdo, $id, $prenom);
        } else {
            EleveRepository::create($pdo, $classeId, $prenom);
        }

        header('Location: /admin/eleves/index.php?classe_id=' . $classeId);
        exit;
    }
}
Layout::debut(($id !== null ? 'Modifier' : 'Nouvel') . ' élève — Administration', ['admin' => true, 'adminNav' => 'students']);
?>
<a class="retour" href="/admin/eleves/index.php?classe_id=<?= $classeId ?>">&larr; Élèves</a>
<h3 style="margin-bottom:2px"><?= $id !== null ? "Modifier l'élève" : 'Nouvel élève' ?></h3>
<p class="hint">Classe : <?= htmlspecialchars($classe['enseignant']) ?> (<?= (int) $classe['annee_debut'] ?>/<?= (int) $classe['annee_debut'] + 1 ?>)</p>
<?php foreach ($erreurs as $erreur): ?>
    <p class="erreur"><?= htmlspecialchars($erreur) ?></p>
<?php endforeach; ?>
<form method="post" style="max-width:340px">
    <?= Csrf::champHtml() ?>
    <div class="field">
        <label for="prenom">Prénom</label>
        <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($prenom) ?>" required autofocus>
    </div>
    <button type="submit" class="btn-block">Enregistrer</button>
</form>
<?php Layout::fin(); ?>
