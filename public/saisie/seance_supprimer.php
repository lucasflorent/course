<?php

declare(strict_types=1);

use App\Auth\SiteAuth;
use App\Config\Database;
use App\Repository\SeanceRepository;
use App\Support\Csrf;
use App\View\Layout;

require __DIR__ . '/../../config/bootstrap.php';

SiteAuth::requireAuth();

$pdo = Database::pdo();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$seance = $id > 0 ? SeanceRepository::findByIdAvecContexte($pdo, $id) : null;

if ($seance === null) {
    header('Location: /saisie/index.php');
    exit;
}

$eleveId = (int) $seance['eleve_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    SeanceRepository::delete($pdo, $id);
    header('Location: /saisie/eleve.php?eleve_id=' . $eleveId);
    exit;
}

Layout::debut('Supprimer une séance — Course de fond CM2', ['eleveId' => $eleveId, 'navActive' => 'saisie']);
?>
<a class="retour" href="/saisie/eleve.php?eleve_id=<?= $eleveId ?>">&larr; <?= htmlspecialchars($seance['prenom']) ?></a>
<h3>Supprimer la séance ?</h3>
<p>
    <strong><?= htmlspecialchars($seance['prenom']) ?></strong>
    — <?= htmlspecialchars((new DateTimeImmutable($seance['date_seance']))->format('d-m-Y')) ?>
</p>
<p class="erreur">Cette action supprimera aussi définitivement tous les temps de passage saisis pour cette séance.</p>
<form method="post" class="actions" style="max-width:380px">
    <?= Csrf::champHtml() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <button type="submit" class="btn-danger">Supprimer définitivement</button>
    <a class="btn btn-secondary" href="/saisie/eleve.php?eleve_id=<?= $eleveId ?>">Annuler</a>
</form>
<?php Layout::fin(); ?>
