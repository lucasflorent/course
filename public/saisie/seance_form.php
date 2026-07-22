<?php

declare(strict_types=1);

use App\Auth\SiteAuth;
use App\Config\Database;
use App\Repository\EleveRepository;
use App\Repository\SeanceRepository;
use App\Repository\TempsPassageRepository;
use App\Support\Csrf;
use App\Support\Icone;
use App\Support\PrefillCookie;
use App\Support\Temps;
use App\Support\TourDerivation;
use App\View\Layout;

require __DIR__ . '/../../config/bootstrap.php';

SiteAuth::requireAuth();

$pdo = Database::pdo();

// $_REQUEST et non $_GET : chaque action (ajouter/supprimer un temps,
// basculer incertain, changer la date...) POST vers cette meme page sans
// repasser l'id en query string ; ne lire que $_GET perdrait la seance en
// cours a chaque action (redirection vers la liste, reponse JSON absente...).
$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;
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

// Date et longueur de tour "de travail" : celles de la seance en base si
// elle existe deja (source de verite unique une fois creee), sinon celles
// portees par la requete (la seance n'existe pas encore, rien a lire en
// base), avec le cookie de premplissage puis aujourd'hui comme repli.
if ($seanceExistante !== null) {
    $dateSeance = $seanceExistante['date_seance'];
    $longueurTourBrut = $seanceExistante['longueur_tour_m'] !== null ? (string) $seanceExistante['longueur_tour_m'] : '';
} else {
    $dateSeance = (string) ($_REQUEST['date_seance'] ?? $cookie['date_seance'] ?? date('Y-m-d'));
    $longueurTourBrut = (string) ($_REQUEST['longueur_tour_m'] ?? $cookie['longueur_tour_m'] ?? '');
}

/**
 * URL de l'ecran de saisie, pour rediriger apres chaque action (PRG). Avant
 * la creation de la seance, date/longueur voyagent en query string (rien a
 * lire en base) ; une fois creee, seul l'id suffit.
 */
function urlEcran(?int $seanceId, int $eleveId, string $dateSeance, string $longueurTourBrut, ?string $erreur = null): string
{
    $params = $seanceId !== null
        ? ['id' => $seanceId]
        : ['eleve_id' => $eleveId, 'date_seance' => $dateSeance, 'longueur_tour_m' => $longueurTourBrut];

    if ($erreur !== null) {
        $params['erreur'] = $erreur;
    }

    return '/saisie/seance_form.php?' . http_build_query($params);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'maj_seance') {
        $dateSeance = (string) ($_POST['date_seance'] ?? $dateSeance);
        $longueurTourBrut = trim((string) ($_POST['longueur_tour_m'] ?? $longueurTourBrut));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateSeance)) {
            header('Location: ' . urlEcran($id, $eleveId, $dateSeance, $longueurTourBrut, 'La date de séance est invalide.'));
            exit;
        }

        if ($id !== null) {
            $longueurTourM = $longueurTourBrut !== '' && is_numeric($longueurTourBrut) && (float) $longueurTourBrut > 0
                ? (float) $longueurTourBrut
                : null;
            SeanceRepository::update($pdo, $id, $dateSeance, $longueurTourM);
            PrefillCookie::set(['classe_id' => (int) $eleve['classe_id'], 'date_seance' => $dateSeance, 'longueur_tour_m' => $longueurTourM]);
        }

        header('Location: ' . urlEcran($id, $eleveId, $dateSeance, $longueurTourBrut));
        exit;
    }

    if ($action === 'ajouter_tour') {
        $valeur = trim((string) ($_POST['temps'] ?? ''));
        $secondes = Temps::toSeconds($valeur);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateSeance)) {
            header('Location: ' . urlEcran($id, $eleveId, $dateSeance, $longueurTourBrut, 'La date de séance est invalide.'));
            exit;
        }

        if ($secondes === null) {
            header('Location: ' . urlEcran($id, $eleveId, $dateSeance, $longueurTourBrut, 'Le temps "' . $valeur . '" doit être au format mm:ss.'));
            exit;
        }

        $longueurTourM = $longueurTourBrut !== '' && is_numeric($longueurTourBrut) && (float) $longueurTourBrut > 0
            ? (float) $longueurTourBrut
            : null;

        $seanceId = $id;
        if ($seanceId === null) {
            $seanceId = SeanceRepository::create($pdo, $eleveId, $dateSeance, $longueurTourM);
        }

        $tempsExistants = TempsPassageRepository::findBySeance($pdo, $seanceId);
        $dernier = $tempsExistants !== [] ? (int) end($tempsExistants)['temps_cumule_s'] : null;

        if ($dernier !== null && $secondes <= $dernier) {
            header('Location: ' . urlEcran($seanceId, $eleveId, $dateSeance, $longueurTourBrut, 'Le temps doit être supérieur au précédent (' . Temps::format($dernier) . ').'));
            exit;
        }

        TempsPassageRepository::create($pdo, $seanceId, $secondes, false);
        PrefillCookie::set(['classe_id' => (int) $eleve['classe_id'], 'date_seance' => $dateSeance, 'longueur_tour_m' => $longueurTourM]);

        header('Location: ' . urlEcran($seanceId, $eleveId, $dateSeance, $longueurTourBrut));
        exit;
    }

    if ($action === 'basculer_incertain' && $id !== null) {
        $tempsId = (int) ($_POST['temps_id'] ?? 0);
        $actuel = ($_POST['incertain_actuel'] ?? '') === '1';
        $nouveauEtat = !$actuel;
        TempsPassageRepository::updateIncertain($pdo, $tempsId, $id, $nouveauEtat);

        if (($_POST['ajax'] ?? '') === '1') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'incertain' => $nouveauEtat]);
            exit;
        }

        header('Location: ' . urlEcran($id, $eleveId, $dateSeance, $longueurTourBrut));
        exit;
    }

    if ($action === 'supprimer_tour' && $id !== null) {
        $tempsId = (int) ($_POST['temps_id'] ?? 0);
        TempsPassageRepository::delete($pdo, $tempsId, $id);
        header('Location: ' . urlEcran($id, $eleveId, $dateSeance, $longueurTourBrut));
        exit;
    }
}

