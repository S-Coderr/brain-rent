<?php
// includes/media_blob_helpers.php
// Utilities for storing and streaming uploaded media from database BLOB storage.

require_once __DIR__ . '/../config/db.php';

function brSupportedBlobEntityTypes(): array
{
    return ['libraries', 'notes', 'problem_solving_videos'];
}

function brIsBlobEntityTypeSupported(string $entityType): bool
{
    return in_array($entityType, brSupportedBlobEntityTypes(), true);
}

function brDetectMimeTypeFromExtension(?string $extension): string
{
    $ext = strtolower((string) $extension);
    $map = [
        'pdf' => 'application/pdf',
        'epub' => 'application/epub+zip',
        'mobi' => 'application/x-mobipocket-ebook',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt' => 'text/plain',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'avi' => 'video/x-msvideo',
        'mov' => 'video/quicktime',
        'mkv' => 'video/x-matroska',
    ];

    return $map[$ext] ?? 'application/octet-stream';
}

function brBuildSafeDownloadName(string $title, string $extension, string $fallbackBase = 'file'): string
{
    $base = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $title);
    $base = trim((string) $base, '_');

    if ($base === '') {
        $base = $fallbackBase;
    }

    $ext = strtolower(trim($extension));
    if ($ext !== '') {
        return $base . '.' . $ext;
    }

    return $base;
}

function brSaveEntityFileBlob(
    Database $db,
    string $entityType,
    int $entityId,
    string $absoluteFilePath,
    string $originalFileName,
    ?string $storedFileName = null,
    ?string $extension = null,
    ?string $mimeType = null,
    ?int $fileSize = null,
    string $storageMode = 'hybrid',
    ?string $sourcePath = null
): bool {
    if (!brIsBlobEntityTypeSupported($entityType) || $entityId <= 0 || !is_file($absoluteFilePath)) {
        return false;
    }

    $ext = strtolower((string) ($extension ?: pathinfo($absoluteFilePath, PATHINFO_EXTENSION)));
    $size = $fileSize ?? (int) filesize($absoluteFilePath);
    $checksum = hash_file('sha256', $absoluteFilePath) ?: null;
    $mime = $mimeType ?: brDetectMimeTypeFromExtension($ext);

    $conn = $db->getConnection();
    $stream = fopen($absoluteFilePath, 'rb');
    if ($stream === false) {
        return false;
    }

    $startedTransaction = false;

    try {
        if (!$conn->inTransaction()) {
            $conn->beginTransaction();
            $startedTransaction = true;
        }

        $sql = "INSERT INTO uploaded_files
                    (entity_type, entity_id, original_file_name, stored_file_name, file_extension,
                     mime_type, file_size, file_checksum, source_path, storage_mode, file_blob)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)
                ON DUPLICATE KEY UPDATE
                    original_file_name = VALUES(original_file_name),
                    stored_file_name = VALUES(stored_file_name),
                    file_extension = VALUES(file_extension),
                    mime_type = VALUES(mime_type),
                    file_size = VALUES(file_size),
                    file_checksum = VALUES(file_checksum),
                    source_path = VALUES(source_path),
                    storage_mode = VALUES(storage_mode),
                    file_blob = NULL,
                    updated_at = CURRENT_TIMESTAMP";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $entityType, PDO::PARAM_STR);
        $stmt->bindValue(2, $entityId, PDO::PARAM_INT);
        $stmt->bindValue(3, $originalFileName, PDO::PARAM_STR);
        $stmt->bindValue(4, $storedFileName, PDO::PARAM_STR);
        $stmt->bindValue(5, $ext, PDO::PARAM_STR);
        $stmt->bindValue(6, $mime, PDO::PARAM_STR);
        $stmt->bindValue(7, $size, PDO::PARAM_INT);
        $stmt->bindValue(8, $checksum, PDO::PARAM_STR);
        $stmt->bindValue(9, $sourcePath, PDO::PARAM_STR);
        $stmt->bindValue(10, $storageMode, PDO::PARAM_STR);
        $stmt->execute();

        $idStmt = $conn->prepare('SELECT id FROM uploaded_files WHERE entity_type = ? AND entity_id = ? LIMIT 1');
        $idStmt->execute([$entityType, $entityId]);
        $uploadId = (int) ($idStmt->fetch(PDO::FETCH_ASSOC)['id'] ?? 0);

        if ($uploadId <= 0) {
            throw new RuntimeException('Could not determine uploaded_files row ID.');
        }

        $deleteChunksStmt = $conn->prepare('DELETE FROM uploaded_file_chunks WHERE uploaded_file_id = ?');
        $deleteChunksStmt->execute([$uploadId]);

        $insertChunkStmt = $conn->prepare(
            'INSERT INTO uploaded_file_chunks (uploaded_file_id, chunk_index, chunk_data) VALUES (?, ?, ?)'
        );

        $chunkSizeBytes = 4 * 1024 * 1024;
        $chunkIndex = 0;

        while (!feof($stream)) {
            $chunkData = fread($stream, $chunkSizeBytes);
            if ($chunkData === false) {
                throw new RuntimeException('Could not read uploaded file chunk.');
            }

            if ($chunkData === '') {
                continue;
            }

            $insertChunkStmt->bindValue(1, $uploadId, PDO::PARAM_INT);
            $insertChunkStmt->bindValue(2, $chunkIndex, PDO::PARAM_INT);
            $insertChunkStmt->bindValue(3, $chunkData, PDO::PARAM_LOB);
            $insertChunkStmt->execute();
            $chunkIndex++;
        }

        if ($chunkIndex === 0) {
            $insertChunkStmt->bindValue(1, $uploadId, PDO::PARAM_INT);
            $insertChunkStmt->bindValue(2, 0, PDO::PARAM_INT);
            $insertChunkStmt->bindValue(3, '', PDO::PARAM_LOB);
            $insertChunkStmt->execute();
        }

        if ($startedTransaction) {
            $conn->commit();
        }

        fclose($stream);
        return true;
    } catch (Throwable $e) {
        if ($startedTransaction && $conn->inTransaction()) {
            $conn->rollBack();
        }
        if (is_resource($stream)) {
            fclose($stream);
        }
        error_log('Failed to save media BLOB: ' . $e->getMessage());
        return false;
    }
}

