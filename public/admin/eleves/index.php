<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Repository\ClasseRepository;
use App\Repository\EleveRepository;
use App\Support\Icone;
use App\View\Layout;

require __DIR__ . '/../../../config/bootstrap.php';

AdminAuth::requireLogin();

$pdo = Database::pdo();
$classeId = (int) ($_GET['classe_id'] ?? 0);
$classe = $classeId > 0 ? ClasseRepository::findById($pdo, $classeId) : null;

if ($classe === null && $classeId > 0) {
    header('Location: /admin/eleves/index.php');
    exit;
}

$eleves = $classe !== null ? EleveRepository::findByClasse($pdo, $classeId) : [];
$classes = $classe === null ? ClasseRepository::findAllAvecComptes($pdo) : [];
Layout::debut('Élèves — Administration', ['admin' => true, 'adminNav' => 'students']);
?>
<a class="retour" href="/admin/classes/index.php">&larr; Classes</a>
<h3 style="margin-bottom:2px">Élèves</h3>
<?php if ($classe !== null): ?>
    <p class="hint"><?= htmlspecialchars($classe['enseignant']) ?> — <?= (int) $classe['annee_debut'] ?>/<?= (int) $classe['annee_debut'] + 1 ?><?= $classe['libelle_classe'] ? ' · ' . htmlspecialchars($classe['libelle_classe']) : '' ?></p>
    <?php if (isset($_GET['importes'])): ?>
        <p class="succes"><?= (int) $_GET['importes'] ?> élève(s) importé(s).</p>
    <?php endif; ?>
    <div class="actions" style="margin-bottom:1rem">
        <a class="btn" href="/admin/eleves/form.php?classe_id=<?= $classeId ?>"><?= Icone::svg('plus') ?> Ajouter un élève</a>
        <a class="btn btn-secondary" href="/admin/eleves/import.php?classe_id=<?= $classeId ?>"><?= Icone::svg('upload-simple') ?> Importer un CSV</a>
    </div>
    <?php if ($eleves === []): ?>
        <p>Aucun élève dans cette classe pour le moment.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr><th>Prénom</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($eleves as $eleve): ?>
                <tr>
                    <td><?= htmlspecialchars($eleve['prenom']) ?></td>
                    <td class="actions">
                        <a class="btn btn-secondary" href="/admin/eleves/form.php?id=<?= (int) $eleve['id'] ?>">Modifier</a>
                        <a class="btn btn-danger" href="/admin/eleves/supprimer.php?id=<?= (int) $eleve['id'] ?>">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php else: ?>
    <p class="hint">Sélectionnez une classe pour gérer ses élèves.</p>
    <?php if ($classes === []): ?>
        <p>Aucune classe pour le moment.</p>
    <?php else: ?>
        <div class="chiprow">
            <?php foreach ($classes as $classeVue): ?>
                <div class="chip-groupe">
                    <a class="chip" href="/admin/eleves/index.php?classe_id=<?= (int) $classeVue['id'] ?>">
                        <?= (int) $classeVue['annee_debut'] ?>/<?= (int) $classeVue['annee_debut'] + 1 ?>
                        <?= $classeVue['libelle_classe'] ? ' · ' . htmlspecialchars($classeVue['libelle_classe']) : '' ?>
                        · <?= (int) $classeVue['nb_eleves'] ?> élève<?= (int) $classeVue['nb_eleves'] > 1 ? 's' : '' ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php Layout::fin(); ?>
