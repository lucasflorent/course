<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeInterface;
use DateTimeImmutable;

/**
 * Determine l'annee scolaire "en cours" (valeur de annee_debut) a partir
 * d'une date de reference : l'annee scolaire n/n+1 commence en septembre.
 */
final class AnneeScolaire
{
    public static function enCours(?DateTimeInterface $reference = null): int
    {
        $reference ??= new DateTimeImmutable();
        $annee = (int) $reference->format('Y');
        $mois = (int) $reference->format('n');

        return $mois >= 9 ? $annee : $annee - 1;
    }
}
