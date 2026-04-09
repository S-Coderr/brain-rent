<?php
// database/setup_database.php
// Run this script to create and populate the database
// Usage: php database/setup_database.php

echo "====================================\n";
echo "BrainRent MySQL Database Setup\n";
echo "====================================\n\n";

// Database credentials are read from config/db.php (and optional config/db.local.php)
require_once __DIR__ . '/../config/db.php';

/**
 * Split a SQL file into executable statements.
 * Supports mysql client style "DELIMITER $$" blocks for procedures/triggers.
 */
function brainrent_split_sql_statements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $delimiter = ';';

    $lines = preg_split("/\r\n|\n|\r/", $sql);
    foreach ($lines as $line) {
        if (preg_match('/^\s*DELIMITER\s+(.+?)\s*$/i', $line, $m)) {
            // Flush anything already complete with the previous delimiter.
            $statements = array_merge($statements, brainrent_extract_statements($buffer, $delimiter));
            $buffer = '';
            $delimiter = trim($m[1]);
            continue;
        }

        $buffer .= $line . "\n";
        $extracted = brainrent_extract_statements($buffer, $delimiter);

        if ($extracted) {
            // brainrent_extract_statements always consumes complete statements and
            // leaves only a possible partial statement in $buffer.
            $buffer = array_pop($extracted);
            $statements = array_merge($statements, $extracted);
        }
    }

    // Final flush
    $extracted = brainrent_extract_statements($buffer, $delimiter);
    if ($extracted) {
        $buffer = array_pop($extracted);
        $statements = array_merge($statements, $extracted);
    }

    return array_values(array_filter(array_map('trim', $statements), static fn($s) => $s !== ''));
}

/**
 * Extract complete statements from $sql using $delimiter.
 * Returns an array where the LAST element is the leftover (possibly partial) buffer.
 */
function brainrent_extract_statements(string $sql, string $delimiter): array
{
    $len = strlen($sql);
    if ($len === 0) {
        return [''];
    }

    $dlen = strlen($delimiter);
    if ($dlen === 0) {
        return [$sql];
    }

    $out = [];
    $current = '';

    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;
    $escape = false;

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $next = ($i + 1 < $len) ? $sql[$i + 1] : '';

        if ($inLineComment) {
            $current .= $ch;
            if ($ch === "\n") {
                $inLineComment = false;
            }
            continue;
        }

        if ($inBlockComment) {
            $current .= $ch;
            if ($ch === '*' && $next === '/') {
                $current .= $next;
                $i++;
                $inBlockComment = false;
            }
            continue;
        }

        if (!$inSingle && !$inDouble && !$inBacktick) {
            // Start of comments
            if ($ch === '-' && $next === '-') {
                $after = ($i + 2 < $len) ? $sql[$i + 2] : '';
                if ($after === ' ' || $after === "\t" || $after === "\r" || $after === "\n" || $after === '') {
                    $current .= $ch . $next;
                    $i++;
                    $inLineComment = true;
                    continue;
                }
            }

            if ($ch === '#') {
                $current .= $ch;
                $inLineComment = true;
                continue;
            }

            if ($ch === '/' && $next === '*') {
                $current .= $ch . $next;
                $i++;
                $inBlockComment = true;
                continue;
            }

            // Delimiter check (multi-char supported)
            if ($dlen === 1) {
                if ($ch === $delimiter) {
                    $stmt = trim($current);
                    if ($stmt !== '') {
                        $out[] = $stmt;
                    }
                    $current = '';
                    continue;
                }
            } else {
                if ($i + $dlen <= $len && substr($sql, $i, $dlen) === $delimiter) {
                    $stmt = trim($current);
                    if ($stmt !== '') {
                        $out[] = $stmt;
                    }
                    $current = '';
                    $i += ($dlen - 1);
                    continue;
                }
            }
        }

        // Quote state transitions
        if ($escape) {
            $current .= $ch;
            $escape = false;
            continue;
        }

        if (($inSingle || $inDouble) && $ch === '\\') {
            $current .= $ch;
            $escape = true;
            continue;
        }

        if (!$inDouble && !$inBacktick && $ch === "'") {
            $inSingle = !$inSingle;
            $current .= $ch;
            continue;
        }
        if (!$inSingle && !$inBacktick && $ch === '"') {
            $inDouble = !$inDouble;
            $current .= $ch;
            continue;
        }
        if (!$inSingle && !$inDouble && $ch === '`') {
            $inBacktick = !$inBacktick;
            $current .= $ch;
            continue;
        }

        $current .= $ch;
    }

    $out[] = $current;
    return $out;
}

try {
    // Connect without database to create it
    echo "Connecting to MySQL server...\n";
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✓ Connected successfully!\n\n";

    // Read SQL file
    echo "Reading SQL schema...\n";
    $sqlFile = __DIR__ . '/brain_rent_mysql.sql';

    if (!file_exists($sqlFile)) {
        die("Error: SQL file not found at: $sqlFile\n");
    }

    $sql = file_get_contents($sqlFile);
    echo "✓ SQL file loaded!\n\n";

    // Execute SQL statements
    echo "Executing SQL statements...\n";

    // Fresh setup: drop the database so re-running doesn't fail on existing tables.
    $pdo->exec('DROP DATABASE IF EXISTS ' . DB_NAME);

    $statements = brainrent_split_sql_statements($sql);
    foreach ($statements as $stmt) {
        $trim = trim($stmt);
        if ($trim === '') {
            continue;
        }
        $pdo->exec($trim);
    }
    echo "✓ Database created and populated successfully!\n\n";

    $featureFiles = [
        __DIR__ . '/add_new_features.sql',
        __DIR__ . '/add_admin_features.sql',
    ];

    foreach ($featureFiles as $featureFile) {
        if (!file_exists($featureFile)) {
            continue;
        }
        echo "Importing " . basename($featureFile) . "...\n";
        $featureSql = file_get_contents($featureFile);
        if ($featureSql === false) {
            continue;
        }
        $featureStatements = brainrent_split_sql_statements($featureSql);
        foreach ($featureStatements as $stmt) {
            $trim = trim($stmt);
            if ($trim === '') {
                continue;
            }
            $pdo->exec($trim);
        }
        echo "✓ " . basename($featureFile) . " imported\n";
    }

    // Verify tables were created
    echo "Verifying database...\n";
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "✓ Found " . count($tables) . " tables:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }

    // Check seed data
    echo "\nVerifying seed data...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "✓ Users: $userCount\n";

    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM expert_profiles");
    $expertCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "✓ Expert profiles: $expertCount\n";

    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM expertise_categories");
    $catCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "✓ Categories: $catCount\n";

    echo "\n====================================\n";
    echo "✓ Setup complete!\n";
    echo "====================================\n\n";
    echo "You can now start the development server:\n";
    echo "  php -S localhost:8000 -t .\n\n";
    echo "Then visit: http://localhost:8000/pages/index.php\n\n";
} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n\n";

    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "Please update the database credentials in this file or config/db.php\n";
        echo "Current settings:\n";
        echo "  Host: " . DB_SERVER . "\n";
        echo "  User: " . DB_USER . "\n";
        echo "  Password: " . (DB_PASSWORD ? '[set]' : '[empty]') . "\n\n";
    }

    if (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "MySQL server appears to be offline. Please start it first.\n\n";
        echo "Common solutions:\n";
        echo "  - XAMPP: Start Apache and MySQL from XAMPP Control Panel\n";
        echo "  - WAMP: Start WampServer\n";
        echo "  - Manual: Start MySQL service\n\n";
    }

    exit(1);
}
