<?php

declare(strict_types=1);

namespace App\Csv;

/**
 * Genere un apercu du fichier CSV importe selon les 6 combinaisons
 * encodage x separateur possibles, pour que l'administrateur choisisse
 * visuellement celle qui affiche correctement les donnees (accents, etc.).
 */
final class CsvPreview
{
    private const ENCODAGES = ['UTF-8', 'ISO-8859-1', 'Windows-1252'];
    private const SEPARATEURS = [',' => 'virgule', ';' => 'point-virgule'];

    /**
     * @return array<string, array{cle:string, encodage:string, separateur:string,
     *   label:string, valide:bool, entetes:string[], lignes_apercu:array<int,string[]>,
     *   nb_lignes_total:int, nb_colonnes_incoherentes:int}>
     */
    public static function genererApercus(string $brut, int $nbLignes = 5): array
    {
        $apercus = [];

        foreach (self::ENCODAGES as $encodage) {
            foreach (self::SEPARATEURS as $separateur => $labelSeparateur) {
                $cle = self::cle($encodage, $separateur);
                $converti = self::convertir($brut, $encodage);
                $valide = $converti !== null;
                $lignes = $valide ? self::parserCsv($converti, $separateur) : [];
                $entetes = $lignes[0] ?? [];

                $nbColonnesIncoherentes = 0;
                foreach ($lignes as $ligne) {
                    if (count($ligne) !== count($entetes)) {
                        $nbColonnesIncoherentes++;
                    }
                }

                $apercus[$cle] = [
                    'cle' => $cle,
                    'encodage' => $encodage,
                    'separateur' => $separateur,
                    'label' => $encodage . ' / ' . $labelSeparateur,
                    'valide' => $valide && $entetes !== [],
                    'entetes' => $entetes,
                    'lignes_apercu' => array_slice($lignes, 1, $nbLignes),
                    'nb_lignes_total' => max(0, count($lignes) - 1),
                    'nb_colonnes_incoherentes' => $nbColonnesIncoherentes,
                ];
            }
        }

        return $apercus;
    }

    /**
     * @return array<int, string[]>
     */
    public static function parseComplet(string $brut, string $encodage, string $separateur): array
    {
        $converti = self::convertir($brut, $encodage);

        return $converti === null ? [] : self::parserCsv($converti, $separateur);
    }

    public static function cle(string $encodage, string $separateur): string
    {
        return $encodage . '|' . $separateur;
    }

    private static function convertir(string $brut, string $encodage): ?string
    {
        if ($encodage === 'UTF-8') {
            return mb_check_encoding($brut, 'UTF-8') ? $brut : null;
        }

        $converti = @mb_convert_encoding($brut, 'UTF-8', $encodage);

        return $converti === false ? null : $converti;
    }

    /**
     * @return array<int, string[]>
     */
    private static function parserCsv(string $contenu, string $separateur): array
    {
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $contenu);
        rewind($handle);

        $lignes = [];
        while (($ligne = fgetcsv($handle, 0, $separateur)) !== false) {
            if ($ligne === [null]) {
                continue;
            }
            $lignes[] = $ligne;
        }

        fclose($handle);

        return $lignes;
    }
}
