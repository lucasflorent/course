<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Repository\ClasseRepository;
use App\Support\Icone;
use App\View\Layout;

require __DIR__ . '/../../../config/bootstrap.php';

AdminAuth::requireLogin();

$pdo = Database::pdo();
$classes = ClasseRepository::findAllAvecComptes($pdo);

$parEnseignant = [];
foreach ($classes as $classe) {
    $parEnseignant[$classe['enseignant']][] = $classe;
}

Layout::debut('Classes — Administration', ['admin' => true, 'adminNav' => 'teachers']);
?>
<h3 style="margin-bottom:2px">Enseignants &amp; classes</h3>
<p class="hint">Toutes les classes, toutes années confondues.</p>

<?php if ($classes === []): ?>
    <p>Aucune classe pour le moment.</p>
<?php else: ?>
    <?php foreach ($parEnseignant as $enseignant => $classesEnseignant): ?>
        <div class="teachercard">
            <div class="teachercard-hdr">
                <strong><?= htmlspecialchars($enseignant) ?></strong>
            </div>
            <div class="chiprow">
                <?php foreach ($classesEnseignant as $classe): ?>
                    <div class="chip-groupe">
                        <a class="chip" href="/admin/eleves/index.php?classe_id=<?= (int) $classe['id'] ?>">
                            <?= (int) $classe['annee_debut'] ?>/<?= (int) $classe['annee_debut'] + 1 ?>
                            <?= $classe['libelle_classe'] ? ' · ' . htmlspecialchars($classe['libelle_classe']) : '' ?>
                            · <?= (int) $classe['nb_eleves'] ?> élève<?= (int) $classe['nb_eleves'] > 1 ? 's' : '' ?>
                        </a>
                        <a class="chip-icon-btn" href="/admin/classes/form.php?id=<?= (int) $classe['id'] ?>" title="Modifier"><?= Icone::svg('pencil-simple', 15) ?></a>
                        <a class="chip-icon-btn danger" href="/admin/classes/supprimer.php?id=<?= (int) $classe['id'] ?>" title="Supprimer"><?= Icone::svg('trash', 15) ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<a class="btn btn-secondary" style="width:auto;margin-top:6px" href="/admin/classes/form.php">
    <?= Icone::svg('plus') ?> Ajouter une classe
</a>
<?php Layout::fin(); ?>
