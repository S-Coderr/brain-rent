-- =============================================
-- ADD NEW FEATURES: Libraries, Notes, Problem Solving Platform
-- Run this after brain_rent_mysql.sql
-- =============================================

USE brain_rent;

-- =============================================
-- TABLE: libraries (E-books)
-- =============================================
CREATE TABLE IF NOT EXISTS libraries (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255)   NOT NULL,
    description     TEXT,
    author          VARCHAR(150),
    category        VARCHAR(100),
    file_path       VARCHAR(500)   NOT NULL,
    file_size       INT,           -- in bytes
    file_type       VARCHAR(50),   -- pdf, epub, mobi, etc
    cover_image     VARCHAR(500),
    uploaded_by     INT            NOT NULL,
    downloads       INT            DEFAULT 0,
    views           INT            DEFAULT 0,
    is_active       TINYINT(1)     DEFAULT 1,
    created_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_library_user FOREIGN KEY (uploaded_by)
        REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_library_category (category),
    INDEX idx_library_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE: notes
-- =============================================
CREATE TABLE IF NOT EXISTS notes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255)   NOT NULL,
    description     TEXT,
    subject         VARCHAR(150),
    category        VARCHAR(100),
    file_path       VARCHAR(500)   NOT NULL,
    file_size       INT,           -- in bytes
    file_type       VARCHAR(50),   -- pdf, docx, txt, etc
    thumbnail       VARCHAR(500),
    uploaded_by     INT            NOT NULL,
    downloads       INT            DEFAULT 0,
    views           INT            DEFAULT 0,
    is_active       TINYINT(1)     DEFAULT 1,
    created_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_notes_user FOREIGN KEY (uploaded_by)
        REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notes_subject (subject),
    INDEX idx_notes_category (category),
    INDEX idx_notes_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE: problem_solving_videos
-- =============================================
CREATE TABLE IF NOT EXISTS problem_solving_videos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255)   NOT NULL,
    description     TEXT,
    problem_type    VARCHAR(100),  -- coding, math, science, etc
    difficulty      ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    video_path      VARCHAR(500)   NOT NULL,
    video_size      BIGINT,        -- in bytes
    video_duration  INT,           -- in seconds
    thumbnail       VARCHAR(500),
    uploaded_by     INT            NOT NULL,
    views           INT            DEFAULT 0,
    likes           INT            DEFAULT 0,
    is_active       TINYINT(1)     DEFAULT 1,
    created_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_video_user FOREIGN KEY (uploaded_by)
        REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_video_type (problem_type),
    INDEX idx_video_difficulty (difficulty),
    INDEX idx_video_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE: video_comments
-- =============================================
CREATE TABLE IF NOT EXISTS video_comments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    video_id        INT            NOT NULL,
    user_id         INT            NOT NULL,
    comment         TEXT           NOT NULL,
    parent_id       INT            NULL,  -- for replies
    is_active       TINYINT(1)     DEFAULT 1,
    created_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_comment_video FOREIGN KEY (video_id)
        REFERENCES problem_solving_videos(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_parent FOREIGN KEY (parent_id)
        REFERENCES video_comments(id) ON DELETE CASCADE,
    INDEX idx_comment_video (video_id),
    INDEX idx_comment_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Create upload directories structure
-- Note: These need to be created manually with proper permissions
-- uploads/
--   ebooks/
--   notes/
--   videos/
--   thumbnails/
-- =============================================
