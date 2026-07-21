<?php

declare(strict_types=1);

use App\Auth\SiteAuth;
use App\Config\Database;
use App\Graphique\GraphiqueTours;
use App\Repository\ClasseRepository;
use App\Repository\SeanceRepository;
use App\Repository\TempsPassageRepository;
use App\Support\Statistiques;
use App\Support\Temps;
use App\Support\TourDerivation;

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
$parEleve = [];
foreach ($lignes as $ligne) {
    if (!in_array((int) $ligne['eleve_id'], $eleveIdsDemandes, true)) {
        continue;
    }
    $parEleve[(int) $ligne['eleve_id']][] = $ligne;
}
uasort($parEleve, static fn (array $a, array $b): int => $a[0]['prenom'] <=> $b[0]['prenom']);

if ($parEleve === []) {
    header('Location: /graphiques/export.php');
    exit;
}

$libelleClasse = $classe['enseignant'] . ' - ' . $classe['annee_debut'] . '/' . ((int) $classe['annee_debut'] + 1);

$largeurMm = 160.0;
$hauteurMm = $largeurMm * (GraphiqueTours::HAUTEUR_PX / GraphiqueTours::LARGEUR_PX);

$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Course de fond CM2');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(false);

foreach ($parEleve as $seancesEleve) {
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $seancesEleve[0]['prenom'], 0, 1);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 7, $libelleClasse, 0, 1);

    $series = [];
    $statsParDate = [];
    foreach ($seancesEleve as $s) {
        $dateFormatee = (new DateTimeImmutable($s['date_seance']))->format('d-m-Y');
        $tours = TourDerivation::deriver(TempsPassageRepository::findBySeance($pdo, (int) $s['seance_id']));
        $stats = Statistiques::calculer($tours);
        $vitesse = Statistiques::vitesseMoyenneKmh(
            $s['longueur_tour_m'] !== null ? (float) $s['longueur_tour_m'] : null,
            $stats['moyenne_s']
        );
        $series[] = ['libelle' => $dateFormatee, 'tours' => $tours, 'vitesse_moyenne_kmh' => $vitesse];
        $statsParDate[] = ['date' => $dateFormatee, 'stats' => $stats, 'vitesse' => $vitesse];
    }

    $png = GraphiqueTours::genererPng($series);
    $yImage = 35;
    $pdf->Image('@' . $png, 15, $yImage, $largeurMm, 0, 'PNG');

    $y = $yImage + $hauteurMm + 8;
    $pdf->SetXY(15, $y);
    $pdf->SetFont('helvetica', 'B', 9);
    foreach (['Date', 'Meilleur', 'Pire', 'Moyenne', 'Ecart-type', 'Vitesse moy.'] as $entete) {
        $pdf->Cell(30, 6, $entete, 1);
    }
    $pdf->Ln();
    $pdf->SetFont('helvetica', '', 9);
    foreach ($statsParDate as $ligneStats) {
        $s = $ligneStats['stats'];
        $pdf->SetX(15);
        $pdf->Cell(30, 6, $ligneStats['date'], 1);
        $pdf->Cell(30, 6, $s['meilleur_s'] !== null ? Temps::format($s['meilleur_s']) : 'Non calculable', 1);
        $pdf->Cell(30, 6, $s['pire_s'] !== null ? Temps::format($s['pire_s']) : 'Non calculable', 1);
        $pdf->Cell(30, 6, $s['moyenne_s'] !== null ? Temps::format((int) round($s['moyenne_s'])) : 'Non calculable', 1);
        $pdf->Cell(30, 6, $s['ecart_type_s'] !== null ? number_format($s['ecart_type_s'], 1) . ' s' : 'Non calculable', 1);
        $pdf->Cell(30, 6, $ligneStats['vitesse'] !== null ? number_format($ligneStats['vitesse'], 1) . ' km/h' : 'Non renseignee', 1);
        $pdf->Ln();
    }
}

$pdf->Output('course-' . $classeId . '.pdf', 'D');
