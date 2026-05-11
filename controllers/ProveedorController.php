<?php
ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../models/Proveedor.php';

define("BASE_URL", "/chocoTumac/");
/* 
 * Controlador ProveedorController
 * Maneja las acciones relacionadas con los proveedores, como crear, editar, actualizar y eliminar.
 * Verifica los permisos del usuario para cada acción y redirige con mensajes de éxito o error según corresponda. 
 */
class ProveedorController {

    private $model;
    // Constructor que inicializa el modelo de proveedor
    public function __construct() {
        $this->model = new Proveedor();
    }
    /*
    * Método para verificar si el usuario tiene permisos para gestionar proveedores (crear o editar).
    * Retorna true si el usuario tiene rol de administrador o editor, false en caso contrario.
    */
    private function puedeGestionar() {
        return isset($_SESSION['user']) && in_array($_SESSION['user']['rol_id'], [1, 3]);
    }
    /* Método para verificar si el usuario es administrador. Retorna true si el usuario tiene rol de administrador, false en caso contrario.    
    */ 
    private function esAdmin() {
        return isset($_SESSION['user']) && $_SESSION['user']['rol_id'] == 1;
    }
    /* Método para verificar el token CSRF. Si el token no es válido, redirige a la página de proveedores con un mensaje de error. */
    private function verificarCSRF() {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode("Petición no válida. Recarga la página."));
            exit();
        }
    }
    /* Método para validar los campos del formulario de proveedor. Retorna true si los datos son válidos o un mensaje de error si no lo son. */
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
    
    
    public function ejecutar() { // Método principal que maneja las acciones según la URL y los permisos del usuario
        // Obtener la acción a realizar desde la URL
        $accion = $_GET['action'] ?? '';

        if (!isset($_SESSION['user'])) {
            header("Location: " . BASE_URL . "index.php?view=login&error=" . urlencode("Tu sesión ha expirado."));
            exit();
        }

        switch ($accion) {

            /* 
            *Acción para crear un nuevo proveedor. Verifica los permisos, valida el token CSRF y llama al método crear del modelo. Redirige con un mensaje de éxito o error según corresponda. 
            */
            case 'crear':
                if (!$this->puedeGestionar()) {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode("No tienes permisos para crear proveedores."));
                    exit();
                }
                // Verificar token CSRF antes de procesar el formulario
                $this->verificarCSRF();
                $res = $this->model->crear($this->camposDesdePost());
                if ($res === true) {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&msg=creado");
                } else {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode($res));
                }
                break;

            /* 
            *Acción para mostrar el formulario de edición de un proveedor. Verifica los permisos y obtiene los datos del proveedor por su ID. Si el proveedor no existe, redirige con un mensaje de error. 
            */
            case 'editar':
                if (!$this->puedeGestionar()) {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode("No tienes permisos para editar proveedores."));
                    exit();
                }
                // Obtener datos del proveedor a editar
                $proveedor = $this->model->obtenerPorId($_GET['id'] ?? 0);
                if (!$proveedor) {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode("Proveedor no encontrado."));
                    exit();
                }
                require __DIR__ . '/../views/editar_proveedor.php';
                break;

            /* 
            *Acción para actualizar un proveedor existente. Verifica los permisos, valida el token CSRF y llama al método actualizar del modelo. Redirige con un mensaje de éxito o error según corresponda. 
            */ 
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

            /* 
            *Acción para eliminar un proveedor. Verifica que el usuario sea administrador, obtiene los datos del proveedor por su ID y llama al método eliminar del modelo. Redirige con un mensaje de éxito o error según corresponda. 
            */
            case 'eliminar':
                if (!$this->esAdmin()) {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode("Solo el administrador puede eliminar proveedores."));
                    exit();
                }
                // Obtener datos del proveedor a eliminar
                $proveedor = $this->model->obtenerPorId($_GET['id'] ?? 0);
                if (!$proveedor) {
                    header("Location: " . BASE_URL . "index.php?view=proveedores&error=" . urlencode("Proveedor no encontrado."));
                    exit();
                }
                // Eliminar proveedor
                $this->model->eliminar($_GET['id']);
                header("Location: " . BASE_URL . "index.php?view=proveedores&msg=eliminado");
                break;
        }
    }
}
// Crear una instancia del controlador y ejecutar la acción correspondiente
(new ProveedorController())->ejecutar();
