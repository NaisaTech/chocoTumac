<?php
if (!class_exists('Database')) {
    class Database {
        public function connect() {
            try {
                $pdo = new PDO(
                    "mysql:host={$this->host};dbname={$this->db};charset=utf8",
                    $this->user,
                    $this->pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
                return $pdo;
            } catch (PDOException $e) {
                error_log("DB Error: " . $e->getMessage());
                die("Error de conexión a la base de datos. Intenta más tarde.");
            }
        }
    }
}