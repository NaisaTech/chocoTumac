<?php
/**
 * Configuración de conexión a la base de datos – ChocoTumac.
 *
 * Ajusta los valores de $host, $db, $user y $pass
 * según tu entorno WampServer local.
 */
if (!class_exists('Database')) {
    class Database {

        private string $host = 'localhost';
        private string $db   = 'chocolatetumaco';
        private string $user = 'root';
        private string $pass = '';          // WampServer por defecto no tiene contraseña

        public function connect(): PDO {
            try {
                return new PDO(
                    "mysql:host={$this->host};dbname={$this->db};charset=utf8",
                    $this->user,
                    $this->pass,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                error_log("DB Error: " . $e->getMessage());
                die("Error de conexión a la base de datos. Intenta más tarde.");
            }
        }
    }
}