<?php
ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../models/Proveedor.php';
require_once __DIR__ . '/Redirectable.php';

define("BASE_URL", "/chocoTumac/");
/* 
 * Controlador ProveedorController
 * Maneja las acciones relacionadas con los proveedores, como crear, editar, actualizar y eliminar.
 * Verifica los permisos del usuario para cada acción y redirige con mensajes de éxito o error según corresponda. 
 */
class ProveedorController {
    use Redirectable;


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
            $this->redirectError('proveedores', 'Petición no válida. Recarga la página.');
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
            $this->redirectError('login', 'Tu sesión ha expirado.');
        }

        switch ($accion) {

            /* 
            *Acción para crear un nuevo proveedor. Verifica los permisos, valida el token CSRF y llama al método crear del modelo. Redirige con un mensaje de éxito o error según corresponda. 
            */
            case 'crear':
                if (!$this->puedeGestionar()) {
                    $this->redirectError('proveedores', 'No tienes permisos para crear proveedores.');
                }
                // Verificar token CSRF antes de procesar el formulario
                $this->verificarCSRF();
                $res = $this->model->crear($this->camposDesdePost());
                if ($res === true) {
                    $this->redirectOk('proveedores', 'creado');} else {
                    $this->redirectError('proveedores', $res);}
                break;

            /* 
            *Acción para mostrar el formulario de edición de un proveedor. Verifica los permisos y obtiene los datos del proveedor por su ID. Si el proveedor no existe, redirige con un mensaje de error. 
            */
            case 'editar':
                if (!$this->puedeGestionar()) {
                    $this->redirectError('proveedores', 'No tienes permisos para editar proveedores.');
                }
                // Obtener datos del proveedor a editar
                $proveedor = $this->model->obtenerPorId($_GET['id'] ?? 0);
                if (!$proveedor) {
                    $this->redirectError('proveedores', 'Proveedor no encontrado.');
                }
                require_once __DIR__ . '/../views/editar_proveedor.php';
                break;

            /* 
            *Acción para actualizar un proveedor existente. Verifica los permisos, valida el token CSRF y llama al método actualizar del modelo. Redirige con un mensaje de éxito o error según corresponda. 
            */ 
            case 'actualizar':
                if (!$this->puedeGestionar()) {
                    $this->redirectError('proveedores', 'No tienes permisos para editar proveedores.');
                }
                $this->verificarCSRF();
                $res = $this->model->actualizar($_POST['id'] ?? 0, $this->camposDesdePost());
                if ($res === true) {
                    $this->redirectOk('proveedores', 'actualizado');} else {
                    $this->redirectError('proveedores', $res);}
                break;

            /* 
            *Acción para eliminar un proveedor. Verifica que el usuario sea administrador, obtiene los datos del proveedor por su ID y llama al método eliminar del modelo. Redirige con un mensaje de éxito o error según corresponda. 
            */
            case 'eliminar':
                if (!$this->esAdmin()) {
                    $this->redirectError('proveedores', 'Solo el administrador puede eliminar proveedores.');
                }
                // Obtener datos del proveedor a eliminar
                $proveedor = $this->model->obtenerPorId($_GET['id'] ?? 0);
                if (!$proveedor) {
                    $this->redirectError('proveedores', 'Proveedor no encontrado.');
                }
                // Eliminar proveedor
                $this->model->eliminar($_GET['id']);
                $this->redirectOk('proveedores', 'eliminado');break;
                    default:
                $this->redirectError('proveedores', 'Acción no reconocida.');
                break;
}
    }
}
// Crear una instancia del controlador y ejecutar la acción correspondiente
(new ProveedorController())->ejecutar();