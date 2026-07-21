<?php

declare(strict_types=1);

namespace App\Graphique;

use App\Support\Temps;
use GdImage;

/**
 * Dessine (GD natif, sans dependance) un graphique "duree de tour par n°
 * de tour" pour 1 a 4 seances superposees. Les series ne se distinguent
 * jamais par la couleur (tout est trace en noir) mais par un style de
 * trait + une forme de marqueur, pour rester lisible en impression N&B.
 * Un tour incertain est affiche avec un marqueur non rempli (contour seul).
 */
final class GraphiqueTours
{
    public const LARGEUR_PX = 880;
    public const HAUTEUR_PX = 590;
    public const MAX_SERIES = 4;

    private const MARGE_GAUCHE = 70;
    private const MARGE_DROITE = 20;
    private const MARGE_HAUT = 25;
    private const HAUTEUR_LABELS_X = 25;
    private const HAUTEUR_LEGENDE = 4 * 24;

    private const STYLES = [
        0 => ['trait' => 'plein', 'marqueur' => 'cercle'],
        1 => ['trait' => 'tirets', 'marqueur' => 'carre'],
        2 => ['trait' => 'pointilles', 'marqueur' => 'triangle'],
        3 => ['trait' => 'tiret_point', 'marqueur' => 'losange'],
    ];

    private const PAS_GRADUATION_POSSIBLES = [5, 10, 15, 20, 30, 45, 60, 90, 120, 180, 300];

    /**
     * @param array<int, array{libelle:string, tours:array<int,array{numero_tour:int,duree_tour_s:int,incertain:bool}>, vitesse_moyenne_kmh:?float}> $series
     */
    public static function genererPng(array $series): string
    {
        $series = array_slice($series, 0, self::MAX_SERIES);

        $im = imagecreatetruecolor(self::LARGEUR_PX, self::HAUTEUR_PX);
        $blanc = imagecolorallocate($im, 255, 255, 255);
        $noir = imagecolorallocate($im, 0, 0, 0);
        $gris = imagecolorallocate($im, 210, 210, 210);
        imagefill($im, 0, 0, $blanc);

        $gauche = self::MARGE_GAUCHE;
        $droite = self::LARGEUR_PX - self::MARGE_DROITE;
        $haut = self::MARGE_HAUT;
        $bas = self::HAUTEUR_PX - self::HAUTEUR_LABELS_X - self::HAUTEUR_LEGENDE;

        [$maxTour, $maxDuree, $auMoinsUnPoint] = self::calculerBornes($series);
        $borneY = self::calculerBorneY($maxDuree);
        $pasY = self::choisirPasGraduation($borneY);

        self::dessinerAxes($im, $series, $gauche, $droite, $haut, $bas, $maxTour, $borneY, $pasY, $noir, $gris);

        if (!$auMoinsUnPoint) {
            self::centrerTexte($im, 5, 'Aucune donnée', $gauche, $droite, (int) (($haut + $bas) / 2), $noir);
        }

        imagesetthickness($im, 2);
        foreach ($series as $i => $s) {
            self::dessinerSerie($im, $s, self::STYLES[$i], $gauche, $droite, $haut, $bas, $maxTour, $borneY, $noir);
        }
        imagesetthickness($im, 1);

        self::dessinerLegende($im, $series, $gauche, $bas + self::HAUTEUR_LABELS_X + 8, $noir);

        ob_start();
        imagepng($im);
        $octets = ob_get_clean();
        imagedestroy($im);

        return (string) $octets;
    }

    /**
     * @return array{0:int, 1:float, 2:bool}
     */
    private static function calculerBornes(array $series): array
    {
        $maxTour = 1;
        $maxDuree = 60.0;
        $auMoinsUnPoint = false;

        foreach ($series as $s) {
            foreach ($s['tours'] as $t) {
                $auMoinsUnPoint = true;
                $maxTour = max($maxTour, $t['numero_tour']);
                $maxDuree = max($maxDuree, (float) $t['duree_tour_s']);
            }
        }

        return [$maxTour, $maxDuree, $auMoinsUnPoint];
    }