$temps = $id !== null ? TempsPassageRepository::findBySeance($pdo, $id) : [];
$tours = TourDerivation::deriver($temps);
$erreur = isset($_GET['erreur']) ? (string) $_GET['erreur'] : null;

Layout::debut(($id !== null ? 'Modifier' : 'Nouvelle') . ' séance — ' . $eleve['prenom'] . ' — Course de fond CM2', [
    'eleveId' => $eleveId,
    'navActive' => 'saisie',
]);
?>
<div class="bandeau" id="bandeau-confirmation" role="status" aria-live="polite"></div>
<a class="retour" href="/saisie/eleve.php?eleve_id=<?= $eleveId ?>">&larr; <?= htmlspecialchars($eleve['prenom']) ?></a>

<?php if ($erreur !== null): ?>
    <p class="erreur"><?= htmlspecialchars($erreur) ?></p>
<?php endif; ?>

<div class="hdr">
    <div class="hdr-txt">
        <div class="hdr-name">Bravo <?= htmlspecialchars($eleve['prenom']) ?> !</div>
        <div class="hdr-sub"><?= count($temps) ?> temps enregistré<?= count($temps) > 1 ? 's' : '' ?></div>
    </div>
    <?php if ($longueurTourBrut !== ''): ?><div class="lapchip"><?= htmlspecialchars($longueurTourBrut) ?> m/tour</div><?php endif; ?>
</div>

<div class="section-lbl">Séance</div>
<form method="post" action="/saisie/seance_form.php" class="daterow">
    <?= Csrf::champHtml() ?>
    <input type="hidden" name="action" value="maj_seance">
    <input type="hidden" name="eleve_id" value="<?= $eleveId ?>">
    <?php if ($id !== null): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
    <input class="input" type="date" name="date_seance" value="<?= htmlspecialchars($dateSeance) ?>" onchange="this.form.submit()">
    <input class="input" type="text" inputmode="numeric" name="longueur_tour_m" style="max-width:110px" placeholder="450m"
           value="<?= htmlspecialchars($longueurTourBrut) ?>" onchange="this.form.submit()">
</form>

<div class="time-display">
    <div class="time-big" id="temps-affiche">00:00</div>
    <div class="time-label">Temps cumulé (min:sec)</div>
</div>

<div class="keypad" id="pave-numerique">
    <?php foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9', 'effacer', '0', 'ok'] as $touche): ?>
        <button type="button" class="key<?= $touche === 'ok' ? ' key-ok' : '' ?>" data-touche="<?= $touche ?>">
            <?= $touche === 'effacer' ? '⌫' : ($touche === 'ok' ? 'OK' : $touche) ?>
        </button>
    <?php endforeach; ?>
</div>

<form method="post" action="/saisie/seance_form.php" id="form-ajouter-tour">
    <?= Csrf::champHtml() ?>
    <input type="hidden" name="action" value="ajouter_tour">
    <input type="hidden" name="eleve_id" value="<?= $eleveId ?>">
    <?php if ($id !== null): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
    <input type="hidden" name="date_seance" value="<?= htmlspecialchars($dateSeance) ?>">
    <input type="hidden" name="longueur_tour_m" value="<?= htmlspecialchars($longueurTourBrut) ?>">
    <input type="hidden" name="temps" id="champ-temps" value="">
</form>

