<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Modelo Cliente
 * Representa a un cliente con sus atributos y métodos para CRUD.
 * Atributos:
 * - id: Identificador único del cliente.
 * - nombre: Nombre del cliente (obligatorio, mínimo 2 caracteres).
 * - tipo_doc: Tipo de documento (NIT, CC, CE, Pasaporte).
 * - num_doc: Número de documento (obligatorio, solo números y guiones).
 * - digito_ver: Dígito de verificación (solo para NIT).
 * - telefono: Teléfono de contacto (opcional, formato válido).
 * - email: Correo electrónico (opcional, formato válido).
 * - direccion: Dirección del cliente.
 * - ciudad: Ciudad del cliente.
 * - departamento: Departamento del cliente.
 */
class Cliente {
    /** Constructor: Establece la conexión a la base de datos. */
    private $conn;

    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    // ── Validaciones ───────────────────────────────────────────
    /* 
    *Método privado para verificar si ya existe un cliente con el mismo tipo y número de documento. Si se proporciona un ID para excluir, se omite ese registro en la verificación (útil para actualizaciones). 
    *Retorna el registro encontrado o false si no existe. 
    */
    private function existeDoc($tipo_doc, $num_doc, $excluir_id = null) {
        if ($excluir_id) {
            $stmt = $this->conn->prepare(
                "SELECT id FROM clientes WHERE tipo_doc = ? AND num_doc = ? AND id != ?"
            );
            $stmt->execute([$tipo_doc, $num_doc, (int)$excluir_id]);
        } else {
            $stmt = $this->conn->prepare(
                "SELECT id FROM clientes WHERE tipo_doc = ? AND num_doc = ?"
            );
            $stmt->execute([$tipo_doc, $num_doc]);
        }
        return $stmt->fetch();
    }

    /* 
    * Método privado para validar los campos del cliente antes de crear o actualizar. Retorna true si los datos son válidos o un mensaje de error si no lo son. 
    */
    private function validarCampos($data) {
        $tipos_validos = ['NIT', 'CC', 'CE', 'Pasaporte'];

        if (empty(trim($data['nombre']))) {
            return "El nombre del cliente es obligatorio.";
        }
        if (strlen(trim($data['nombre'])) < 2) {
            return "El nombre debe tener al menos 2 caracteres.";
        }
        if (!in_array($data['tipo_doc'], $tipos_validos)) {
            return "Tipo de documento no válido.";
        }
        if (empty(trim($data['num_doc']))) {
            return "El número de documento es obligatorio.";
        }
        if (!preg_match('/^[0-9\-]+$/', trim($data['num_doc']))) {
            return "El número de documento solo puede contener números y guiones.";
        }
        if ($data['tipo_doc'] === 'NIT' && !empty($data['digito_ver'])) {
            if (!preg_match('/^[0-9]$/', $data['digito_ver'])) {
                return "El dígito de verificación debe ser un número del 0 al 9.";
            }
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return "El formato del correo electrónico no es válido.";
        }
        if (!empty($data['telefono']) && !preg_match('/^[0-9+\s\-]{7,20}$/', $data['telefono'])) {
            return "El formato del teléfono no es válido.";
        }
        return true;
    }

    // ── CRUD ───────────────────────────────────────────────────
    /* 
    * Método para crear un nuevo cliente. Valida los datos y verifica que no exista otro cliente con el mismo tipo y número de documento antes de insertar en la base de datos. Retorna true si se creó correctamente o un mensaje de error si no. 
    */
    public function crear($data) {
        $data = array_map('trim', $data);

        $val = $this->validarCampos($data);
        if ($val !== true) return $val;

        if ($this->existeDoc($data['tipo_doc'], $data['num_doc'])) {
            return "Ya existe un cliente con ese tipo y número de documento.";
        }

        $stmt = $this->conn->prepare("
            INSERT INTO clientes
                (nombre, tipo_doc, num_doc, digito_ver, telefono, email, direccion, ciudad, departamento)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['nombre'],
            $data['tipo_doc'],
            $data['num_doc'],
            $data['tipo_doc'] === 'NIT' ? ($data['digito_ver'] ?: null) : null,
            $data['telefono'] ?: null,
            $data['email']    ?: null,
            $data['direccion'] ?: null,
            $data['ciudad']   ?: null,
            $data['departamento'] ?: null,
        ]);
        return true;
    }

    /* Método para obtener todos los clientes ordenados por nombre. Retorna un array de clientes. */
    public function obtener() {
        return $this->conn->query("SELECT * FROM clientes ORDER BY nombre ASC");
    }

    /* Método para obtener un cliente por su ID. Retorna un array asociativo con los datos del cliente o false si no se encuentra. */
    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* Método para actualizar un cliente existente. Valida los datos y verifica que no exista otro cliente con el mismo tipo y número de documento (excepto el actual). Retorna true si se actualizó correctamente o un mensaje de error si no. */
    public function actualizar($id, $data) {
        $data = array_map('trim', $data);

        $val = $this->validarCampos($data);
        if ($val !== true) return $val;

        if ($this->existeDoc($data['tipo_doc'], $data['num_doc'], $id)) {
            return "Ya existe otro cliente con ese tipo y número de documento.";
        }

        $stmt = $this->conn->prepare("
            UPDATE clientes SET
                nombre = ?, tipo_doc = ?, num_doc = ?, digito_ver = ?,
                telefono = ?, email = ?, direccion = ?, ciudad = ?, departamento = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['nombre'],
            $data['tipo_doc'],
            $data['num_doc'],
            $data['tipo_doc'] === 'NIT' ? ($data['digito_ver'] ?: null) : null,
            $data['telefono'] ?: null,
            $data['email']    ?: null,
            $data['direccion'] ?: null,
            $data['ciudad']   ?: null,
            $data['departamento'] ?: null,
            (int)$id,
        ]);
        return true;
    }

    /* Método para eliminar un cliente. Retorna true si se eliminó correctamente o false si no. */
    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM clientes WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }
}
