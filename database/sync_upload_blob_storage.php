<?php
// database/sync_upload_blob_storage.php
// Sync existing filesystem uploads into uploaded_files table and print analysis.

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/media_blob_helpers.php';

echo "====================================\n";
echo "BrainRent Upload Blob Sync + Analysis\n";
echo "====================================\n\n";

$db = Database::getInstance();

$targets = [
    [
        'entity_type' => 'libraries',
        'table' => 'libraries',
        'id_col' => 'id',
        'path_col' => 'file_path',
        'size_col' => 'file_size',
        'ext_col' => 'file_type',
        'label' => 'E-Books',
    ],
    [
        'entity_type' => 'notes',
        'table' => 'notes',
        'id_col' => 'id',
        'path_col' => 'file_path',
        'size_col' => 'file_size',
        'ext_col' => 'file_type',
        'label' => 'Notes',
    ],
    [
        'entity_type' => 'problem_solving_videos',
        'table' => 'problem_solving_videos',
        'id_col' => 'id',
        'path_col' => 'video_path',
        'size_col' => 'video_size',
        'ext_col' => null,
        'label' => 'Videos',
    ],
];

$totals = [
    'rows' => 0,
    'stored' => 0,
    'skipped' => 0,
    'missing_path' => 0,
    'failed' => 0,
    'bytes' => 0,
];

foreach ($targets as $target) {
    $table = $target['table'];
    $entityType = $target['entity_type'];

    $rows = $db->fetchAll("SELECT * FROM {$table}");

    $summary = [
        'rows' => count($rows),
        'stored' => 0,
        'skipped' => 0,
        'missing_path' => 0,
        'failed' => 0,
        'bytes' => 0,
    ];

    foreach ($rows as $row) {
        $entityId = (int) ($row[$target['id_col']] ?? 0);
        $storedPathValue = trim((string) ($row[$target['path_col']] ?? ''));

        if ($entityId <= 0 || $storedPathValue === '') {
            $summary['missing_path']++;
            continue;
        }

        $absolutePath = resolveUploadedFilePath($storedPathValue);
        if (!is_file($absolutePath)) {
            $summary['missing_path']++;
            continue;
        }

        $ext = '';
        if (!empty($target['ext_col']) && !empty($row[$target['ext_col']])) {
            $ext = strtolower((string) $row[$target['ext_col']]);
        }
        if ($ext === '') {
            $ext = strtolower((string) pathinfo($absolutePath, PATHINFO_EXTENSION));
        }

        $existing = brGetEntityFileBlobMeta($db, $entityType, $entityId);
        if ($existing) {
            $summary['skipped']++;
            $summary['bytes'] += (int) ($existing['file_size'] ?? 0);
            continue;
        }

        $ok = brSaveEntityFileBlob(
            $db,
            $entityType,
            $entityId,
            $absolutePath,
            basename($absolutePath),
            basename($absolutePath),
            $ext,
            null,
            null,
            'hybrid',
            $storedPathValue
        );

        if ($ok) {
            $summary['stored']++;
            $summary['bytes'] += (int) filesize($absolutePath);
        } else {
            $summary['failed']++;
        }
    }

    $totals['rows'] += $summary['rows'];
    $totals['stored'] += $summary['stored'];
    $totals['skipped'] += $summary['skipped'];
    $totals['missing_path'] += $summary['missing_path'];
    $totals['failed'] += $summary['failed'];
    $totals['bytes'] += $summary['bytes'];

    echo '[' . $target['label'] . "]\n";
    echo 'Rows: ' . $summary['rows'] . "\n";
    echo 'Stored to DB: ' . $summary['stored'] . "\n";
    echo 'Already linked: ' . $summary['skipped'] . "\n";
    echo 'Missing files/paths: ' . $summary['missing_path'] . "\n";
    echo 'Failed: ' . $summary['failed'] . "\n";
    echo 'Total size linked: ' . number_format($summary['bytes'] / 1024 / 1024, 2) . " MB\n\n";
}

$blobStats = $db->fetchAll(
    "SELECT entity_type, COUNT(*) AS file_count, COALESCE(SUM(file_size), 0) AS total_bytes
     FROM uploaded_files
     GROUP BY entity_type
     ORDER BY entity_type"
);

echo "Overall Blob Table Stats\n";
if (empty($blobStats)) {
    echo "No files currently linked in uploaded_files.\n";
} else {
    foreach ($blobStats as $row) {
        $mb = ((int) $row['total_bytes']) / 1024 / 1024;
        echo '- ' . $row['entity_type'] . ': ' . (int) $row['file_count'] . ' files, ' . number_format($mb, 2) . " MB\n";
    }
}

echo "\n====================================\n";
echo "Sync + Analysis Complete\n";
echo "====================================\n";
echo 'Total rows scanned: ' . $totals['rows'] . "\n";
echo 'Total newly stored: ' . $totals['stored'] . "\n";
echo 'Total already linked: ' . $totals['skipped'] . "\n";
echo 'Total missing paths/files: ' . $totals['missing_path'] . "\n";
echo 'Total failed: ' . $totals['failed'] . "\n";
echo 'Total linked size: ' . number_format($totals['bytes'] / 1024 / 1024, 2) . " MB\n";
