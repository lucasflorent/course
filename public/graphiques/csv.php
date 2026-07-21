<?php

declare(strict_types=1);

use App\Auth\SiteAuth;
use App\Config\Database;
use App\Repository\ClasseRepository;
use App\Repository\SeanceRepository;
use App\Repository\TempsPassageRepository;
use App\Support\TourDerivation;
use App\Support\Temps;

require __DIR__ . '/../../config/bootstrap.php';

SiteAuth::requireAuth();

$pdo = Database::pdo();
$classeId = (int) ($_GET['classe_id'] ?? 0);
$dates = array_values(array_unique((array) ($_GET['date_seance'] ?? [])));
$eleveIdsDemandes = array_map('intval', (array) ($_GET['eleve_id'] ?? []));

$classe = $classeId > 0 ? ClasseRepository::findById($pdo, $classeId) : null;

if ($classe === null || $dates === [] || $eleveIdsDemandes === []) {
    header('Location: /graphiques/export.php');
    exit;
}

$lignes = SeanceRepository::findByClasseEtDates($pdo, $classeId, $dates);
$retenues = array_values(array_filter(
    $lignes,
    static fn (array $l): bool => in_array((int) $l['eleve_id'], $eleveIdsDemandes, true)
));

if ($retenues === []) {
    header('Location: /graphiques/export.php');
    exit;
}

$libelleClasse = $classe['enseignant'] . ' - ' . $classe['annee_debut'] . '/' . ((int) $classe['annee_debut'] + 1);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="course-' . $classeId . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['Élève', 'Classe', 'Date séance', 'Longueur tour (m)', 'N° tour', 'Temps cumulé (mm:ss)', 'Temps cumulé (s)', 'Incertain'], ';');

foreach ($retenues as $ligne) {
    $tempsListe = TempsPassageRepository::findBySeance($pdo, (int) $ligne['seance_id']);
    $tours = TourDerivation::deriver($tempsListe);

    foreach ($tempsListe as $i => $t) {
        $numeroTour = $i === 0 ? '' : (string) $tours[$i - 1]['numero_tour'];
        fputcsv($out, [
            $ligne['prenom'],
            $libelleClasse,
            (new DateTimeImmutable($ligne['date_seance']))->format('d-m-Y'),
            $ligne['longueur_tour_m'] ?? '',
            $numeroTour,
            Temps::format((int) $t['temps_cumule_s']),
            (int) $t['temps_cumule_s'],
            $t['incertain'] ? 'oui' : 'non',
        ], ';');
    }
}

fclose($out);
