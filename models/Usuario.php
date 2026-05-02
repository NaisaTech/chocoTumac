<?php
require_once __DIR__ . '/../config/database.php';

class Usuario {

    private $conn;

    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    private function passwordSegura($password) {
        return preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/', $password);
    }

    public function crear($nombre, $email, $password, $rol_id, $telefono = null) {
        $nombre = trim($nombre);
        $email  = strtolower(trim($email));

        if (empty($nombre)) return "El nombre es obligatorio.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return "El formato del correo no es válido.";
        if (!$this->passwordSegura($password)) {
            return "La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número.";
        }
        if (!in_array((int)$rol_id, [1, 2, 3])) return "Rol no válido.";
        if (!empty($telefono) && !preg_match('/^[0-9+\s\-]{7,20}$/', trim($telefono))) {
            return "El formato del teléfono no es válido.";
        }

        $check = $this->conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) return "Ya existe un usuario registrado con ese correo.";

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->conn->prepare(
            "INSERT INTO usuarios (nombre, email, password, telefono, rol_id) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$nombre, $email, $hash, trim($telefono) ?: null, (int)$rol_id]);
        return true;
    }

    public function obtener() {
        return $this->conn->query(
            "SELECT u.*, r.nombre AS rol
             FROM usuarios u
             JOIN roles r ON u.rol_id = r.id
             ORDER BY u.nombre ASC"
        );
    }

    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizar($id, $nombre, $email, $rol_id, $telefono = null) {
        $nombre = trim($nombre);
        $email  = strtolower(trim($email));

        if (empty($nombre)) return "El nombre es obligatorio.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return "El formato del correo no es válido.";
        if (!empty($telefono) && !preg_match('/^[0-9+\s\-]{7,20}$/', trim($telefono))) {
            return "El formato del teléfono no es válido.";
        }

        $check = $this->conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $check->execute([$email, (int)$id]);
        if ($check->fetch()) return "Ese correo ya está en uso por otro usuario.";

        $stmt = $this->conn->prepare(
            "UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, rol_id = ? WHERE id = ?"
        );
        $stmt->execute([$nombre, $email, trim($telefono) ?: null, (int)$rol_id, (int)$id]);
        return true;
    }

    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Registrar último acceso
            $upd = $this->conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
            $upd->execute([$user['id']]);

            unset($user['password']);
            return $user;
        }
        return false;
    }

    public function actualizarPassword($id, $hash) {
        $stmt = $this->conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        return $stmt->execute([$hash, (int)$id]);
    }

    public function obtenerConPassword($id) {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
