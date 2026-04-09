-- =============================================
-- ADD ADMIN ROLE SUPPORT
-- =============================================

USE brain_rent;

ALTER TABLE users
    MODIFY user_type ENUM
('client','expert','both','admin') NOT NULL DEFAULT 'client';