function brGetEntityFileBlobMeta(Database $db, string $entityType, int $entityId): ?array
{
    if (!brIsBlobEntityTypeSupported($entityType) || $entityId <= 0) {
        return null;
    }

    return $db->fetchOne(
        "SELECT id, entity_type, entity_id, original_file_name, stored_file_name,
                file_extension, mime_type, file_size, file_checksum, source_path,
                storage_mode, created_at, updated_at
         FROM uploaded_files
         WHERE entity_type = ? AND entity_id = ?
         LIMIT 1",
        [$entityType, $entityId]
    );
}

function brStreamEntityFileBlobByUploadId(Database $db, int $uploadId): bool
{
    if ($uploadId <= 0) {
        return false;
    }

    $conn = $db->getConnection();
    $bufferedAttr = defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY') ? PDO::MYSQL_ATTR_USE_BUFFERED_QUERY : null;
    $previousBuffered = null;

    if ($bufferedAttr !== null) {
        try {
            $previousBuffered = (bool) $conn->getAttribute($bufferedAttr);
            $conn->setAttribute($bufferedAttr, false);
        } catch (Throwable $e) {
            $bufferedAttr = null;
        }
    }

    try {
        $chunkStmt = $conn->prepare(
            'SELECT chunk_data FROM uploaded_file_chunks WHERE uploaded_file_id = ? ORDER BY chunk_index ASC'
        );
        $chunkStmt->execute([$uploadId]);

        $streamedChunks = false;
        while ($row = $chunkStmt->fetch(PDO::FETCH_NUM)) {
            $streamedChunks = true;
            $chunkData = $row[0] ?? '';

            if (is_resource($chunkData)) {
                fpassthru($chunkData);
                fclose($chunkData);
            } else {
                echo (string) $chunkData;
            }
        }

        if ($streamedChunks) {
            return true;
        }

        $stmt = $conn->prepare('SELECT file_blob FROM uploaded_files WHERE id = ? LIMIT 1');
        $stmt->execute([$uploadId]);

        $lob = null;
        $stmt->bindColumn(1, $lob, PDO::PARAM_LOB);
        $fetched = $stmt->fetch(PDO::FETCH_BOUND);

        if (!$fetched) {
            return false;
        }

        if (is_resource($lob)) {
            fpassthru($lob);
            fclose($lob);
            return true;
        }

        echo (string) $lob;
        return true;
    } catch (Throwable $e) {
        error_log('Failed to stream media BLOB: ' . $e->getMessage());
        return false;
    } finally {
        if ($bufferedAttr !== null && $previousBuffered !== null) {
            try {
                $conn->setAttribute($bufferedAttr, $previousBuffered);
            } catch (Throwable $ignore) {
            }
        }
    }
}

function brUploadedMediaUnionSql(): string
{
    return "
        SELECT 'libraries' AS entity_type,
               l.id AS entity_id,
               l.title,
               l.uploaded_by,
               l.created_at,
               IFNULL(l.is_active, 1) AS is_active,
               l.file_type AS entity_extension,
               l.file_path AS entity_source_path,
               l.file_size AS entity_file_size
        FROM libraries l

        UNION ALL

        SELECT 'notes' AS entity_type,
               n.id AS entity_id,
               n.title,
               n.uploaded_by,
               n.created_at,
               IFNULL(n.is_active, 1) AS is_active,
               n.file_type AS entity_extension,
               n.file_path AS entity_source_path,
               n.file_size AS entity_file_size
        FROM notes n

        UNION ALL

        SELECT 'problem_solving_videos' AS entity_type,
               v.id AS entity_id,
               v.title,
               v.uploaded_by,
               v.created_at,
               IFNULL(v.is_active, 1) AS is_active,
               NULL AS entity_extension,
               v.video_path AS entity_source_path,
               v.video_size AS entity_file_size
        FROM problem_solving_videos v
    ";
}