    private static function calculerBorneY(float $maxDuree): float
    {
        return $maxDuree * 1.1;
    }

    private static function choisirPasGraduation(float $borneY): int
    {
        foreach (self::PAS_GRADUATION_POSSIBLES as $pas) {
            if ($borneY / $pas <= 8) {
                return $pas;
            }
        }

        return 300;
    }

    private static function dessinerAxes(
        GdImage $im,
        array $series,
        int $gauche,
        int $droite,
        int $haut,
        int $bas,
        int $maxTour,
        float $borneY,
        int $pasY,
        int $noir,
        int $gris
    ): void {
        $borneAffichee = max($pasY, (int) ceil($borneY / $pasY) * $pasY);

        for ($valeur = 0; $valeur <= $borneAffichee; $valeur += $pasY) {
            $y = self::yPourValeur((float) $valeur, $borneAffichee, $haut, $bas);
            imageline($im, $gauche, $y, $droite, $y, $gris);
            $label = self::texteGd(Temps::format($valeur));
            $largeur = imagefontwidth(3) * strlen($label);
            imagestring($im, 3, $gauche - $largeur - 8, $y - (int) (imagefontheight(3) / 2), $label, $noir);
        }

        imageline($im, $gauche, $haut, $gauche, $bas, $noir);
        imageline($im, $gauche, $bas, $droite, $bas, $noir);

        $pasX = max(1, (int) ceil($maxTour / 20));
        for ($n = 1; $n <= $maxTour; $n += $pasX) {
            $x = self::xPourTour($n, $maxTour, $gauche, $droite);
            $label = self::texteGd((string) $n);
            $largeur = imagefontwidth(2) * strlen($label);
            imagestring($im, 2, $x - (int) ($largeur / 2), $bas + 5, $label, $noir);
        }
    }

    private static function dessinerSerie(
        GdImage $im,
        array $serie,
        array $style,
        int $gauche,
        int $droite,
        int $haut,
        int $bas,
        int $maxTour,
        float $borneY,
        int $noir
    ): void {
        $borneAffichee = max(1.0, $borneY);
        $precedent = null;

        foreach ($serie['tours'] as $t) {
            $x = self::xPourTour($t['numero_tour'], $maxTour, $gauche, $droite);
            $y = self::yPourValeur((float) $t['duree_tour_s'], $borneAffichee, $haut, $bas);
            if ($precedent !== null) {
                self::tracerLigne($im, $style['trait'], $precedent[0], $precedent[1], $x, $y, $noir);
            }
            $precedent = [$x, $y];
        }

        foreach ($serie['tours'] as $t) {
            $x = self::xPourTour($t['numero_tour'], $maxTour, $gauche, $droite);
            $y = self::yPourValeur((float) $t['duree_tour_s'], $borneAffichee, $haut, $bas);
            self::dessinerMarqueur($im, $style['marqueur'], $x, $y, !$t['incertain'], $noir);
        }
    }

    private static function dessinerLegende(GdImage $im, array $series, int $gauche, int $yDepart, int $noir): void
    {
        for ($i = 0; $i < self::MAX_SERIES; $i++) {
            if (!isset($series[$i])) {
                continue;
            }

            $y = $yDepart + $i * 24 + 12;
            $style = self::STYLES[$i];

            self::tracerLigne($im, $style['trait'], $gauche, $y, $gauche + 40, $y, $noir);
            self::dessinerMarqueur($im, $style['marqueur'], $gauche + 20, $y, true, $noir);

            $texte = $series[$i]['libelle'];
            if ($series[$i]['vitesse_moyenne_kmh'] !== null) {
                $texte .= ' - ' . number_format($series[$i]['vitesse_moyenne_kmh'], 1) . ' km/h';
            }
            imagestring($im, 3, $gauche + 50, $y - 7, self::texteGd($texte), $noir);
        }
    }

