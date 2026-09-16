CREATE DATABASE IF NOT EXISTS api_archivos;

CREATE USER IF NOT EXISTS 'api_archivos_user'@'localhost' IDENTIFIED BY '12345';

GRANT ALL PRIVILEGES oN api_archivos.* TO 'api_archivos_user'@'localhost';
FLUSH PRIVILEGES;
EXIT

mysql -u api_archivos_user -p
12345

USE api_archivos;

CREATE TABLE IF NOT EXISTS archivos (
    id_archivo INT AUTO_INCREMENT PRIMARY KEY,
    identificador VARCHAR(64) NOT NULL UNIQUE,
    nombre_original VARCHAR(255) NOT NULL,
    nombre_interno VARCHAR(255) NOT NULL UNIQUE,
    tamano BIGINT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    descargas_realizadas INT NOT NULL DEFAULT 0,
    max_downloads INT NOT NULL DEFAULT 1,
    token_admin_hash VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS rate_limits (
    ip VARCHAR(45) PRIMARY KEY,
    ventana_inicio DATETIME NOT NULL,
    solicitudes INT NOT NULL DEFAULT 0
);