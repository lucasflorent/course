<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Repository\EleveRepository;
use App\Support\Csrf;

require __DIR__ . '/../../../config/bootstrap.php';

AdminAuth::requireLogin();

$pdo = Database::pdo();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$eleve = $id > 0 ? EleveRepository::findById($pdo, $id) : null;

if ($eleve === null) {
    header('Location: /admin/classes/index.php');
    exit;
}

$classeId = (int) $eleve['classe_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    EleveRepository::delete($pdo, $id);
    header('Location: /admin/eleves/index.php?classe_id=' . $classeId);
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supprimer un élève — Administration</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<a class="retour" href="/admin/eleves/index.php?classe_id=<?= $classeId ?>">&larr; Élèves</a>
<h1>Supprimer l'élève ?</h1>
<p><strong><?= htmlspecialchars($eleve['prenom']) ?></strong></p>
<p class="erreur">Cette action supprimera aussi définitivement ses séances et temps de passage.</p>
<form method="post" class="actions">
    <?= Csrf::champHtml() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <button type="submit" class="bouton-danger">Supprimer définitivement</button>
    <a class="bouton bouton-secondaire" href="/admin/eleves/index.php?classe_id=<?= $classeId ?>">Annuler</a>
</form>
</body>
</html>
