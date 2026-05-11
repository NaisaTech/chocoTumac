<?php
/**
 * Controlador ClienteController – ChocoTumac
 *
 * Maneja las acciones CRUD relacionadas con clientes:
 * crear, editar, actualizar y eliminar.
 * Verifica permisos por rol y protege cada mutación con token CSRF.
 *
 * @package ChocoTumac
 */

ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/Redirectable.php';

define('BASE_URL', '/chocoTumac/');

class ClienteController
{
    use Redirectable;

    /** @var Cliente Modelo de clientes */
    private Cliente $model;

    /** Inicializa el modelo de Cliente. */
    public function __construct()
    {
        $this->model = new Cliente();
    }

    /**
     * Verifica si el usuario puede gestionar clientes (crear/editar).
     * Roles: Administrador (1) y Empleado (3).
     */
    private function puedeGestionar(): bool
    {
        return isset($_SESSION['user'])
            && in_array($_SESSION['user']['rol_id'], [1, 3], true);
    }

    /**
     * Verifica si el usuario es Administrador (rol_id = 1).
     * Solo el admin puede eliminar registros.
     */
    private function esAdmin(): bool
    {
        return isset($_SESSION['user'])
            && $_SESSION['user']['rol_id'] === 1;
    }

    /**
     * Valida el token CSRF del formulario.
     * Si no coincide, redirige con error y termina la ejecución.
     */
    private function verificarCSRF(): void
    {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $this->redirectError('clientes', 'Petición no válida. Recarga la página.');
        }
    }

    /**
     * Extrae y devuelve los campos del cliente desde $_POST.
     *
     * @return array<string, string>
     */
    private function camposDesdePost(): array
    {
        return [
            'nombre'       => $_POST['nombre']      ?? '',
            'tipo_doc'     => $_POST['tipo_doc']     ?? 'CC',
            'num_doc'      => $_POST['num_doc']      ?? '',
            'digito_ver'   => $_POST['digito_ver']   ?? '',
            'telefono'     => $_POST['telefono']     ?? '',
            'email'        => $_POST['email']        ?? '',
            'direccion'    => $_POST['direccion']    ?? '',
            'ciudad'       => $_POST['ciudad']       ?? '',
            'departamento' => $_POST['departamento'] ?? '',
        ];
    }

    /**
     * Punto de entrada del controlador.
     * Lee la acción desde $_GET['action'] y delega al método correspondiente.
     */
    public function ejecutar(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->redirectError('login', 'Tu sesión ha expirado.');
        }

        switch ($_GET['action'] ?? '') {
            case 'crear':     $this->crear();     break;
            case 'editar':    $this->editar();    break;
            case 'actualizar':$this->actualizar();break;
            case 'eliminar':  $this->eliminar();  break;
        }
    }

    /**
     * Crea un nuevo cliente tras validar permisos y token CSRF.
     */
    private function crear(): void
    {
        if (!$this->puedeGestionar()) {
            $this->redirectError('clientes', 'No tienes permisos para crear clientes.');
        }
        $this->verificarCSRF();
        $res = $this->model->crear($this->camposDesdePost());
        $res === true
            ? $this->redirectOk('clientes', 'creado')
            : $this->redirectError('clientes', $res);
    }

    /**
     * Carga la vista de edición del cliente indicado en $_GET['id'].
     */
    private function editar(): void
    {
        if (!$this->puedeGestionar()) {
            $this->redirectError('clientes', 'No tienes permisos para editar clientes.');
        }
        $cliente = $this->model->obtenerPorId((int)($_GET['id'] ?? 0));
        if (!$cliente) {
            $this->redirectError('clientes', 'Cliente no encontrado.');
        }
        require __DIR__ . '/../views/editar_cliente.php';
    }

    /**
     * Actualiza los datos de un cliente existente.
     */
    private function actualizar(): void
    {
        if (!$this->puedeGestionar()) {
            $this->redirectError('clientes', 'No tienes permisos para editar clientes.');
        }
        $this->verificarCSRF();
        $res = $this->model->actualizar((int)($_POST['id'] ?? 0), $this->camposDesdePost());
        $res === true
            ? $this->redirectOk('clientes', 'actualizado')
            : $this->redirectError('clientes', $res);
    }

    /**
     * Elimina un cliente. Solo accesible para Administradores.
     */
    private function eliminar(): void
    {
        if (!$this->esAdmin()) {
            $this->redirectError('clientes', 'Solo el administrador puede eliminar clientes.');
        }
        $cliente = $this->model->obtenerPorId((int)($_GET['id'] ?? 0));
        if (!$cliente) {
            $this->redirectError('clientes', 'Cliente no encontrado.');
        }
        $this->model->eliminar((int)$_GET['id']);
        $this->redirectOk('clientes', 'eliminado');
    }
}

(new ClienteController())->ejecutar();