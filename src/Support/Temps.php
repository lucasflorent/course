<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Conversion entre le format d'affichage/saisie mm:ss et les secondes
 * stockees en base (temps cumules depuis le depart).
 */
final class Temps
{
    public static function toSeconds(string $mmss): ?int
    {
        if (!preg_match('/^(\d{1,3}):([0-5]\d)$/', trim($mmss), $m)) {
            return null;
        }

        return ((int) $m[1] * 60) + (int) $m[2];
    }

    public static function format(int $secondes): string
    {
        $minutes = intdiv($secondes, 60);
        $reste = $secondes % 60;

        return sprintf('%d:%02d', $minutes, $reste);
    }
}
