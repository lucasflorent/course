<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class EleveRepository
{
    public static function findByClasse(PDO $pdo, int $classeId): array
    {
        $stmt = $pdo->prepare('SELECT id, classe_id, prenom FROM eleves WHERE classe_id = ? ORDER BY prenom ASC');
        $stmt->execute([$classeId]);

        return $stmt->fetchAll();
    }

    public static function findById(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT id, classe_id, prenom FROM eleves WHERE id = ?');
        $stmt->execute([$id]);
        $eleve = $stmt->fetch();

        return $eleve === false ? null : $eleve;
    }

    /**
     * Eleve avec le contexte de sa classe (utilise pour l'en-tete de saisie).
     */
    public static function findByIdAvecClasse(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT e.id, e.classe_id, e.prenom, c.enseignant, c.annee_debut, c.libelle_classe
             FROM eleves e JOIN classes c ON c.id = e.classe_id
             WHERE e.id = ?'
        );
        $stmt->execute([$id]);
        $eleve = $stmt->fetch();

        return $eleve === false ? null : $eleve;
    }

    public static function create(PDO $pdo, int $classeId, string $prenom): int
    {
        $stmt = $pdo->prepare('INSERT INTO eleves (classe_id, prenom) VALUES (?, ?)');
        $stmt->execute([$classeId, $prenom]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * @param string[] $prenoms
     * @return int nombre d'eleves inseres
     */
    public static function createBulk(PDO $pdo, int $classeId, array $prenoms): int
    {
        $stmt = $pdo->prepare('INSERT INTO eleves (classe_id, prenom) VALUES (?, ?)');

        $pdo->beginTransaction();
        try {
            $nb = 0;
            foreach ($prenoms as $prenom) {
                $prenom = trim($prenom);
                if ($prenom === '') {
                    continue;
                }
                $stmt->execute([$classeId, $prenom]);
                $nb++;
            }
            $pdo->commit();

            return $nb;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function update(PDO $pdo, int $id, string $prenom): void
    {
        $stmt = $pdo->prepare('UPDATE eleves SET prenom = ? WHERE id = ?');
        $stmt->execute([$prenom, $id]);
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare('DELETE FROM eleves WHERE id = ?');
        $stmt->execute([$id]);
    }
}
