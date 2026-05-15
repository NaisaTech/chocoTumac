<?php
require_once __DIR__ . '/../config/database.php';
/**
 * Modelo Proveedor
 * Representa a un proveedor con sus atributos y métodos para CRUD.
 */
class Proveedor {
    /**
     * Atributos:
     * - id: Identificador único del proveedor.
     * - nombre: Nombre del proveedor (obligatorio, mínimo 2 caracteres).
     * - tipo_doc: Tipo de documento (NIT, CC, CE, Pasaporte).
     * - num_doc: Número de documento (obligatorio, solo números y guiones).
     * - digito_ver: Dígito de verificación (solo para NIT).
     * - tipo_proveedor: Tipo de proveedor (Agricultor, Intermediario, Cooperativa, Empresa).
     * - persona_contacto: Nombre de la persona de contacto.
     * - telefono: Teléfono de contacto (opcional, formato válido).
     * - email: Correo electrónico (opcional, formato válido).
     * - direccion: Dirección del proveedor.
     * - ciudad: Ciudad del proveedor.
     * - departamento: Departamento del proveedor.
     */
    private $conn;

    /** Constructor: Establece la conexión a la base de datos. */
    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    /** Método privado para verificar si ya existe un proveedor con el mismo tipo y número de documento. */
    private function existeDoc($tipo_doc, $num_doc, $excluir_id = null) {
        if ($excluir_id) {// Al actualizar, se excluye el proveedor actual de la verificación
            $stmt = $this->conn->prepare( 
            /*
            *Se prepara la consulta para verificar si existe otro proveedor con el mismo tipo y número de documento, excluyendo el ID del proveedor que se está actualizando.
            */

                "SELECT id FROM proveedores WHERE tipo_doc = ? AND num_doc = ? AND id != ?"
            );
            $stmt->execute([$tipo_doc, $num_doc, (int)$excluir_id]);
        } else { // Al crear, se verifica normalmente
            $stmt = $this->conn->prepare(
                "SELECT id FROM proveedores WHERE tipo_doc = ? AND num_doc = ?"
            );
            $stmt->execute([$tipo_doc, $num_doc]);
        }
        return $stmt->fetch();
    }
    /*
    * Método privado para validar los campos del proveedor antes de crear o actualizar. 
    */
    private function validarCampos($data) {
        $tipos_doc_validos       = ['NIT', 'CC', 'CE', 'Pasaporte'];
        $tipos_proveedor_validos = ['Agricultor', 'Intermediario', 'Cooperativa', 'Empresa'];
        // Validaciones básicas
        if (empty(trim($data['nombre']))) {
            return "El nombre del proveedor es obligatorio.";
        }
        if (strlen(trim($data['nombre'])) < 2) {
            return "El nombre debe tener al menos 2 caracteres.";
        }
        if (!in_array($data['tipo_doc'], $tipos_doc_validos)) {
            return "Tipo de documento no válido.";
        }
        if (empty(trim($data['num_doc']))) {
            return "El número de documento es obligatorio.";
        }
        if (!preg_match('/^[0-9\-]+$/', trim($data['num_doc']))) {
            return "El número de documento solo puede contener números y guiones.";
        }
        if (!in_array($data['tipo_proveedor'], $tipos_proveedor_validos)) {
            return "Tipo de proveedor no válido.";
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return "El formato del correo electrónico no es válido.";
        }
        if (!empty($data['telefono']) && !preg_match('/^[0-9+\s\-]{7,20}$/', $data['telefono'])) {
            return "El formato del teléfono no es válido.";
        }
        return true;
    }

    /* 
    *Método para crear un nuevo proveedor. Valida los datos y verifica que no exista otro proveedor con el mismo tipo y número de documento antes de insertar en la base de datos. 
    */
    public function crear($data) {
        $data = array_map('trim', $data);
    // Validar campos
        $val = $this->validarCampos($data);
        if ($val !== true) {
            return $val;
        }
        if ($this->existeDoc($data['tipo_doc'], $data['num_doc'])) {
            return "Ya existe un proveedor con ese tipo y número de documento.";
        }
        // Insertar nuevo proveedor
        $stmt = $this->conn->prepare("
            INSERT INTO proveedores
                (nombre, tipo_doc, num_doc, digito_ver, tipo_proveedor, persona_contacto,
                 telefono, email, direccion, ciudad, departamento)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['nombre'],
            $data['tipo_doc'],
            $data['num_doc'],
            $data['tipo_doc'] === 'NIT' ? ($data['digito_ver'] ?: null) : null,
            $data['tipo_proveedor'],
            $data['persona_contacto'] ?: null,
            $data['telefono']         ?: null,
            $data['email']            ?: null,
            $data['direccion']        ?: null,
            $data['ciudad']           ?: null,
            $data['departamento']     ?: null,
        ]);
        return true;
    }
    /*   
     * Método para obtener todos los proveedores. Devuelve un array de proveedores ordenados por nombre. 
     * */
    public function obtener() {
        return $this->conn->query("SELECT * FROM proveedores ORDER BY nombre ASC");
    }
    /* 
    * Método para obtener un proveedor por su ID. Devuelve un array asociativo con los datos del proveedor o false si no se encuentra. 
    */
    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM proveedores WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* 
    * Método para actualizar un proveedor existente. Valida los datos y verifica que no exista otro proveedor con el mismo tipo y número de documento antes de actualizar en la base de datos. 
    */
    public function actualizar($id, $data) {
        $data = array_map('trim', $data);

        $val = $this->validarCampos($data);
        if ($val !== true) {
            return $val;
        }
        if ($this->existeDoc($data['tipo_doc'], $data['num_doc'], $id)) {
            return "Ya existe otro proveedor con ese tipo y número de documento.";
        }
        // Actualizar proveedor
        $stmt = $this->conn->prepare("
            UPDATE proveedores SET
                nombre = ?, tipo_doc = ?, num_doc = ?, digito_ver = ?,
                tipo_proveedor = ?, persona_contacto = ?,
                telefono = ?, email = ?, direccion = ?, ciudad = ?, departamento = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['nombre'],
            $data['tipo_doc'],
            $data['num_doc'],
            $data['tipo_doc'] === 'NIT' ? ($data['digito_ver'] ?: null) : null,
            $data['tipo_proveedor'],
            $data['persona_contacto'] ?: null,
            $data['telefono']         ?: null,
            $data['email']            ?: null,
            $data['direccion']        ?: null,
            $data['ciudad']           ?: null,
            $data['departamento']     ?: null,
            (int)$id,
        ]);
        return true;
    }
    /* 
    * Método para eliminar un proveedor por su ID. Devuelve true si se eliminó correctamente o false si ocurrió un error. 
    */
    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM proveedores WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }
}