<?php

declare(strict_types=1);

use App\Auth\SiteAuth;
use App\Config\Database;
use App\Graphique\GraphiqueTours;
use App\Repository\SeanceRepository;
use App\Repository\TempsPassageRepository;
use App\Support\Statistiques;
use App\Support\TourDerivation;

require __DIR__ . '/../../config/bootstrap.php';

SiteAuth::requireAuth();

$pdo = Database::pdo();
$eleveId = (int) ($_GET['eleve_id'] ?? 0);
$seanceIdsBruts = array_unique(array_map('intval', (array) ($_GET['seance_id'] ?? [])));

if ($eleveId <= 0 || $seanceIdsBruts === [] || count($seanceIdsBruts) > GraphiqueTours::MAX_SERIES) {
    http_response_code(400);
    exit;
}

$series = [];
foreach ($seanceIdsBruts as $seanceId) {
    $seance = SeanceRepository::findById($pdo, $seanceId);

    if ($seance === null || (int) $seance['eleve_id'] !== $eleveId) {
        http_response_code(400);
        exit;
    }

    $temps = TempsPassageRepository::findBySeance($pdo, $seanceId);
    $tours = TourDerivation::deriver($temps);
    $stats = Statistiques::calculer($tours);
    $vitesse = Statistiques::vitesseMoyenneKmh(
        $seance['longueur_tour_m'] !== null ? (float) $seance['longueur_tour_m'] : null,
        $stats['moyenne_s']
    );

    $series[] = [
        'libelle' => (new DateTimeImmutable($seance['date_seance']))->format('d-m-Y'),
        'tours' => $tours,
        'vitesse_moyenne_kmh' => $vitesse,
    ];
}

header('Content-Type: image/png');
header('Cache-Control: no-store');
echo GraphiqueTours::genererPng($series);
