<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Statistiques d'une seance a partir des tours derives (App\Support\TourDerivation).
 * Les tours incertains sont exclus. Si aucun tour certain n'existe, toutes les
 * valeurs retournees sont null (afficher "Non calculable", jamais 0).
 */
final class Statistiques
{
    /**
     * @param array<int, array{duree_tour_s:int, incertain:bool}> $tours
     * @return array{meilleur_s:?int, pire_s:?int, moyenne_s:?float, ecart_type_s:?float, nb_tours_certains:int}
     */
    public static function calculer(array $tours): array
    {
        $certains = array_values(array_filter($tours, static fn (array $t): bool => !$t['incertain']));
        $n = count($certains);

        if ($n === 0) {
            return ['meilleur_s' => null, 'pire_s' => null, 'moyenne_s' => null, 'ecart_type_s' => null, 'nb_tours_certains' => 0];
        }

        $durees = array_column($certains, 'duree_tour_s');
        $moyenne = array_sum($durees) / $n;
        $variance = array_sum(array_map(static fn (int $d): float => ($d - $moyenne) ** 2, $durees)) / $n;

        return [
            'meilleur_s' => min($durees),
            'pire_s' => max($durees),
            'moyenne_s' => $moyenne,
            'ecart_type_s' => sqrt($variance),
            'nb_tours_certains' => $n,
        ];
    }

    /**
     * Vitesse moyenne en km/h. Null si la longueur de tour n'est pas
     * renseignee ou si aucun tour certain n'existe.
     */
    public static function vitesseMoyenneKmh(?float $longueurTourM, ?float $dureeMoyenneS): ?float
    {
        if ($longueurTourM === null || $dureeMoyenneS === null || $dureeMoyenneS <= 0.0) {
            return null;
        }

        return ($longueurTourM / $dureeMoyenneS) * 3.6;
    }
}
