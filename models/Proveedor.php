<?php
require_once __DIR__ . '/../config/database.php';

class Proveedor {

    private $conn;

    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    private function existeDoc($tipo_doc, $num_doc, $excluir_id = null) {
        if ($excluir_id) {
            $stmt = $this->conn->prepare(
                "SELECT id FROM proveedores WHERE tipo_doc = ? AND num_doc = ? AND id != ?"
            );
            $stmt->execute([$tipo_doc, $num_doc, (int)$excluir_id]);
        } else {
            $stmt = $this->conn->prepare(
                "SELECT id FROM proveedores WHERE tipo_doc = ? AND num_doc = ?"
            );
            $stmt->execute([$tipo_doc, $num_doc]);
        }
        return $stmt->fetch();
    }

    private function validarCampos($data) {
        $tipos_doc_validos       = ['NIT', 'CC', 'CE', 'Pasaporte'];
        $tipos_proveedor_validos = ['Agricultor', 'Intermediario', 'Cooperativa', 'Empresa'];

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

    public function crear($data) {
        $data = array_map('trim', $data);

        $val = $this->validarCampos($data);
        if ($val !== true) return $val;

        if ($this->existeDoc($data['tipo_doc'], $data['num_doc'])) {
            return "Ya existe un proveedor con ese tipo y número de documento.";
        }

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

    public function obtener() {
        return $this->conn->query("SELECT * FROM proveedores ORDER BY nombre ASC");
    }

    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM proveedores WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizar($id, $data) {
        $data = array_map('trim', $data);

        $val = $this->validarCampos($data);
        if ($val !== true) return $val;

        if ($this->existeDoc($data['tipo_doc'], $data['num_doc'], $id)) {
            return "Ya existe otro proveedor con ese tipo y número de documento.";
        }

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

    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM proveedores WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }
}
