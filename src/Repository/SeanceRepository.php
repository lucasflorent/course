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
}
