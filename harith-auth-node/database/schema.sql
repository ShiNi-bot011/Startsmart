-- StartSmart AI - Sprint 1 database (US001 Register, US002 Log in)
-- Run once:  mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS startsmart
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE startsmart;

-- US001: one row per student. Email must be unique. Only a bcrypt hash of the
-- password is stored, never the password itself ("Create account with hashed password").
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  xp            INT UNSIGNED NOT NULL DEFAULT 0,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- US002: one row per active login ("Session token" / "Session ended").
-- We store a SHA-256 hash of the token so a leaked table cannot be used to log in.
CREATE TABLE IF NOT EXISTS sessions (
  token_hash CHAR(64) PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sessions_user (user_id),
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Minimal table so US002 can "Get subjects of this student only".
-- US003 (Manage subjects, Lai Shi Ni) owns this table and can extend it.
CREATE TABLE IF NOT EXISTS subjects (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  name       VARCHAR(100) NOT NULL,
  position   INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_subject_per_student (user_id, name),
  CONSTRAINT fk_subjects_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
