-- Metropolitan College — Graduation Ceremony Attendance
-- Run this once to set up the database.
--
-- On shared/cPanel hosting where the database is already created for you,
-- skip the CREATE DATABASE / USE lines and just run the CREATE TABLE + INSERT
-- statements against your existing database.

CREATE DATABASE IF NOT EXISTS metropolitan_attendance
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE metropolitan_attendance;

CREATE TABLE IF NOT EXISTS students (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    registration_number  VARCHAR(50)  NOT NULL,
    full_name            VARCHAR(150) NOT NULL,
    course               VARCHAR(150) DEFAULT NULL,
    faculty              VARCHAR(150) DEFAULT NULL,
    batch                VARCHAR(50)  DEFAULT NULL,
    attendance_status    ENUM('pending', 'present') NOT NULL DEFAULT 'pending',
    attendance_time      DATETIME DEFAULT NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_registration_number (registration_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample rows for testing the student flow. Safe to re-run (INSERT IGNORE).
INSERT IGNORE INTO students (registration_number, full_name, course, faculty, batch) VALUES
('MC/2022/0001', 'Amara Perera',   'BSc (Hons) Information Technology', 'Faculty of Computing', '2022'),
('MC/2022/0002', 'Nadeesha Silva', 'BSc (Hons) Business Management',    'Faculty of Business',  '2022'),
('MC/2022/0003', 'Kasun Fernando', 'BEng (Hons) Software Engineering',  'Faculty of Computing', '2022');
