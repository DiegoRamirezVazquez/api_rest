
CREATE DATABASE IF NOT EXISTS password_security;
USE password_security;


CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,

    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',

    failed_login_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- ============================================
-- TABLA: password_history
-- ============================================

CREATE TABLE password_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_password_history_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


-- ============================================
-- TABLA: password_policy
-- ============================================

CREATE TABLE password_policy (
    id INT AUTO_INCREMENT PRIMARY KEY,

    min_length INT NOT NULL DEFAULT 12,
    max_length INT NOT NULL DEFAULT 64,

    require_uppercase BOOLEAN NOT NULL DEFAULT TRUE,
    require_lowercase BOOLEAN NOT NULL DEFAULT TRUE,
    require_numbers BOOLEAN NOT NULL DEFAULT TRUE,
    require_symbols BOOLEAN NOT NULL DEFAULT TRUE,

    disallow_common_passwords BOOLEAN NOT NULL DEFAULT TRUE,
    disallow_username_in_password BOOLEAN NOT NULL DEFAULT TRUE,
    disallow_sequential_characters BOOLEAN NOT NULL DEFAULT TRUE,

    password_history_limit INT NOT NULL DEFAULT 5,
    expiration_days INT NOT NULL DEFAULT 90
);


-- ============================================
-- POLÍTICA INICIAL
-- ============================================

INSERT INTO password_policy (
    min_length,
    max_length,
    require_uppercase,
    require_lowercase,
    require_numbers,
    require_symbols,
    disallow_common_passwords,
    disallow_username_in_password,
    disallow_sequential_characters,
    password_history_limit,
    expiration_days
)
VALUES (
    12,
    64,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    5,
    90
);