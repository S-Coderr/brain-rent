<?php
// database/patch_library_notes_active.php
// Ensure is_active defaults to 1 and re-enable existing uploads for libraries and notes.
// Usage: php database/patch_library_notes_active.php

require_once __DIR__ . '/../config/db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

function tableExists(Database $db, string $table): bool
{
    $row = $db->fetchOne(
        "SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ?",
        [DB_NAME, $table]
    );
    return (bool) $row;
}

function ensureIsActiveColumn(Database $db, PDO $conn, string $table): void
{
    $columns = $db->fetchAll("SHOW COLUMNS FROM {$table}");
    $existing = [];
    foreach ($columns as $col) {
        $existing[strtolower($col['Field'])] = $col;
    }

    if (!isset($existing['is_active'])) {
        $conn->exec("ALTER TABLE {$table} ADD COLUMN is_active TINYINT(1) DEFAULT 1");
        echo "Added is_active column on {$table}.\n";
        return;
    }

    $conn->exec("ALTER TABLE {$table} MODIFY COLUMN is_active TINYINT(1) DEFAULT 1");
    echo "Updated {$table}.is_active default to 1.\n";
}

$tables = ['libraries', 'notes'];

foreach ($tables as $table) {
    if (!tableExists($db, $table)) {
        echo "Table '{$table}' not found. Run php database/setup_database.php\n";
        continue;
    }

    ensureIsActiveColumn($db, $conn, $table);

    $updated = $db->execute(
        "UPDATE {$table} SET is_active = 1 WHERE is_active IS NULL OR is_active = 0"
    );
    echo "Enabled {$updated} record(s) in {$table}.\n";
}
