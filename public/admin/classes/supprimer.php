<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Repository\ClasseRepository;
use App\Support\Csrf;

require __DIR__ . '/../../../config/bootstrap.php';

AdminAuth::requireLogin();

$pdo = Database::pdo();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$classe = $id > 0 ? ClasseRepository::findById($pdo, $id) : null;

if ($classe === null) {
    header('Location: /admin/classes/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    ClasseRepository::delete($pdo, $id);
    header('Location: /admin/classes/index.php');
    exit;
}

$stats = ClasseRepository::statistiques($pdo, $id);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supprimer une classe — Administration</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<a class="retour" href="/admin/classes/index.php">&larr; Classes</a>
<h1>Supprimer la classe ?</h1>
<p>
    <strong><?= htmlspecialchars($classe['enseignant']) ?></strong>
    — <?= (int) $classe['annee_debut'] ?>/<?= (int) $classe['annee_debut'] + 1 ?>
    <?php if (!empty($classe['libelle_classe'])): ?>(<?= htmlspecialchars($classe['libelle_classe']) ?>)<?php endif; ?>
</p>
<p class="erreur">
    Cette action supprimera aussi definitivement :
    <?= (int) $stats['nb_eleves'] ?> élève(s),
    <?= (int) $stats['nb_seances'] ?> séance(s) et
    <?= (int) $stats['nb_temps'] ?> temps de passage associés.
</p>
<form method="post" class="actions">
    <?= Csrf::champHtml() ?>
    <input type="hidden" name="id" value="<?= (int) $classe['id'] ?>">
    <button type="submit" class="bouton-danger">Supprimer définitivement</button>
    <a class="bouton bouton-secondaire" href="/admin/classes/index.php">Annuler</a>
</form>
</body>
</html>