function brFetchUploadedMedia(Database $db, ?int $uploaderId = null, int $limit = 20): array
{
    $safeLimit = max(1, min($limit, 200));

    $sql = "
        SELECT m.entity_type,
               m.entity_id,
               m.title,
               m.uploaded_by,
               m.created_at,
               m.is_active,
               m.entity_extension,
               m.entity_source_path,
               m.entity_file_size,
               u.full_name AS uploader_name,
               uf.id AS upload_id,
               uf.storage_mode,
               uf.file_extension AS db_extension,
               uf.source_path AS db_source_path,
               uf.file_size AS db_file_size,
               IFNULL(ufc.chunk_count, 0) AS chunk_count,
               CASE
                   WHEN uf.id IS NULL THEN 0
                   WHEN IFNULL(ufc.chunk_count, 0) > 0 THEN 1
                   WHEN uf.file_blob IS NOT NULL THEN 1
                   ELSE 0
               END AS db_linked
        FROM (" . brUploadedMediaUnionSql() . ") m
        LEFT JOIN users u
            ON u.id = m.uploaded_by
        LEFT JOIN uploaded_files uf
            ON uf.entity_type = m.entity_type
           AND uf.entity_id = m.entity_id
        LEFT JOIN (
            SELECT uploaded_file_id, COUNT(*) AS chunk_count
            FROM uploaded_file_chunks
            GROUP BY uploaded_file_id
        ) ufc
            ON ufc.uploaded_file_id = uf.id
    ";

    $params = [];
    if ($uploaderId !== null && $uploaderId > 0) {
        $sql .= " WHERE m.uploaded_by = ?";
        $params[] = $uploaderId;
    }

    $sql .= " ORDER BY m.created_at DESC LIMIT " . (int) $safeLimit;

    return $db->fetchAll($sql, $params);
}

function brGetUploadedMediaSummary(Database $db, ?int $uploaderId = null): array
{
    $sql = "
        SELECT COUNT(*) AS total_items,
               SUM(CASE
                       WHEN uf.id IS NULL THEN 0
                       WHEN IFNULL(ufc.chunk_count, 0) > 0 THEN 1
                       WHEN uf.file_blob IS NOT NULL THEN 1
                       ELSE 0
                   END) AS db_items,
               SUM(CASE WHEN m.is_active = 1 THEN 1 ELSE 0 END) AS active_items,
               IFNULL(SUM(COALESCE(NULLIF(uf.file_size, 0), m.entity_file_size, 0)), 0) AS total_bytes
        FROM (" . brUploadedMediaUnionSql() . ") m
        LEFT JOIN uploaded_files uf
            ON uf.entity_type = m.entity_type
           AND uf.entity_id = m.entity_id
        LEFT JOIN (
            SELECT uploaded_file_id, COUNT(*) AS chunk_count
            FROM uploaded_file_chunks
            GROUP BY uploaded_file_id
        ) ufc
            ON ufc.uploaded_file_id = uf.id
    ";

    $params = [];
    if ($uploaderId !== null && $uploaderId > 0) {
        $sql .= " WHERE m.uploaded_by = ?";
        $params[] = $uploaderId;
    }

    $row = $db->fetchOne($sql, $params) ?: [];

    return [
        'total_items' => (int) ($row['total_items'] ?? 0),
        'db_items' => (int) ($row['db_items'] ?? 0),
        'active_items' => (int) ($row['active_items'] ?? 0),
        'total_bytes' => (int) ($row['total_bytes'] ?? 0),
    ];
}

function brUploadedMediaTypeLabel(string $entityType): string
{
    if ($entityType === 'libraries') {
        return 'Book';
    }

    if ($entityType === 'notes') {
        return 'Note';
    }

    if ($entityType === 'problem_solving_videos') {
        return 'Video';
    }

    return 'File';
}

function brUploadedMediaViewUrl(string $entityType, int $entityId): string
{
    if ($entityType === 'libraries') {
        return APP_URL . '/api/view-ebook.php?id=' . $entityId;
    }

    if ($entityType === 'notes') {
        return APP_URL . '/api/view-note.php?id=' . $entityId;
    }

    if ($entityType === 'problem_solving_videos') {
        return APP_URL . '/pages/video-detail.php?id=' . $entityId;
    }

    return '#';
}

function brUploadedMediaDownloadUrl(string $entityType, int $entityId): string
{
    if ($entityType === 'libraries') {
        return APP_URL . '/api/download-ebook.php?id=' . $entityId;
    }

    if ($entityType === 'notes') {
        return APP_URL . '/api/download-note.php?id=' . $entityId;
    }

    if ($entityType === 'problem_solving_videos') {
        return APP_URL . '/api/download-video.php?id=' . $entityId;
    }

    return '#';
}
