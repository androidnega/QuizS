#!/usr/bin/env php
<?php
/**
 * Converts database.sqlite to a MySQL-compatible .sql dump for phpMyAdmin.
 * Run: php convert-sqlite-to-mysql.php
 * Output: QuizSnap-mysql.sql
 */

$sqlitePath = __DIR__ . '/database.sqlite';
$outputPath = __DIR__ . '/QuizSnap-mysql.sql';

if (!is_file($sqlitePath)) {
    fwrite(STDERR, "Error: database.sqlite not found.\n");
    exit(1);
}

$pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$out = fopen($outputPath, 'w');
if (!$out) {
    fwrite(STDERR, "Error: Cannot write to {$outputPath}\n");
    exit(1);
}

// phpMyAdmin-friendly header
fwrite($out, "-- QuizSnap MySQL dump (converted from SQLite)\n");
fwrite($out, "-- " . date('Y-m-d H:i:s') . "\n\n");
fwrite($out, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $createSql = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name=" . $pdo->quote($table))->fetchColumn();
    if (!$createSql) {
        continue;
    }

    $mysqlCreate = sqliteCreateToMysql($createSql, $table);
    fwrite($out, "DROP TABLE IF EXISTS `" . $table . "`;\n");
    fwrite($out, $mysqlCreate . ";\n\n");

    $stmt = $pdo->query("SELECT * FROM " . $pdo->quote($table));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) === 0) {
        continue;
    }

    $columns = array_keys($rows[0]);
    $colList = implode('`, `', $columns);
    foreach ($rows as $row) {
        $values = [];
        foreach ($columns as $col) {
            $v = $row[$col];
            if ($v === null) {
                $values[] = 'NULL';
            } else {
                $values[] = $pdo->quote($v);
            }
        }
        fwrite($out, "INSERT INTO `{$table}` (`{$colList}`) VALUES (" . implode(', ', $values) . ");\n");
    }
    fwrite($out, "\n");
}

fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");
fclose($out);

echo "Written: {$outputPath}\n";

/**
 * Convert SQLite CREATE TABLE to MySQL syntax.
 */
function sqliteCreateToMysql(string $sql, string $tableName): string
{
    $sql = preg_replace('/^CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+"[^"]+"\s*\(/i', 'CREATE TABLE `' . $tableName . '` (', $sql);
    $sql = preg_replace('/^CREATE\s+TABLE\s+"[^"]+"\s*\(/i', 'CREATE TABLE `' . $tableName . '` (', $sql);
    $sql = str_replace('"', '`', $sql);

    // Remove trailing ); and any following CREATE INDEX / UNIQUE (we'll skip indexes from this string; SQLite dump has them separate)
    $sql = trim($sql);
    if (substr($sql, -2) === ');') {
        $sql = substr($sql, 0, -2);
    }
    if (substr($sql, -1) === ')') {
        $sql = substr($sql, 0, -1);
    }

    $inside = substr($sql, strpos($sql, '(') + 1);
    $parts = [];
    $current = '';
    $depth = 0;
    $len = strlen($inside);
    for ($i = 0; $i < $len; $i++) {
        $c = $inside[$i];
        if ($c === '(') {
            $depth++;
            $current .= $c;
        } elseif ($c === ')') {
            $depth--;
            $current .= $c;
        } elseif (($c === ',' && $depth === 0) || $i === $len - 1) {
            if ($i === $len - 1 && $c !== ',') {
                $current .= $c;
            }
            $def = trim($current);
            if ($def !== '') {
                $parts[] = convertColumnDef($def);
            }
            $current = '';
        } else {
            $current .= $c;
        }
    }
    if (trim($current) !== '') {
        $parts[] = convertColumnDef(trim($current));
    }

    $create = 'CREATE TABLE `' . $tableName . '` (' . "\n  " . implode(",\n  ", $parts) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    return $create;
}

function convertColumnDef(string $def): string
{
    $orig = $def;
    $def = trim($def);
    $upper = strtoupper($def);

    // PRIMARY KEY ("key") for settings table
    if (preg_match('/^\s*PRIMARY\s+KEY\s+\(`([^`]+)`\)\s*$/i', $def, $m)) {
        return 'PRIMARY KEY (`' . $m[1] . '`)';
    }

    // FOREIGN KEY (...) REFERENCES - convert but keep
    if (strpos($upper, 'FOREIGN KEY') === 0) {
        return $def;
    }

    $def = preg_replace('/\s+not\s+null\s+/i', ' NOT NULL ', $def);
    $def = preg_replace('/\s+default\s+(\'[^\']*\'|\d+)\s*/i', ' DEFAULT $1 ', $def);

    // integer primary key autoincrement -> BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY
    if (preg_match('/^`(\w+)`\s+integer\s+primary\s+key\s+autoincrement\s+not\s+null/i', $def, $m)) {
        return '`' . $m[1] . '` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
    }
    if (preg_match('/^`(\w+)`\s+integer\s+primary\s+key\s+autoincrement/i', $def, $m)) {
        return '`' . $m[1] . '` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
    }
    if (preg_match('/^`(\w+)`\s+integer\s+primary\s+key/i', $def, $m)) {
        return '`' . $m[1] . '` BIGINT UNSIGNED NOT NULL PRIMARY KEY';
    }

    $def = preg_replace('/\binteger\b/i', 'BIGINT UNSIGNED', $def);
    $def = preg_replace('/\bvarchar\b(?!\s*\()/i', 'VARCHAR(255)', $def);
    $def = preg_replace('/\bdatetime\b/i', 'DATETIME', $def);
    $def = preg_replace('/\btext\b/i', 'TEXT', $def);
    $def = preg_replace('/\bnumeric\b/i', 'DECIMAL(10,2)', $def);
    $def = preg_replace('/\btinyint\s*\(\s*1\s*\)/i', 'TINYINT(1)', $def);
    $def = preg_replace("/\bdefault\s+'(\d+)'/i", 'DEFAULT $1', $def);

    return $def;
}
