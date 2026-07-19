<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ClasseRepository
{
    public static function findAll(PDO $pdo): array
    {
        $stmt = $pdo->query(
            'SELECT id, enseignant, annee_debut, libelle_classe, cree_le
             FROM classes
             ORDER BY annee_debut DESC, enseignant ASC'
        );

        return $stmt->fetchAll();
    }

    public static function findById(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT id, enseignant, annee_debut, libelle_classe, cree_le FROM classes WHERE id = ?'
        );
        $stmt->execute([$id]);
        $classe = $stmt->fetch();

        return $classe === false ? null : $classe;
    }

    public static function findByAnnee(PDO $pdo, int $anneeDebut): array
    {
        $stmt = $pdo->prepare(
            'SELECT id, enseignant, annee_debut, libelle_classe
             FROM classes
             WHERE annee_debut = ?
             ORDER BY enseignant ASC'
        );
        $stmt->execute([$anneeDebut]);

        return $stmt->fetchAll();
    }

    public static function listeAnnees(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT DISTINCT annee_debut FROM classes ORDER BY annee_debut DESC');

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function listeEnseignants(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT DISTINCT enseignant FROM classes ORDER BY enseignant ASC');

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function create(PDO $pdo, string $enseignant, int $anneeDebut, ?string $libelle): int
    {
        $stmt = $pdo->prepare(
            'INSERT INTO classes (enseignant, annee_debut, libelle_classe) VALUES (?, ?, ?)'
        );
        $stmt->execute([$enseignant, $anneeDebut, $libelle]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(PDO $pdo, int $id, string $enseignant, int $anneeDebut, ?string $libelle): void
    {
        $stmt = $pdo->prepare(
            'UPDATE classes SET enseignant = ?, annee_debut = ?, libelle_classe = ? WHERE id = ?'
        );
        $stmt->execute([$enseignant, $anneeDebut, $libelle, $id]);
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare('DELETE FROM classes WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Compteurs en cascade (eleves / seances / temps de passage), pour
     * l'ecran de confirmation avant suppression.
     */
    public static function statistiques(PDO $pdo, int $id): array
    {
        $stmt = $pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM eleves e WHERE e.classe_id = c.id) AS nb_eleves,
                (SELECT COUNT(*) FROM seances s JOIN eleves e ON e.id = s.eleve_id WHERE e.classe_id = c.id) AS nb_seances,
                (SELECT COUNT(*) FROM temps_passage t
                    JOIN seances s ON s.id = t.seance_id
                    JOIN eleves e ON e.id = s.eleve_id
                    WHERE e.classe_id = c.id) AS nb_temps
             FROM classes c WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        $resultat = $stmt->fetch();

        return $resultat === false
            ? ['nb_eleves' => 0, 'nb_seances' => 0, 'nb_temps' => 0]
            : $resultat;
    }
}
