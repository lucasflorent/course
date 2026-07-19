<?php

declare(strict_types=1);

/**
 * Jeu de donnees de demo/test : quelques enseignants, quelques annees
 * scolaires, une vingtaine d'eleves par classe, 5 ou 6 seances par classe
 * (meme date et meme distance pour tous les eleves d'une seance donnee).
 *
 * Usage : php scripts/seed_demo.php [--force]
 *   --force  supprime d'abord les classes existantes (cascade sur eleves /
 *            seances / temps_passage) avant de regenerer les donnees.
 */

use App\Config\Database;

require __DIR__ . '/../config/bootstrap.php';

$pdo = Database::pdo();
$force = in_array('--force', $argv, true);

$nbClassesExistantes = (int) $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();

if ($nbClassesExistantes > 0 && !$force) {
    echo "Des classes existent deja ({$nbClassesExistantes}). Rien a faire.\n";
    echo "Relancez avec --force pour supprimer les classes existantes et regenerer.\n";
    exit(0);
}

if ($nbClassesExistantes > 0 && $force) {
    // ON DELETE CASCADE se charge de purger eleves / seances / temps_passage.
    $pdo->exec('DELETE FROM classes');
    echo "Classes existantes supprimees ({$nbClassesExistantes}).\n";
}

// --- Parametres du jeu de donnees --------------------------------------
$enseignants = ['Mme Dupont', 'M. Martin', 'Mme Lefebvre'];
$anneesDebut = [2023, 2024, 2025];

$poolPrenoms = [
    'Emma', 'Léo', 'Chloé', 'Hugo', 'Manon', 'Louis', 'Camille', 'Nathan', 'Sarah', 'Ethan',
    'Jade', 'Lucas', 'Lina', 'Gabriel', 'Zoé', 'Nolan', 'Inès', 'Tom', 'Léa', 'Enzo',
    'Louise', 'Raphaël', 'Anna', 'Adam', 'Alice', 'Mathis', 'Julia', 'Noah', 'Rose', 'Sacha',
    'Juliette', 'Liam', 'Mila', 'Arthur', 'Elena', 'Timéo', 'Agathe', 'Maxime', 'Nina', 'Théo',
];

$longueursPossibles = [50.0, 75.0, 100.0];

$stmtClasse = $pdo->prepare(
    'INSERT INTO classes (enseignant, annee_debut, libelle_classe) VALUES (?, ?, ?)'
);
$stmtEleve = $pdo->prepare('INSERT INTO eleves (classe_id, prenom) VALUES (?, ?)');
$stmtSeance = $pdo->prepare(
    'INSERT INTO seances (eleve_id, date_seance, longueur_tour_m) VALUES (?, ?, ?)'
);
$stmtTemps = $pdo->prepare(
    'INSERT INTO temps_passage (seance_id, temps_cumule_s, incertain) VALUES (?, ?, ?)'
);

/**
 * Genere N dates de seance reparties (avec un peu de hasard) sur l'annee
 * scolaire annee_debut/annee_debut+1 (octobre a mai), triees par ordre
 * chronologique.
 *
 * @return DateTimeImmutable[]
 */
function genererDatesSeances(int $anneeDebut, int $nombre): array
{
    $debut = new DateTimeImmutable("{$anneeDebut}-10-01");
    $fin = new DateTimeImmutable(($anneeDebut + 1) . '-05-31');
    $totalJours = $fin->diff($debut)->days;
    $pas = intdiv($totalJours, $nombre);

    $dates = [];
    for ($i = 0; $i < $nombre; $i++) {
        $jitter = random_int(-5, 5);
        $offset = ($i * $pas) + intdiv($pas, 2) + $jitter;
        $offset = max(0, min($totalJours, $offset));
        $dates[] = $debut->modify("+{$offset} days");
    }

    return $dates;
}

$totalClasses = 0;
$totalEleves = 0;
$totalSeances = 0;
$totalTemps = 0;

$pdo->beginTransaction();

try {
    foreach ($enseignants as $enseignant) {
        foreach ($anneesDebut as $anneeDebut) {
            $stmtClasse->execute([$enseignant, $anneeDebut, 'CM2']);
            $classeId = (int) $pdo->lastInsertId();
            $totalClasses++;

            // Vitesse de course (s/m) et distance de tour propres a la classe,
            // pour que toutes les seances d'une meme classe partagent la meme
            // distance (comme demande : une seance = une distance commune).
            $longueurTour = $longueursPossibles[array_rand($longueursPossibles)];

            $prenoms = $poolPrenoms;
            shuffle($prenoms);
            $prenomsClasse = array_slice($prenoms, 0, 20);

            $eleveIds = [];
            foreach ($prenomsClasse as $prenom) {
                $stmtEleve->execute([$classeId, $prenom]);
                $eleveIds[] = (int) $pdo->lastInsertId();
                $totalEleves++;
            }

            $nombreSeances = random_int(5, 6);
            $dates = genererDatesSeances($anneeDebut, $nombreSeances);

            foreach ($dates as $date) {
                // Duree cible de la course (8 a 12 minutes), commune a tous
                // les eleves de cette seance.
                $dureeCourseS = random_int(480, 720);
                $dateStr = $date->format('Y-m-d');

                foreach ($eleveIds as $eleveId) {
                    $stmtSeance->execute([$eleveId, $dateStr, $longueurTour]);
                    $seanceId = (int) $pdo->lastInsertId();
                    $totalSeances++;

                    // Vitesse propre a l'eleve (s/m), donnant un rythme de
                    // tour de base, avec une legere fatigue au fil des tours.
                    $vitesseBase = random_int(280, 360) / 1000; // s/m

                    $cumule = 0;
                    $tour = 0;

                    while (true) {
                        $tour++;
                        $fatigue = 1 + min(0.25, $tour * 0.01);
                        $bruit = random_int(-150, 150) / 100; // secondes
                        $dureeTour = ($longueurTour * $vitesseBase * $fatigue) + $bruit;
                        $dureeTour = max(1.0, $dureeTour);

                        $nouveauCumule = $cumule + $dureeTour;
                        if ($nouveauCumule > $dureeCourseS) {
                            break;
                        }

                        $cumule = $nouveauCumule;

                        // ~6% de chance que ce passage n'ait pas ete note sur
                        // la feuille papier (tour "manquant" = pas de ligne).
                        if (random_int(1, 100) <= 6) {
                            continue;
                        }

                        $incertain = random_int(1, 100) <= 8;
                        $stmtTemps->execute([$seanceId, (int) round($cumule), $incertain ? 1 : 0]);
                        $totalTemps++;
                    }
                }
            }

            echo "Classe creee : {$enseignant} / {$anneeDebut}-" . ($anneeDebut + 1)
                . " ({$nombreSeances} seances, " . count($eleveIds) . " eleves)\n";
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Erreur pendant le seed : ' . $e->getMessage() . "\n");
    exit(1);
}

echo "\nTermine.\n";
echo "  Classes       : {$totalClasses}\n";
echo "  Eleves        : {$totalEleves}\n";
echo "  Seances       : {$totalSeances}\n";
echo "  Temps saisis  : {$totalTemps}\n";