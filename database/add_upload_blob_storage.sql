-- =============================================
-- ADD FEATURE: Store uploaded media files in database (BLOB)
-- =============================================

USE brain_rent;

CREATE TABLE IF NOT EXISTS uploaded_files (
    id                  BIGINT AUTO_INCREMENT PRIMARY KEY,
    entity_type         ENUM('libraries','notes','problem_solving_videos') NOT NULL,
    entity_id           INT NOT NULL,
    original_file_name  VARCHAR(255) NOT NULL,
    stored_file_name    VARCHAR(255) DEFAULT NULL,
    file_extension      VARCHAR(20) DEFAULT NULL,
    mime_type           VARCHAR(150) DEFAULT 'application/octet-stream',
    file_size           BIGINT NOT NULL DEFAULT 0,
    file_checksum       CHAR(64) DEFAULT NULL,
    source_path         VARCHAR(500) DEFAULT NULL,
    storage_mode        ENUM('database','hybrid','filesystem') NOT NULL DEFAULT 'hybrid',
    file_blob           LONGBLOB NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_uploaded_file_entity (entity_type, entity_id),
    INDEX idx_uploaded_file_entity (entity_type, entity_id),
    INDEX idx_uploaded_file_checksum (file_checksum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS uploaded_file_chunks (
    id                  BIGINT AUTO_INCREMENT PRIMARY KEY,
    uploaded_file_id    BIGINT NOT NULL,
    chunk_index         INT NOT NULL,
    chunk_data          LONGBLOB NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_uploaded_file_chunk (uploaded_file_id, chunk_index),
    INDEX idx_uploaded_file_chunk_lookup (uploaded_file_id, chunk_index),
    CONSTRAINT fk_uploaded_file_chunk_file FOREIGN KEY (uploaded_file_id)
        REFERENCES uploaded_files(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE uploaded_files
    MODIFY COLUMN file_blob LONGBLOB NULL;

DELIMITER $$

DROP TRIGGER IF EXISTS trg_libraries_delete_uploaded_file$$
CREATE TRIGGER trg_libraries_delete_uploaded_file
AFTER DELETE ON libraries
FOR EACH ROW
BEGIN
    DELETE FROM uploaded_files
    WHERE entity_type = 'libraries' AND entity_id = OLD.id;
END$$

DROP TRIGGER IF EXISTS trg_notes_delete_uploaded_file$$
CREATE TRIGGER trg_notes_delete_uploaded_file
AFTER DELETE ON notes
FOR EACH ROW
BEGIN
    DELETE FROM uploaded_files
    WHERE entity_type = 'notes' AND entity_id = OLD.id;
END$$

DROP TRIGGER IF EXISTS trg_videos_delete_uploaded_file$$
CREATE TRIGGER trg_videos_delete_uploaded_file
AFTER DELETE ON problem_solving_videos
FOR EACH ROW
BEGIN
    DELETE FROM uploaded_files
    WHERE entity_type = 'problem_solving_videos' AND entity_id = OLD.id;
END$$

DELIMITER ;
