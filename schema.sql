-- ============================================================
--  Wedding Database Schema
--  Database: wedding
-- ============================================================

CREATE DATABASE IF NOT EXISTS wedding CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wedding;

-- ------------------------------------------------------------
--  Admin Users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(60)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
--  Guests
--  Replaces the old `wedding invited` table.
--  To migrate existing data:
--    INSERT INTO guests (name, guest_limit, food_choice, party_names, party_foods)
--    SELECT Names, guest_limit, food_choice, plus_one_name, plus_one_food
--    FROM `wedding invited`;
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS guests (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(100) NOT NULL UNIQUE,
    guest_limit         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    food_choice         VARCHAR(50)  DEFAULT NULL,
    party_names         TEXT         DEFAULT NULL,
    party_foods         TEXT         DEFAULT NULL,
    rsvp_submitted_at   DATETIME     DEFAULT NULL,
    created_at          DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
--  Events
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    event_datetime DATETIME     NOT NULL,
    venue          VARCHAR(150),
    location       VARCHAR(200)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed the wedding reception event
INSERT IGNORE INTO events (id, name, event_datetime, venue, location)
VALUES (1, 'Wedding Reception', '2026-09-27 18:00:00', 'Cambridge Mill', 'Cambridge, Ontario');

-- ------------------------------------------------------------
--  Event Attendance
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS event_attendance (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    guest_id   INT NOT NULL,
    event_id   INT NOT NULL,
    attending  TINYINT(1) DEFAULT 1,
    UNIQUE KEY unique_attendance (guest_id, event_id),
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
