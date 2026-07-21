<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Repository\ClasseRepository;
use App\Support\Csrf;
use App\View\Layout;

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
Layout::debut('Supprimer une classe — Administration', ['admin' => true, 'adminNav' => 'teachers']);
?>
<a class="retour" href="/admin/classes/index.php">&larr; Classes</a>
<h3>Supprimer la classe ?</h3>
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
<form method="post" class="actions" style="max-width:380px">
    <?= Csrf::champHtml() ?>
    <input type="hidden" name="id" value="<?= (int) $classe['id'] ?>">
    <button type="submit" class="btn-danger">Supprimer définitivement</button>
    <a class="btn btn-secondary" href="/admin/classes/index.php">Annuler</a>
</form>
<?php Layout::fin(); ?>
