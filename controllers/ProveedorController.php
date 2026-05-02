<?php
ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../models/Proveedor.php';

define("BASE_URL", "/choco_tumac/");

class ProveedorController {

    private $model;

    public function __construct() {
        $this->model = new Proveedor();
    }

    private function puedeGestionar() {
        return isset($_SESSION['user']) && in_array($_SESSION['user']['rol_id'], [1, 3]);
    }

    private function esAdmin() {
        return isset($_SESSION['user']) && $_SESSION['user']['rol_id'] == 1;
    }

    private function verificarCSRF() {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode("Petición no válida. Recarga la página."));
            exit();
        }
    }

    private function camposDesdePost() {
        return [
            'nombre'           => $_POST['nombre']           ?? '',
            'tipo_doc'         => $_POST['tipo_doc']         ?? 'CC',
            'num_doc'          => $_POST['num_doc']          ?? '',
            'digito_ver'       => $_POST['digito_ver']       ?? '',
            'tipo_proveedor'   => $_POST['tipo_proveedor']   ?? 'Agricultor',
            'persona_contacto' => $_POST['persona_contacto'] ?? '',
            'telefono'         => $_POST['telefono']         ?? '',
            'email'            => $_POST['email']            ?? '',
            'direccion'        => $_POST['direccion']        ?? '',
            'ciudad'           => $_POST['ciudad']           ?? '',
            'departamento'     => $_POST['departamento']     ?? '',
        ];
    }

    public function ejecutar() {

        $accion = $_GET['action'] ?? '';

        if (!isset($_SESSION['user'])) {
            header("Location: " . BASE_URL . "index.php?view=login&error=" . urlencode("Tu sesión ha expirado."));
            exit();
        }

        switch ($accion) {

            case 'crear':
                if (!$this->puedeGestionar()) {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode("No tienes permisos para crear proveedores."));
                    exit();
                }
                $this->verificarCSRF();
                $res = $this->model->crear($this->camposDesdePost());
                if ($res === true) {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&msg=creado");
                } else {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode($res));
                }
                break;

            case 'editar':
                if (!$this->puedeGestionar()) {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode("No tienes permisos para editar proveedores."));
                    exit();
                }
                $proveedor = $this->model->obtenerPorId($_GET['id'] ?? 0);
                if (!$proveedor) {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode("Proveedor no encontrado."));
                    exit();
                }
                require __DIR__ . '/../views/editar_proveedor.php';
                break;

            case 'actualizar':
                if (!$this->puedeGestionar()) {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode("No tienes permisos para editar proveedores."));
                    exit();
                }
                $this->verificarCSRF();
                $res = $this->model->actualizar($_POST['id'] ?? 0, $this->camposDesdePost());
                if ($res === true) {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&msg=actualizado");
                } else {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode($res));
                }
                break;

            case 'eliminar':
                if (!$this->esAdmin()) {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode("Solo el administrador puede eliminar proveedores."));
                    exit();
                }
                $proveedor = $this->model->obtenerPorId($_GET['id'] ?? 0);
                if (!$proveedor) {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode("Proveedor no encontrado."));
                    exit();
                }
                $this->model->eliminar($_GET['id']);
                header("Location: " . BASE_URL . "index.php?view=proveedores&msg=eliminado");
                break;
        }
    }
}

(new ProveedorController())->ejecutar();