<?php if ($temps !== []): ?>
    <div class="section-lbl" style="margin-top:16px">Temps enregistrés</div>
    <div class="laplist">
        <?php foreach ($temps as $i => $t): ?>
            <?php $tour = $tours[$i] ?? null; ?>
            <div class="laprow">
                <span class="lapn">Temps n° <?= $i + 1 ?></span>
                <span class="lapt"><?= htmlspecialchars(Temps::format((int) $t['temps_cumule_s'])) ?></span>
                <?php if ($tour !== null): ?>
                    <span class="lapd">+<?= htmlspecialchars(Temps::format($tour['duree_tour_s'])) ?></span>
                <?php endif; ?>
                <button type="button" class="lapunsure<?= $t['incertain'] ? ' on' : '' ?>"
                        data-action-incertain
                        data-temps-id="<?= (int) $t['id'] ?>"
                        data-incertain="<?= $t['incertain'] ? '1' : '0' ?>"
                        title="Temps incertain (écriture peu lisible)">
                    <?= Icone::svg('warning-circle') ?>
                </button>
                <form method="post" action="/saisie/seance_form.php">
                    <?= Csrf::champHtml() ?>
                    <input type="hidden" name="action" value="supprimer_tour">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="temps_id" value="<?= (int) $t['id'] ?>">
                    <button type="submit" class="lapdel" title="Supprimer ce temps">
                        <?= Icone::svg('trash') ?>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
(function () {
    var buffer = '';
    var affichage = document.getElementById('temps-affiche');
    var champTemps = document.getElementById('champ-temps');
    var formulaire = document.getElementById('form-ajouter-tour');

    function majAffichage() {
        var chiffres = (buffer + '0000').slice(0, 4);
        affichage.textContent = chiffres.slice(0, 2) + ':' + chiffres.slice(2, 4);
    }

    document.getElementById('pave-numerique').addEventListener('click', function (evenement) {
        var touche = evenement.target.closest('[data-touche]');
        if (!touche) {
            return;
        }
        var valeur = touche.dataset.touche;

        if (valeur === 'effacer') {
            buffer = buffer.slice(0, -1);
            majAffichage();
        } else if (valeur === 'ok') {
            if (buffer === '') {
                return;
            }
            champTemps.value = affichage.textContent;
            formulaire.submit();
        } else {
            buffer = (buffer + valeur).slice(-4);
            majAffichage();
        }
    });

    var seanceId = <?= (int) ($id ?? 0) ?>;
    var jetonCsrf = <?= json_encode(Csrf::token()) ?>;
    var bandeau = document.getElementById('bandeau-confirmation');
    var minuteurBandeau = null;

    function afficherBandeau(texte, estErreur) {
        bandeau.textContent = texte;
        bandeau.classList.toggle('erreur', !!estErreur);
        bandeau.classList.add('visible');
        clearTimeout(minuteurBandeau);
        minuteurBandeau = setTimeout(function () {
            bandeau.classList.remove('visible');
        }, 2200);
    }

    var laplist = document.querySelector('.laplist');
    if (laplist) {
        laplist.addEventListener('click', function (evenement) {
            var bouton = evenement.target.closest('[data-action-incertain]');
            if (!bouton || bouton.disabled) {
                return;
            }

            var etaitIncertain = bouton.dataset.incertain === '1';
            var corps = new URLSearchParams();
            corps.set('csrf_token', jetonCsrf);
            corps.set('action', 'basculer_incertain');
            corps.set('id', String(seanceId));
            corps.set('temps_id', bouton.dataset.tempsId);
            corps.set('incertain_actuel', etaitIncertain ? '1' : '0');
            corps.set('ajax', '1');

            bouton.disabled = true;

            fetch('/saisie/seance_form.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: corps.toString(),
            })
                .then(function (reponse) {
                    if (!reponse.ok) {
                        throw new Error('reponse-invalide');
                    }
                    return reponse.json();
                })
                .then(function (donnees) {
                    if (!donnees || !donnees.ok) {
                        throw new Error('echec');
                    }
                    bouton.dataset.incertain = donnees.incertain ? '1' : '0';
                    bouton.classList.toggle('on', donnees.incertain);
                    bouton.classList.remove('vient-de-changer');
                    void bouton.offsetWidth; // relance l'animation meme si l'etat est identique
                    bouton.classList.add('vient-de-changer');
                    setTimeout(function () { bouton.classList.remove('vient-de-changer'); }, 220);
                    afficherBandeau(donnees.incertain ? 'Temps marqué comme incertain.' : 'Temps marqué comme certain.');
                })
                .catch(function () {
                    afficherBandeau("Échec de la mise à jour, réessaie.", true);
                })
                .finally(function () {
                    bouton.disabled = false;
                });
        });
    }
})();
</script>
<?php Layout::fin(); ?>
