<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Repository\ClasseRepository;
use App\Repository\EleveRepository;
use App\Support\Csrf;

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
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $id !== null ? 'Modifier' : 'Nouvel' ?> élève — Administration</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<a class="retour" href="/admin/eleves/index.php?classe_id=<?= $classeId ?>">&larr; Élèves</a>
<h1><?= $id !== null ? 'Modifier l\'élève' : 'Nouvel élève' ?></h1>
<p>Classe : <?= htmlspecialchars($classe['enseignant']) ?> (<?= (int) $classe['annee_debut'] ?>/<?= (int) $classe['annee_debut'] + 1 ?>)</p>
<?php foreach ($erreurs as $erreur): ?>
    <p class="erreur"><?= htmlspecialchars($erreur) ?></p>
<?php endforeach; ?>
<form method="post">
    <?= Csrf::champHtml() ?>
    <label for="prenom">Prénom</label>
    <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($prenom) ?>" required autofocus>
    <button type="submit" class="bouton-large">Enregistrer</button>
</form>
</body>
</html>
