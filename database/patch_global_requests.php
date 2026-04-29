<?php
// database/patch_global_requests.php
// Patch schema for global requests (nullable expert_id, is_global flag, nullable payee/preferred_expert).
// Usage: php database/patch_global_requests.php

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

function columnExists(Database $db, string $table, string $column): bool
{
    $row = $db->fetchOne(
        "SELECT 1 FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?",
        [DB_NAME, $table, $column]
    );
    return (bool) $row;
}

function foreignKeyExists(Database $db, string $table, string $fk): bool
{
    $row = $db->fetchOne(
        "SELECT 1 FROM information_schema.key_column_usage
         WHERE table_schema = ? AND table_name = ? AND constraint_name = ?
           AND referenced_table_name IS NOT NULL",
        [DB_NAME, $table, $fk]
    );
    return (bool) $row;
}

function dropForeignKeyIfExists(Database $db, PDO $conn, string $table, string $fk): void
{
    if (foreignKeyExists($db, $table, $fk)) {
        $conn->exec("ALTER TABLE {$table} DROP FOREIGN KEY {$fk}");
        echo "Dropped FK {$fk} on {$table}.\n";
    }
}

// thinking_requests
if (tableExists($db, 'thinking_requests')) {
    if (!columnExists($db, 'thinking_requests', 'is_global')) {
        $conn->exec("ALTER TABLE thinking_requests ADD COLUMN is_global TINYINT(1) DEFAULT 0");
        echo "Added thinking_requests.is_global.\n";
    }

    dropForeignKeyIfExists($db, $conn, 'thinking_requests', 'fk_tr_expert');
    $conn->exec("ALTER TABLE thinking_requests MODIFY COLUMN expert_id INT NULL");
    $conn->exec("ALTER TABLE thinking_requests ADD CONSTRAINT fk_tr_expert FOREIGN KEY (expert_id) REFERENCES users(id) ON DELETE SET NULL");
    echo "Updated thinking_requests.expert_id to nullable.\n";

    $db->execute("UPDATE thinking_requests SET is_global = 0 WHERE is_global IS NULL");
}

// payments
if (tableExists($db, 'payments')) {
    dropForeignKeyIfExists($db, $conn, 'payments', 'fk_pay_payee');
    $conn->exec("ALTER TABLE payments MODIFY COLUMN payee_id INT NULL");
    $conn->exec("ALTER TABLE payments ADD CONSTRAINT fk_pay_payee FOREIGN KEY (payee_id) REFERENCES users(id) ON DELETE SET NULL");
    echo "Updated payments.payee_id to nullable.\n";
}

// temporary_payments
if (tableExists($db, 'temporary_payments')) {
    dropForeignKeyIfExists($db, $conn, 'temporary_payments', 'fk_tmp_pay_expert');
    $conn->exec("ALTER TABLE temporary_payments MODIFY COLUMN preferred_expert_id INT NULL");
    $conn->exec("ALTER TABLE temporary_payments ADD CONSTRAINT fk_tmp_pay_expert FOREIGN KEY (preferred_expert_id) REFERENCES users(id) ON DELETE SET NULL");
    echo "Updated temporary_payments.preferred_expert_id to nullable.\n";
}

echo "Global request patch complete.\n";
