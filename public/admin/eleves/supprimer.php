<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Repository\EleveRepository;
use App\Support\Csrf;
use App\View\Layout;

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
Layout::debut('Supprimer un élève — Administration', ['admin' => true, 'adminNav' => 'students']);
?>
<a class="retour" href="/admin/eleves/index.php?classe_id=<?= $classeId ?>">&larr; Élèves</a>
<h3>Supprimer l'élève ?</h3>
<p><strong><?= htmlspecialchars($eleve['prenom']) ?></strong></p>
<p class="erreur">Cette action supprimera aussi définitivement ses séances et temps de passage.</p>
<form method="post" class="actions" style="max-width:380px">
    <?= Csrf::champHtml() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <button type="submit" class="btn-danger">Supprimer définitivement</button>
    <a class="btn btn-secondary" href="/admin/eleves/index.php?classe_id=<?= $classeId ?>">Annuler</a>
</form>
<?php Layout::fin(); ?>
