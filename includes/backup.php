<?php
/**
 * Copia de seguridad completa: esquema (CREATE TABLE IF NOT EXISTS, igual que
 * install.php/update.php) + todos los datos como INSERT, en un único .sql restaurable
 * con `mysql < backup.sql` sobre una base de datos vacía, o revisable a mano.
 */

require_once __DIR__ . '/schema.php';

function ludoteca_backup_sql(PDO $pdo): string
{
    $tables = ['players', 'games', 'plays', 'play_players', 'wishlist', 'loans', 'settings'];

    $sql = "-- Copia de seguridad de Ludoteca — " . date('Y-m-d H:i:s') . "\n";
    $sql .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach (ludoteca_schema_statements() as $stmt) {
        $sql .= $stmt . ";\n\n";
    }

    foreach ($tables as $table) {
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            continue;
        }
        $columns = array_keys($rows[0]);
        $columnList = '`' . implode('`, `', $columns) . '`';

        $sql .= "-- {$table} (" . count($rows) . " filas)\n";
        foreach ($rows as $row) {
            $values = array_map(
                fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                $row
            );
            $sql .= "INSERT INTO `{$table}` ({$columnList}) VALUES (" . implode(', ', $values) . ");\n";
        }
        $sql .= "\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}