    private static function centrerTexte(GdImage $im, int $fonte, string $texte, int $gauche, int $droite, int $y, int $noir): void
    {
        $texte = self::texteGd($texte);
        $largeur = imagefontwidth($fonte) * strlen($texte);
        $x = (int) (($gauche + $droite) / 2 - $largeur / 2);
        imagestring($im, $fonte, $x, $y, $texte, $noir);
    }

    /**
     * Les fontes bitmap integrees de GD ne comprennent que le Latin-1, pas
     * l'UTF-8 (les caracteres non representables, ex. le tiret cadratin,
     * sont translitteres en equivalent ASCII plutot que de s'afficher en
     * mojibake).
     */
    private static function texteGd(string $texte): string
    {
        $converti = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texte);

        return $converti !== false ? $converti : $texte;
    }

    private static function xPourTour(int $numeroTour, int $maxTour, int $gauche, int $droite): int
    {
        if ($maxTour <= 1) {
            return (int) (($gauche + $droite) / 2);
        }

        $ratio = ($numeroTour - 1) / ($maxTour - 1);

        return $gauche + (int) round($ratio * ($droite - $gauche));
    }

    private static function yPourValeur(float $valeur, float $borneY, int $haut, int $bas): int
    {
        if ($borneY <= 0.0) {
            return $bas;
        }

        $ratio = $valeur / $borneY;

        return $bas - (int) round($ratio * ($bas - $haut));
    }

    private static function appliquerStyle(GdImage $im, string $style, int $noir): void
    {
        $transparent = IMG_COLOR_TRANSPARENT;
        $motif = match ($style) {
            'tirets' => [...array_fill(0, 8, $noir), ...array_fill(0, 6, $transparent)],
            'pointilles' => [...array_fill(0, 2, $noir), ...array_fill(0, 4, $transparent)],
            'tiret_point' => [
                ...array_fill(0, 8, $noir), ...array_fill(0, 4, $transparent),
                ...array_fill(0, 2, $noir), ...array_fill(0, 4, $transparent),
            ],
            default => [$noir],
        };
        imagesetstyle($im, $motif);
    }

    private static function tracerLigne(GdImage $im, string $style, int $x1, int $y1, int $x2, int $y2, int $noir): void
    {
        if ($style === 'plein') {
            imageline($im, $x1, $y1, $x2, $y2, $noir);

            return;
        }

        self::appliquerStyle($im, $style, $noir);
        imageline($im, $x1, $y1, $x2, $y2, IMG_COLOR_STYLED);
    }

    private static function dessinerMarqueur(GdImage $im, string $forme, int $x, int $y, bool $rempli, int $noir, int $rayon = 5): void
    {
        switch ($forme) {
            case 'cercle':
                if ($rempli) {
                    imagefilledellipse($im, $x, $y, $rayon * 2, $rayon * 2, $noir);
                } else {
                    imageellipse($im, $x, $y, $rayon * 2, $rayon * 2, $noir);
                }
                break;
            case 'carre':
                if ($rempli) {
                    imagefilledrectangle($im, $x - $rayon, $y - $rayon, $x + $rayon, $y + $rayon, $noir);
                } else {
                    imagerectangle($im, $x - $rayon, $y - $rayon, $x + $rayon, $y + $rayon, $noir);
                }
                break;
            case 'triangle':
                $points = [$x, $y - $rayon, $x - $rayon, $y + $rayon, $x + $rayon, $y + $rayon];
                if ($rempli) {
                    imagefilledpolygon($im, $points, $noir);
                } else {
                    imagepolygon($im, $points, $noir);
                }
                break;
            case 'losange':
                $points = [$x, $y - $rayon, $x + $rayon, $y, $x, $y + $rayon, $x - $rayon, $y];
                if ($rempli) {
                    imagefilledpolygon($im, $points, $noir);
                } else {
                    imagepolygon($im, $points, $noir);
                }
                break;
        }
    }
}
