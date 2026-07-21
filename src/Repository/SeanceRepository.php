<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class SeanceRepository
{
    public static function findByEleve(PDO $pdo, int $eleveId): array
    {
        $stmt = $pdo->prepare(
            'SELECT s.id, s.date_seance, s.longueur_tour_m,
                    (SELECT COUNT(*) FROM temps_passage t WHERE t.seance_id = s.id) AS nb_temps
             FROM seances s
             WHERE s.eleve_id = ?
             ORDER BY s.date_seance DESC, s.id DESC'
        );
        $stmt->execute([$eleveId]);

        return $stmt->fetchAll();
    }

    public static function findById(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT id, eleve_id, date_seance, longueur_tour_m FROM seances WHERE id = ?');
        $stmt->execute([$id]);
        $seance = $stmt->fetch();

        return $seance === false ? null : $seance;
    }

    /**
     * Seance avec le contexte complet (eleve + classe), pour l'en-tete du
     * formulaire de saisie/edition.
     */
    public static function findByIdAvecContexte(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT s.id, s.eleve_id, s.date_seance, s.longueur_tour_m,
                    e.prenom, e.classe_id, c.enseignant, c.annee_debut, c.libelle_classe
             FROM seances s
             JOIN eleves e ON e.id = s.eleve_id
             JOIN classes c ON c.id = e.classe_id
             WHERE s.id = ?'
        );
        $stmt->execute([$id]);
        $seance = $stmt->fetch();

        return $seance === false ? null : $seance;
    }

    public static function create(PDO $pdo, int $eleveId, string $dateSeance, ?float $longueurTourM): int
    {
        $stmt = $pdo->prepare(
            'INSERT INTO seances (eleve_id, date_seance, longueur_tour_m) VALUES (?, ?, ?)'
        );
        $stmt->execute([$eleveId, $dateSeance, $longueurTourM]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(PDO $pdo, int $id, string $dateSeance, ?float $longueurTourM): void
    {
        $stmt = $pdo->prepare(
            'UPDATE seances SET date_seance = ?, longueur_tour_m = ? WHERE id = ?'
        );
        $stmt->execute([$dateSeance, $longueurTourM, $id]);
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare('DELETE FROM seances WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Dates de seance distinctes d'une classe, avec le nombre d'eleves y
     * ayant participe — alimente le selecteur de dates de la page d'export
     * (une "seance" reelle = evenement classe entiere partageant une date,
     * stocke en base comme une ligne par eleve).
     */
    public static function listerDatesDistinctesParClasse(PDO $pdo, int $classeId): array
    {
        $stmt = $pdo->prepare(
            'SELECT s.date_seance, COUNT(DISTINCT s.eleve_id) AS nb_eleves
             FROM seances s
             JOIN eleves e ON e.id = s.eleve_id
             WHERE e.classe_id = ?
             GROUP BY s.date_seance
             ORDER BY s.date_seance DESC'
        );
        $stmt->execute([$classeId]);

        return $stmt->fetchAll();
    }

    /**
     * Seances d'une classe pour un ensemble de dates (1 a
     * GraphiqueTours::MAX_SERIES), avec le prenom de l'eleve. A regrouper par
     * eleve_id cote appelant pour construire les series multi-seances.
     *
     * @param string[] $dates
     * @return array<int, array{eleve_id:int, prenom:string, seance_id:int, date_seance:string, longueur_tour_m:?string}>
     */
    public static function findByClasseEtDates(PDO $pdo, int $classeId, array $dates): array
    {
        if ($dates === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($dates), '?'));
        $stmt = $pdo->prepare(
            "SELECT e.id AS eleve_id, e.prenom, s.id AS seance_id, s.date_seance, s.longueur_tour_m
             FROM seances s
             JOIN eleves e ON e.id = s.eleve_id
             WHERE e.classe_id = ? AND s.date_seance IN ({$placeholders})
             ORDER BY e.prenom ASC, s.date_seance ASC"
        );
        $stmt->execute([$classeId, ...$dates]);

        return $stmt->fetchAll();
    }
}
