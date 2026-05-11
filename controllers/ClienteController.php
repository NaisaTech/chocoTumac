<?php 
ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../models/Cliente.php';

define("BASE_URL", "/chocoTumac/");

/* 
 * Controlador ClienteController
 * Maneja las acciones relacionadas con los clientes, como crear, editar, actualizar y eliminar.
 * Verifica los permisos del usuario para cada acción y redirige con mensajes de éxito o error según corresponda. 
 */
class ClienteController {

    // Propiedad para almacenar el modelo de cliente
    private $model;
    // Constructor que inicializa el modelo de cliente
    public function __construct() {
        $this->model = new Cliente();
    }
    /* Método para verificar si el usuario tiene permisos para gestionar clientes (crear o editar). Retorna true si el usuario tiene rol de administrador o editor, false en caso contrario. */
    private function puedeGestionar() {
        return isset($_SESSION['user']) && in_array($_SESSION['user']['rol_id'], [1, 3]);
    }
    /* Método para verificar si el usuario es administrador. Retorna true si el usuario tiene rol de administrador, false en caso contrario. */
    private function esAdmin() {
        return isset($_SESSION['user']) && $_SESSION['user']['rol_id'] == 1;
    }
    /* Método para verificar el token CSRF. Si el token no es válido, redirige a la página de clientes con un mensaje de error. */
    private function verificarCSRF() {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            header("Location: " . BASE_URL . "index.php?view=clientes&error=" . urlencode("Petición no válida. Recarga la página."));
            exit();
        }
    }

    /* Método para obtener los campos del formulario desde $_POST. Retorna un array con los datos del cliente. */
    private function camposDesdePost() {
        return [
            'nombre'      => $_POST['nombre']      ?? '',
            'tipo_doc'    => $_POST['tipo_doc']    ?? 'CC',
            'num_doc'     => $_POST['num_doc']     ?? '',
            'digito_ver'  => $_POST['digito_ver']  ?? '',
            'telefono'    => $_POST['telefono']    ?? '',
            'email'       => $_POST['email']       ?? '',
            'direccion'   => $_POST['direccion']   ?? '',
            'ciudad'      => $_POST['ciudad']      ?? '',
            'departamento'=> $_POST['departamento']?? '',
        ];
    }

    /* Método privado para validar los campos del cliente antes de crear o actualizar. Retorna true si los datos son válidos o un mensaje de error si no lo son. */
    public function ejecutar() {
        // Obtener la acción a realizar desde la URL
        $accion = $_GET['action'] ?? '';

       // Verificar que el usuario esté autenticado antes de permitir cualquier acción 
        if (!isset($_SESSION['user'])) {
            header("Location: " . BASE_URL . "index.php?view=login&error=" . urlencode("Tu sesión ha expirado."));
            exit();
        }

        // Manejar las acciones según la URL y los permisos del usuario
        switch ($accion) {

            case 'crear':
                if (!$this->puedeGestionar()) {
                    header("Location: " . BASE_URL . "index.php?view=clientes&error=" . urlencode("No tienes permisos para crear clientes."));
                    exit();
                }
                $this->verificarCSRF();
                $res = $this->model->crear($this->camposDesdePost());
                if ($res === true) {
                    header("Location: " . BASE_URL . "index.php?view=clientes&msg=creado");
                } else {
                    header("Location: " . BASE_URL . "index.php?view=clientes&error=" . urlencode($res));
                }
                break;
            /* Acción para editar un cliente existente. Verifica los permisos, obtiene el cliente por ID y carga la vista de edición. Si el cliente no se encuentra, redirige con un mensaje de error. */
            case 'editar':
                if (!$this->puedeGestionar()) {
                    header("Location: " . BASE_URL . "index.php?view=clientes&error=" . urlencode("No tienes permisos para editar clientes."));
                    exit();
                }
                $cliente = $this->model->obtenerPorId($_GET['id'] ?? 0);
                if (!$cliente) {
                    header("Location: " . BASE_URL . "index.php?view=clientes&error=" . urlencode("Cliente no encontrado."));
                    exit();
                }
                require __DIR__ . '/../views/editar_cliente.php';
                break;
            /* Acción para actualizar un cliente existente. Verifica los permisos, valida el token CSRF, llama al método actualizar del modelo y redirige con un mensaje de éxito o error según corresponda. */
            case 'actualizar':
                if (!$this->puedeGestionar()) {
                    header("Location: " . BASE_URL . "index.php?view=clientes&error=" . urlencode("No tienes permisos para editar clientes."));
                    exit();
                }
                $this->verificarCSRF();
                $res = $this->model->actualizar($_POST['id'] ?? 0, $this->camposDesdePost());
                if ($res === true) {
                    header("Location: " . BASE_URL . "index.php?view=clientes&msg=actualizado");
                } else {
                    header("Location: " . BASE_URL . "index.php?view=clientes&error=" . urlencode($res));
                }
                break;
            /* Acción para eliminar un cliente. Verifica que el usuario sea administrador, obtiene el cliente por ID y llama al método eliminar del modelo. Redirige con un mensaje de éxito o error según corresponda. */
            case 'eliminar':
                if (!$this->esAdmin()) {
                    header("Location: " . BASE_URL . "index.php?view=clientes&error=" . urlencode("Solo el administrador puede eliminar clientes."));
                    exit();
                }
                $cliente = $this->model->obtenerPorId($_GET['id'] ?? 0);
                if (!$cliente) {
                    header("Location: " . BASE_URL . "index.php?view=clientes&error=" . urlencode("Cliente no encontrado."));
                    exit();
                }
                $this->model->eliminar($_GET['id']);
                header("Location: " . BASE_URL . "index.php?view=clientes&msg=eliminado");
                break;
        }
    }
}

(new ClienteController())->ejecutar();
