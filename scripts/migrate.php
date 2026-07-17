<?php

declare(strict_types=1);

use App\Config\Database;
use App\Config\Env;

require __DIR__ . '/../config/bootstrap.php';

$dbName = Env::get('DB_NAME');
$charset = Env::get('DB_CHARSET', 'utf8mb4');

echo "Creation de la base '{$dbName}' si necessaire...\n";
$pdoSansBase = Database::pdoSansBase();
$pdoSansBase->exec(
    "CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci"
);

$schemaPath = __DIR__ . '/../docs/schema.sql';
if (!is_file($schemaPath)) {
    fwrite(STDERR, "Fichier introuvable : {$schemaPath}\n");
    exit(1);
}

$sql = file_get_contents($schemaPath);

// Retire les lignes de commentaire (le fichier contient un bloc de requetes
// de reference entierement commente, a ne pas executer).
$lignesUtiles = array_filter(
    explode("\n", $sql),
    static fn (string $ligne): bool => !str_starts_with(trim($ligne), '--')
);
$sqlSansCommentaires = implode("\n", $lignesUtiles);

$instructions = array_filter(
    array_map('trim', explode(';', $sqlSansCommentaires)),
    static fn (string $instruction): bool => $instruction !== ''
);

$pdo = Database::pdo();
$compte = 0;

foreach ($instructions as $instruction) {
    $pdo->exec($instruction);
    $compte++;
    if (preg_match('/CREATE TABLE\s+(\w+)/i', $instruction, $m)) {
        echo "  Table creee : {$m[1]}\n";
    }
}

echo "{$compte} instruction(s) executee(s). Migration terminee.\n";
