<?php
class Database {
    private $host = "localhost";
    private $db   = "chocolatetumaco";
    private $user = "root";
    private $pass = "";

    public function connect() {
        try {
            $pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db};charset=utf8",
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            // No exponer detalles del error al usuario
            error_log("DB Error: " . $e->getMessage());
            die("Error de conexión a la base de datos. Intenta más tarde.");
        }
    }
}
