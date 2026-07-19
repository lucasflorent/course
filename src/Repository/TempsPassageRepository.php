<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class TempsPassageRepository
{
    public static function findBySeance(PDO $pdo, int $seanceId): array
    {
        $stmt = $pdo->prepare(
            'SELECT id, temps_cumule_s, incertain FROM temps_passage WHERE seance_id = ? ORDER BY temps_cumule_s ASC'
        );
        $stmt->execute([$seanceId]);

        return $stmt->fetchAll();
    }

    /**
     * Remplace l'integralite des temps de passage d'une seance par la liste
     * fournie. Strategie deliberement simple (pas de diff ligne a ligne) :
     * une seance n'etant jamais verrouillee, l'edition reecrit tout le jeu.
     *
     * @param array<int, array{temps_cumule_s:int, incertain:bool}> $lignes
     */
    public static function replaceAll(PDO $pdo, int $seanceId, array $lignes): void
    {
        $pdo->beginTransaction();
        try {
            $stmtDelete = $pdo->prepare('DELETE FROM temps_passage WHERE seance_id = ?');
            $stmtDelete->execute([$seanceId]);

            $stmtInsert = $pdo->prepare(
                'INSERT INTO temps_passage (seance_id, temps_cumule_s, incertain) VALUES (?, ?, ?)'
            );
            foreach ($lignes as $ligne) {
                $stmtInsert->execute([$seanceId, $ligne['temps_cumule_s'], $ligne['incertain'] ? 1 : 0]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
