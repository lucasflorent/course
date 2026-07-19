<?php

declare(strict_types=1);

use App\Auth\SiteAuth;
use App\Config\Database;
use App\Repository\EleveRepository;
use App\Repository\SeanceRepository;
use App\Repository\TempsPassageRepository;
use App\Support\Csrf;
use App\Support\PrefillCookie;
use App\Support\Temps;

require __DIR__ . '/../../config/bootstrap.php';

SiteAuth::requireAuth();

$pdo = Database::pdo();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$seanceExistante = $id !== null ? SeanceRepository::findByIdAvecContexte($pdo, $id) : null;

if ($id !== null && $seanceExistante === null) {
    header('Location: /saisie/index.php');
    exit;
}

if ($seanceExistante !== null) {
    $eleveId = (int) $seanceExistante['eleve_id'];
    $eleve = $seanceExistante;
} else {
    $eleveId = (int) ($_GET['eleve_id'] ?? $_POST['eleve_id'] ?? 0);
    $eleve = $eleveId > 0 ? EleveRepository::findByIdAvecClasse($pdo, $eleveId) : null;
}

if ($eleve === null) {
    header('Location: /saisie/index.php');
    exit;
}

$cookie = PrefillCookie::get();

// Valeurs par defaut affichees dans le formulaire (avant prise en compte
// d'un eventuel POST, traite plus bas) : celles de la seance en edition
// (y compris si explicitement NULL, ne pas retomber sur le cookie dans ce
// cas), sinon celles du cookie de preremplissage pour une nouvelle seance.
if ($seanceExistante !== null) {
    $dateSeance = $seanceExistante['date_seance'];
    $longueurTourBrut = $seanceExistante['longueur_tour_m'] !== null ? (string) $seanceExistante['longueur_tour_m'] : '';
} else {
    $dateSeance = (string) ($cookie['date_seance'] ?? date('Y-m-d'));
    $longueurTourBrut = (string) ($cookie['longueur_tour_m'] ?? '');
}

$lignesAffichage = [];
if ($seanceExistante !== null) {
    foreach (TempsPassageRepository::findBySeance($pdo, $id) as $temps) {
        $lignesAffichage[] = [
            'valeur' => Temps::format((int) $temps['temps_cumule_s']),
            'incertain' => (bool) $temps['incertain'],
        ];
    }
}

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $dateSeance = (string) ($_POST['date_seance'] ?? '');
    $longueurTourBrut = trim((string) ($_POST['longueur_tour_m'] ?? ''));
    $tempsPostes = $_POST['temps'] ?? [];
    $incertainPostes = $_POST['incertain'] ?? [];

    // Reconstruit les lignes telles que soumises, pour un reaffichage fidele
    // en cas d'erreur.
    $lignesAffichage = [];
    foreach ($tempsPostes as $index => $valeur) {
        $lignesAffichage[] = [
            'valeur' => (string) $valeur,
            'incertain' => isset($incertainPostes[$index]),
        ];
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateSeance)) {
        $erreurs[] = 'La date de séance est obligatoire.';
    }

    $longueurTourM = null;
    if ($longueurTourBrut !== '') {
        if (!is_numeric($longueurTourBrut) || (float) $longueurTourBrut <= 0) {
            $erreurs[] = 'La longueur du tour doit être un nombre positif.';
        } else {
            $longueurTourM = (float) $longueurTourBrut;
        }
    }

    $lignesValidees = [];
    $dernierTemps = null;
    foreach ($tempsPostes as $index => $valeur) {
        $valeur = trim((string) $valeur);
        if ($valeur === '') {
            continue; // ligne laissee vide = tour non note, pas d'erreur
        }

        $secondes = Temps::toSeconds($valeur);
        if ($secondes === null) {
            $erreurs[] = 'Le temps "' . htmlspecialchars($valeur) . '" doit être au format mm:ss.';
            continue;
        }

        if ($dernierTemps !== null && $secondes <= $dernierTemps) {
            $erreurs[] = 'Les temps doivent être strictement croissants (' . Temps::format($secondes) . ' après ' . Temps::format($dernierTemps) . ').';
            continue;
        }

        $dernierTemps = $secondes;
        $lignesValidees[] = [
            'temps_cumule_s' => $secondes,
            'incertain' => isset($incertainPostes[$index]),
        ];
    }

    if ($erreurs === []) {
        if ($id !== null) {
            SeanceRepository::update($pdo, $id, $dateSeance, $longueurTourM);
            $seanceId = $id;
        } else {
            $seanceId = SeanceRepository::create($pdo, $eleveId, $dateSeance, $longueurTourM);
        }

        TempsPassageRepository::replaceAll($pdo, $seanceId, $lignesValidees);

        PrefillCookie::set([
            'classe_id' => (int) $eleve['classe_id'],
            'date_seance' => $dateSeance,
            'longueur_tour_m' => $longueurTourM,
        ]);

        header('Location: /saisie/eleve.php?eleve_id=' . $eleveId);
        exit;
    }
}

