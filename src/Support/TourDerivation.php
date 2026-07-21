<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Derivation des tours a partir des temps cumules (voir la requete de
 * reference commentee dans docs/schema.sql). Le n° de tour n'est jamais
 * stocke : c'est le rang, une fois les temps tries par ordre croissant.
 */
final class TourDerivation
{
    /**
     * @param array<int, array{temps_cumule_s:int, incertain:bool|int}> $tempsTries trie par temps_cumule_s croissant
     * @return array<int, array{numero_tour:int, duree_tour_s:int, incertain:bool}>
     */
    public static function deriver(array $tempsTries): array
    {
        $tours = [];
        $precedent = null;
        $numero = 0;

        foreach ($tempsTries as $temps) {
            if ($precedent !== null) {
                $numero++;
                $tours[] = [
                    'numero_tour' => $numero,
                    'duree_tour_s' => (int) $temps['temps_cumule_s'] - (int) $precedent['temps_cumule_s'],
                    'incertain' => (bool) $temps['incertain'] || (bool) $precedent['incertain'],
                ];
            }
            $precedent = $temps;
        }

        return $tours;
    }
}
