<?php
/**
 * Clase Database
 * 
 * Se encarga de establecer la conexión con la base de datos
 * utilizando PDO, aplicando buenas prácticas de seguridad.
 */ 
class Database {

    /** @var string Host del servidor */
    private $host = "localhost";
    /** @var string Host del servidor */
    private $db   = "chocolatetumaco";
    /** @var string Usuario de la base de datos */
    private $user = "root";
    /** @var string Contraseña de la base de datos */
    private $pass = "";


    /**
     * Método que crea y retorna una conexión PDO
     * 
     * @return PDO
     */
    public function connect() {
        try {
            $pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db};charset=utf8",
                $this->user,
                $this->pass,
                [
                    //Modo error: lanza excepciones
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    //Devuelve resultados como arrays asociativos
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    //Desactiva emulación (más seguro)
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            /* No exponer detalles del error al usuario*/
            //Registrar error internamente (no mostrar al usuario)
            error_log("DB Error: " . $e->getMessage());
            //Mensaje genérico para el usuario
            die("Error de conexión a la base de datos. Intenta más tarde.");
        }
    }
}
