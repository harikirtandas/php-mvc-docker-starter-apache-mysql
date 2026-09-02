-- este script solo corre en el primer arranque del volumen mysql-data
-- (docker-entrypoint-initdb.d se ejecuta una unica vez, cuando /var/lib/mysql
-- esta vacio). para reaplicarlo hace falta recrear el volumen: make fresh

CREATE TABLE IF NOT EXISTS demo_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO demo_items (nombre) VALUES ('primer item de ejemplo');
INSERT INTO demo_items (nombre) VALUES ('segundo item de ejemplo');