// Au moins quelques lignes vides pour commencer la saisie d'une nouvelle seance.
if ($lignesAffichage === []) {
    for ($i = 0; $i < 6; $i++) {
        $lignesAffichage[] = ['valeur' => '', 'incertain' => false];
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $id !== null ? 'Modifier' : 'Nouvelle' ?> séance — <?= htmlspecialchars($eleve['prenom']) ?></title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<a class="retour" href="/saisie/eleve.php?eleve_id=<?= $eleveId ?>">&larr; <?= htmlspecialchars($eleve['prenom']) ?></a>
<h1><?= $id !== null ? 'Modifier la séance' : 'Nouvelle séance' ?></h1>

<?php foreach ($erreurs as $erreur): ?>
    <p class="erreur"><?= $erreur ?></p>
<?php endforeach; ?>

<form method="post">
    <?= Csrf::champHtml() ?>
    <input type="hidden" name="eleve_id" value="<?= $eleveId ?>">

    <label for="date_seance">Date de la séance</label>
    <input type="date" id="date_seance" name="date_seance" value="<?= htmlspecialchars($dateSeance) ?>" required>

    <label for="longueur_tour_m">Longueur du tour en mètres (optionnel)</label>
    <input type="number" id="longueur_tour_m" name="longueur_tour_m" step="0.01" min="0.01"
           value="<?= htmlspecialchars((string) $longueurTourBrut) ?>">

    <label>Temps de passage (format mm:ss, dans l'ordre)</label>
    <div id="lignes-temps">
        <?php foreach ($lignesAffichage as $index => $ligne): ?>
            <div class="ligne-temps">
                <input type="text" name="temps[<?= $index ?>]" inputmode="numeric" placeholder="mm:ss"
                       pattern="\d{1,3}:[0-5]\d" value="<?= htmlspecialchars($ligne['valeur']) ?>">
                <label><input type="checkbox" name="incertain[<?= $index ?>]" value="1" <?= $ligne['incertain'] ? 'checked' : '' ?>> incertain</label>
                <button type="button" class="bouton-secondaire" onclick="this.closest('.ligne-temps').remove()">Retirer</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="ajouter-ligne" class="bouton-secondaire">+ Ajouter un temps</button>

    <button type="submit" class="bouton-large">Enregistrer</button>
</form>

<script>
(function () {
    var conteneur = document.getElementById('lignes-temps');
    var indexSuivant = <?= count($lignesAffichage) ?>;

    document.getElementById('ajouter-ligne').addEventListener('click', function () {
        var ligne = document.createElement('div');
        ligne.className = 'ligne-temps';
        ligne.innerHTML =
            '<input type="text" name="temps[' + indexSuivant + ']" inputmode="numeric" placeholder="mm:ss" pattern="\\d{1,3}:[0-5]\\d">' +
            '<label><input type="checkbox" name="incertain[' + indexSuivant + ']" value="1"> incertain</label>' +
            '<button type="button" class="bouton-secondaire" onclick="this.closest(\'.ligne-temps\').remove()">Retirer</button>';
        conteneur.appendChild(ligne);
        indexSuivant++;
        ligne.querySelector('input[type="text"]').focus();
    });
})();
</script>
</body>
</html>
