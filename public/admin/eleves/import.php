<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Csv\CsvPreview;
use App\Repository\ClasseRepository;
use App\Repository\EleveRepository;
use App\Support\Csrf;
use App\Support\Icone;
use App\View\Layout;

require __DIR__ . '/../../../config/bootstrap.php';

AdminAuth::requireLogin();

$pdo = Database::pdo();
$classeId = (int) ($_GET['classe_id'] ?? $_POST['classe_id'] ?? 0);
$classe = $classeId > 0 ? ClasseRepository::findById($pdo, $classeId) : null;

if ($classe === null) {
    header('Location: /admin/classes/index.php');
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['etape'] ?? '') === 'upload') {
    Csrf::requireValid();

    if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        $erreur = 'Veuillez choisir un fichier CSV.';
    } elseif ($_FILES['csv']['size'] > 1_000_000) {
        $erreur = 'Fichier trop volumineux (1 Mo maximum).';
    } else {
        $_SESSION['import_csv_brut'] = file_get_contents($_FILES['csv']['tmp_name']);
        $_SESSION['import_csv_classe_id'] = $classeId;
    }
}

$brut = ($_SESSION['import_csv_classe_id'] ?? null) === $classeId
    ? ($_SESSION['import_csv_brut'] ?? null)
    : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['etape'] ?? '') === 'confirmer' && $brut !== null) {
    Csrf::requireValid();

    $apercus = CsvPreview::genererApercus($brut);
    $cle = (string) ($_POST['config'] ?? '');

    if (!isset($apercus[$cle]) || !$apercus[$cle]['valide']) {
        $erreur = 'Configuration invalide ou non lisible, veuillez en choisir une autre.';
    } else {
        $colonneIndex = (int) ($_POST['colonne'][$cle] ?? 0);
        $premiereLigneEntete = isset($_POST['entete']);

        $lignes = CsvPreview::parseComplet($brut, $apercus[$cle]['encodage'], $apercus[$cle]['separateur']);
        if ($premiereLigneEntete) {
            array_shift($lignes);
        }

        $prenoms = [];
        foreach ($lignes as $ligne) {
            if (isset($ligne[$colonneIndex]) && trim($ligne[$colonneIndex]) !== '') {
                $prenoms[] = trim($ligne[$colonneIndex]);
            }
        }

        $nb = EleveRepository::createBulk($pdo, $classeId, $prenoms);
        unset($_SESSION['import_csv_brut'], $_SESSION['import_csv_classe_id']);

        header('Location: /admin/eleves/index.php?classe_id=' . $classeId . '&importes=' . $nb);
        exit;
    }
}

$apercus = $brut !== null ? CsvPreview::genererApercus($brut) : null;
Layout::debut('Importer des élèves — Administration', ['admin' => true, 'adminNav' => 'students']);
?>
<a class="retour" href="/admin/eleves/index.php?classe_id=<?= $classeId ?>">&larr; Élèves</a>
<h3 style="margin-bottom:2px">Élèves &amp; import</h3>
<p class="hint">Importer une liste de prénoms depuis un fichier CSV pour <?= htmlspecialchars($classe['enseignant']) ?>.</p>
<?php if ($erreur): ?><p class="erreur"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>

<?php if ($apercus === null): ?>
    <form method="post" enctype="multipart/form-data">
        <?= Csrf::champHtml() ?>
        <input type="hidden" name="etape" value="upload">
        <input type="hidden" name="classe_id" value="<?= $classeId ?>">
        <div class="importbox">
            <?= Icone::svg('upload-simple', 28) ?>
            <div style="font-weight:500">Choisir un fichier CSV (liste des prénoms)</div>
            <input type="file" id="csv" name="csv" accept=".csv,text/csv" required style="max-width:320px">
        </div>
        <button type="submit" class="btn-block">Aperçu</button>
    </form>
<?php else: ?>
    <p>Choisissez la combinaison qui affiche correctement les prénoms (accents lisibles, colonnes cohérentes) :</p>
    <form method="post">
        <?= Csrf::champHtml() ?>
        <input type="hidden" name="etape" value="confirmer">
        <input type="hidden" name="classe_id" value="<?= $classeId ?>">

        <label><input type="checkbox" name="entete" value="1" checked style="width:auto;min-height:0"> La première ligne est un en-tête (non importée)</label>

        <div class="previewgrid">
            <?php foreach ($apercus as $cle => $apercu): ?>
                <div class="carte pvopt<?= $apercu['valide'] ? '' : ' invalide' ?>">
                    <label>
                        <input type="radio" name="config" value="<?= htmlspecialchars($cle) ?>" style="width:auto;min-height:0"
                            <?= $apercu['valide'] ? '' : 'disabled' ?>>
                        <strong><?= htmlspecialchars($apercu['label']) ?></strong>
                    </label>
                    <?php if (!$apercu['valide']): ?>
                        <p><em>Fichier illisible avec cet encodage.</em></p>
                    <?php else: ?>
                        <p>
                            <?= (int) $apercu['nb_lignes_total'] ?> ligne(s) de données
                            <?php if ($apercu['nb_colonnes_incoherentes'] > 0): ?>
                                — <span class="erreur"><?= (int) $apercu['nb_colonnes_incoherentes'] ?> ligne(s) au nombre de colonnes incohérent</span>
                            <?php endif; ?>
                        </p>
                        <label>Colonne identifiant
                            <select name="colonne[<?= htmlspecialchars($cle) ?>]">
                                <?php foreach ($apercu['entetes'] as $index => $entete): ?>
                                    <option value="<?= $index ?>"><?= htmlspecialchars($entete !== '' ? $entete : 'Colonne ' . ($index + 1)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <table>
                            <tbody>
                            <?php foreach ($apercu['lignes_apercu'] as $ligne): ?>
                                <tr><?php foreach ($ligne as $champ): ?><td><?= htmlspecialchars($champ) ?></td><?php endforeach; ?></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn-block">Importer les élèves</button>
    </form>
<?php endif; ?>
<?php Layout::fin(); ?>
