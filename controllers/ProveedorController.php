<?php
/**
 * Controlador: Proveedor – ChocoTumac
 *
 * Gestiona las acciones CRUD sobre proveedores:
 * crear, editar, actualizar y eliminar.
 * Verifica permisos por rol y protege mutaciones con token CSRF.
 *
 * @package ChocoTumac
 */

ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../models/Proveedor.php';
require_once __DIR__ . '/Redirectable.php';

define('BASE_URL', '/chocoTumac/');

class ProveedorController
{
    use Redirectable;

    /** @var Proveedor Instancia del modelo de proveedores */
    private Proveedor $model;

    /** Inicializa el modelo de Proveedor. */
    public function __construct()
    {
        $this->model = new Proveedor();
    }

    // ── Helpers de seguridad ─────────────────────────────────────────

    /**
     * Verifica si el usuario puede gestionar proveedores (crear/editar).
     * Roles permitidos: Administrador (1) y Empleado (3).
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
            && $_SESSION['user']['rol_id'] == 1;
    }

    /**
     * Valida el token CSRF enviado en el formulario.
     * Redirige con error si no coincide.
     */
    private function verificarCSRF(): void
    {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $this->redirectError('proveedores', 'Petición no válida. Recarga la página.');
        }
    }

    /**
     * Extrae y devuelve los campos del proveedor desde $_POST.
     *
     * @return array<string, string>
     */
    private function camposDesdePost(): array
    {
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

    /**
     * Verifica que el usuario pueda gestionar; redirige si no tiene permisos.
     *
     * @param string $msg Mensaje de error a mostrar.
     */
    private function requerirGestion(string $msg): void
    {
        if (!$this->puedeGestionar()) {
            $this->redirectError('proveedores', $msg);
        }
    }

    /**
     * Verifica que el usuario sea administrador; redirige si no lo es.
     *
     * @param string $msg Mensaje de error a mostrar.
     */
    private function requerirAdmin(string $msg): void
    {
        if (!$this->esAdmin()) {
            $this->redirectError('proveedores', $msg);
        }
    }

    // ── Punto de entrada ─────────────────────────────────────────────

    /**
     * Lee 'action' de la URL y delega a cada método privado.
     * Complejidad cognitiva: 2 (switch + default).
     */
    public function ejecutar(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->redirectError('login', 'Tu sesión ha expirado.');
        }

        switch ($_GET['action'] ?? '') {
            case 'crear':      $this->crear();      break;
            case 'editar':     $this->editar();     break;
            case 'actualizar': $this->actualizar(); break;
            case 'eliminar':   $this->eliminar();   break;
            default:
                $this->redirectError('proveedores', 'Acción no reconocida.');
                break;
        }
    }

    // ── Acciones ─────────────────────────────────────────────────────

    /**
     * Crea un nuevo proveedor tras validar permisos y token CSRF.
     */
    private function crear(): void
    {
        $this->requerirGestion('No tienes permisos para crear proveedores.');
        $this->verificarCSRF();

        $res = $this->model->crear($this->camposDesdePost());

        $res === true
            ? $this->redirectOk('proveedores', 'creado')
            : $this->redirectError('proveedores', $res);
    }

    /**
     * Carga la vista de edición del proveedor indicado en $_GET['id'].
     */
    private function editar(): void
    {
        $this->requerirGestion('No tienes permisos para editar proveedores.');

        $proveedor = $this->model->obtenerPorId((int)($_GET['id'] ?? 0));
        if (!$proveedor) {
            $this->redirectError('proveedores', 'Proveedor no encontrado.');
        }

        require_once __DIR__ . '/../views/editar_proveedor.php';
    }

    /**
     * Actualiza los datos de un proveedor existente.
     */
    private function actualizar(): void
    {
        $this->requerirGestion('No tienes permisos para editar proveedores.');
        $this->verificarCSRF();

        $res = $this->model->actualizar((int)($_POST['id'] ?? 0), $this->camposDesdePost());

        $res === true
            ? $this->redirectOk('proveedores', 'actualizado')
            : $this->redirectError('proveedores', $res);
    }

    /**
     * Elimina un proveedor. Solo accesible para Administradores.
     */
    private function eliminar(): void
    {
        $this->requerirAdmin('Solo el administrador puede eliminar proveedores.');

        $proveedor = $this->model->obtenerPorId((int)($_GET['id'] ?? 0));
        if (!$proveedor) {
            $this->redirectError('proveedores', 'Proveedor no encontrado.');
        }

        $this->model->eliminar((int)$_GET['id']);
        $this->redirectOk('proveedores', 'eliminado');
    }
}

(new ProveedorController())->ejecutar();