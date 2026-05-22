-- ============================================================
--  Teacher Bill Management System — Database Schema
--  Import this file via phpMyAdmin > Import
-- ============================================================

CREATE DATABASE IF NOT EXISTS teacher_bill_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE teacher_bill_system;

-- ============================================================
-- TABLE: users
-- Stores both HOD and Teachers
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(100)   NOT NULL,
    email            VARCHAR(100)   NOT NULL UNIQUE,
    password         VARCHAR(255)   NOT NULL,
    role             ENUM('hod', 'teacher') NOT NULL DEFAULT 'teacher',
    teacher_type     ENUM('visiting', 'guest', 'earn_and_learn') DEFAULT NULL,
    department       VARCHAR(100)   DEFAULT NULL,
    phone            VARCHAR(20)    DEFAULT NULL,
    rate_per_lecture DECIMAL(8,2)   DEFAULT 0.00,
    is_active        TINYINT(1)     NOT NULL DEFAULT 1,
    created_at       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: lectures
-- Each lecture session recorded by a teacher
-- ============================================================
CREATE TABLE IF NOT EXISTS lectures (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id     INT            NOT NULL,
    lecture_date   DATE           NOT NULL,
    subject        VARCHAR(150)   NOT NULL,
    lecture_count  INT            NOT NULL DEFAULT 1,
    notes          TEXT           DEFAULT NULL,
    created_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: bills
-- One bill per submission (per teacher per month)
-- ============================================================
CREATE TABLE IF NOT EXISTS bills (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id       INT            NOT NULL,
    generated_by     INT            DEFAULT NULL,  -- NULL = teacher, or HOD id if manual
    month_year       VARCHAR(20)    NOT NULL,       -- e.g. "March 2026"
    period_from      DATE           NOT NULL,
    period_to        DATE           NOT NULL,
    total_lectures   INT            NOT NULL DEFAULT 0,
    rate_per_lecture DECIMAL(8,2)   NOT NULL DEFAULT 0.00,
    total_amount     DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    status           ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    rejection_reason TEXT           DEFAULT NULL,
    submitted_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at      TIMESTAMP      NULL DEFAULT NULL,
    reviewed_by      INT            DEFAULT NULL,
    FOREIGN KEY (teacher_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: bill_lectures
-- Junction table linking a bill to its specific lecture entries
-- ============================================================
CREATE TABLE IF NOT EXISTS bill_lectures (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    bill_id    INT NOT NULL,
    lecture_id INT NOT NULL,
    FOREIGN KEY (bill_id)    REFERENCES bills(id)    ON DELETE CASCADE,
    FOREIGN KEY (lecture_id) REFERENCES lectures(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: activity_log
-- Audit trail for all important actions
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT            DEFAULT NULL,
    action      VARCHAR(200)   NOT NULL,
    description TEXT           DEFAULT NULL,
    ip_address  VARCHAR(45)    DEFAULT NULL,
    created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- INDEXES for performance
-- ============================================================
CREATE INDEX idx_lectures_teacher   ON lectures(teacher_id);
CREATE INDEX idx_lectures_date      ON lectures(lecture_date);
CREATE INDEX idx_bills_teacher      ON bills(teacher_id);
CREATE INDEX idx_bills_status       ON bills(status);
CREATE INDEX idx_bill_lectures_bill ON bill_lectures(bill_id);
CREATE INDEX idx_log_user           ON activity_log(user_id);

-- ============================================================
-- SEED DATA
-- Default HOD account + 2 sample teachers
-- HOD password    : hod@1234
-- Teacher passwords: teacher@1234
-- ============================================================

INSERT INTO users (name, email, password, role, department, phone, rate_per_lecture) VALUES
(
    'Dr. Rajesh Sharma',
    'hod@college.edu',
    '$2y$10$fmRh7sHI/S4RqnBvIUW4Wus9o1B6ZkoojoNNl9fiXb7lkuNBNh1LK',  -- hod@1234
    'hod',
    'Computer Science',
    '9876543210',
    0.00
),
(
    'Prof. Anjali Mehta',
    'anjali@college.edu',
    '$2y$10$D1hcjQ9Mob0RjDKLK6cR5.xmXNBHocqvlnBMHpz.RaKtlGJm6fdHa',  -- teacher@1234
    'teacher',
    'Computer Science',
    '9123456780',
    500.00
),
(
    'Prof. Ravi Kumar',
    'ravi@college.edu',
    '$2y$10$D1hcjQ9Mob0RjDKLK6cR5.xmXNBHocqvlnBMHpz.RaKtlGJm6fdHa',  -- teacher@1234
    'teacher',
    'Computer Science',
    '9234567801',
    450.00
);

-- Update teacher types after insert
UPDATE users SET teacher_type = 'visiting'     WHERE email = 'anjali@college.edu';
UPDATE users SET teacher_type = 'guest'        WHERE email = 'ravi@college.edu';

-- Sample lecture entries for Prof. Anjali (teacher id = 2)
INSERT INTO lectures (teacher_id, lecture_date, subject, lecture_count) VALUES
(2, '2026-03-03', 'Data Structures', 2),
(2, '2026-03-05', 'Data Structures', 2),
(2, '2026-03-10', 'Algorithms',      2),
(2, '2026-03-12', 'Algorithms',      2),
(2, '2026-03-17', 'Data Structures', 2),
(2, '2026-03-19', 'Algorithms',      2),
(2, '2026-03-24', 'Data Structures', 2),
(2, '2026-03-26', 'Algorithms',      2);

-- Sample bill for Prof. Anjali — submitted, pending
INSERT INTO bills (teacher_id, month_year, period_from, period_to, total_lectures, rate_per_lecture, total_amount, status, submitted_at) VALUES
(2, 'March 2026', '2026-03-01', '2026-03-31', 16, 500.00, 8000.00, 'pending', '2026-04-01 10:00:00');

-- Link bill to lectures
INSERT INTO bill_lectures (bill_id, lecture_id) VALUES
(1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8);

-- ============================================================
-- QUICK REFERENCE
-- ============================================================
-- HOD Login:
--   Email    : hod@college.edu
--   Password : hod@1234
--
-- Teacher Login (Anjali):
--   Email    : anjali@college.edu
--   Password : teacher@1234
--
-- Teacher Login (Ravi):
--   Email    : ravi@college.edu
--   Password : teacher@1234
-- ============================================================
