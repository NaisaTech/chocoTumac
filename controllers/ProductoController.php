<?php
/**
 * Controlador: Producto – ChocoTumac Sprint 2.
 *
 * Gestiona las peticiones HTTP relacionadas con el catálogo de productos
 * y el ajuste manual de stock en el inventario.
 *
 * Acciones disponibles:
 *   - crear:      Registra un nuevo producto (solo admin)
 *   - crearTipo:  Registra un nuevo tipo de producto (solo admin)
 *   - editar:     Carga la vista de edición (solo admin)
 *   - actualizar: Guarda cambios de un producto (solo admin)
 *   - ajuste:     Ajusta el stock inicial de un producto (solo admin)
 *
 * Seguridad:
 *   - Verifica sesión activa en todas las acciones
 *   - Verifica token CSRF en todas las acciones POST
 *   - Restringe todas las acciones al rol Administrador (rol_id = 1)
 *
 * @package ChocoTumac
 * @sprint  2
 */

ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/Redirectable.php';

/** URL base del sistema para redirecciones */
define('BASE_URL', '/chocoTumac/');

class ProductoController
{
    use Redirectable;

    /** @var Producto Instancia del modelo de productos */
    private Producto $model;

    public function __construct()
    {
        $this->model = new Producto();
    }

    // ── Helpers de seguridad ─────────────────────────────────────────

    /**
     * Verifica que el usuario autenticado tenga rol de Administrador.
     *
     * @return bool true si es admin, false si no.
     */
    private function esAdmin(): bool
    {
        return isset($_SESSION['user']) && $_SESSION['user']['rol_id'] == 1;
    }

    /**
     * Verifica que el token CSRF del formulario coincida con el de sesión.
     * Redirige con error si el token no es válido.
     */
    private function verificarCSRF(): void
    {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $this->redirectError('inventario', 'Petición no válida. Recarga la página.');
        }
    }

    /**
     * Verifica que exista una sesión activa.
     * Redirige al login si no hay sesión.
     */
    private function verificarSesion(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->redirectError('login', 'Tu sesión ha expirado.');
        }
    }

    /**
     * Verifica que el usuario sea administrador.
     * Redirige con el mensaje indicado si no tiene permisos.
     *
     * @param string $msg Mensaje de error a mostrar.
     */
    private function requerirAdmin(string $msg): void
    {
        if (!$this->esAdmin()) {
            $this->redirectError('inventario', $msg);
        }
    }

    // ── Punto de entrada ─────────────────────────────────────────────

    /**
     * Lee el parámetro 'action' de la URL y delega a cada método privado.
     * Complejidad cognitiva: 2 (solo el switch + default).
     */
    public function ejecutar(): void
    {
        $this->verificarSesion();

        switch ($_GET['action'] ?? '') {
            case 'crear':      $this->crear();      break;
            case 'crearTipo':  $this->crearTipo();  break;
            case 'editar':     $this->editar();      break;
            case 'actualizar': $this->actualizar(); break;
            case 'ajuste':     $this->ajuste();     break;
            default:
                $this->redirectError('inventario', 'Acción no reconocida.');
                break;
        }
    }

    // ── Acciones ─────────────────────────────────────────────────────

    /**
     * Registra un nuevo producto en el catálogo.
     * Solo administradores. Requiere token CSRF válido.
     */
    private function crear(): void
    {
        $this->requerirAdmin('Solo el administrador puede crear productos.');
        $this->verificarCSRF();

        $res = $this->model->crear([
            'nombre'        => $_POST['nombre']        ?? '',
            'tipo_id'       => $_POST['tipo_id']       ?? '',
            'presentacion'  => $_POST['presentacion']  ?? '',
            'stock_inicial' => $_POST['stock_inicial'] ?? 0,
            'stock_minimo'  => $_POST['stock_minimo']  ?? 0,
            'precio_venta'  => $_POST['precio_venta']  ?? 0,
        ]);

        $res === true
            ? $this->redirectOk('inventario', 'producto_creado')
            : $this->redirectError('inventario', $res);
    }

    /**
     * Registra un nuevo tipo de producto en el catálogo.
     * Solo administradores. Requiere token CSRF válido.
     */
    private function crearTipo(): void
    {
        $this->requerirAdmin('Solo el administrador puede crear tipos de producto.');
        $this->verificarCSRF();

        $res = $this->model->crearTipo([
            'nombre'                => $_POST['nombre']                ?? '',
            'unidad'                => $_POST['unidad']                ?? 'kg',
            'unidad_venta'          => $_POST['unidad_venta']          ?? 'und',
            'requiere_presentacion' => $_POST['requiere_presentacion'] ?? null,
            'descripcion'           => $_POST['descripcion']           ?? '',
        ]);

        $res === true
            ? $this->redirectOk('inventario', 'tipo_creado')
            : $this->redirectError('inventario', $res);
    }

    /**
     * Carga la vista de edición de un producto existente.
     * Solo administradores. Recibe el ID del producto por GET.
     */
    private function editar(): void
    {
        $this->requerirAdmin('Solo el administrador puede editar productos.');

        $producto = $this->model->obtenerPorId((int)($_GET['id'] ?? 0));
        if (!$producto) {
            $this->redirectError('inventario', 'Producto no encontrado.');
        }

        // Redirige a index.php para tener contexto completo (sesión, navbar, variables)
        $this->redirect('index.php?view=editar_producto&id=' . (int)($_GET['id'] ?? 0));
    }

    /**
     * Guarda los cambios de un producto editado.
     * Solo administradores. Requiere token CSRF válido.
     */
    private function actualizar(): void
    {
        $this->requerirAdmin('Solo el administrador puede editar productos.');
        $this->verificarCSRF();

        $res = $this->model->actualizar((int)($_POST['id'] ?? 0), [
            'nombre'        => $_POST['nombre']        ?? '',
            'tipo_id'       => $_POST['tipo_id']       ?? '',
            'presentacion'  => $_POST['presentacion']  ?? '',
            'stock_minimo'  => $_POST['stock_minimo']  ?? 0,
            'precio_venta'  => $_POST['precio_venta']  ?? 0,
            'activo'        => isset($_POST['activo']) ? 1 : 0,
        ]);

        $res === true
            ? $this->redirectOk('inventario', 'producto_actualizado')
            : $this->redirectError('inventario', $res);
    }

    /**
     * Establece el stock de un producto a una cantidad específica.
     * Usado para carga inicial o corrección manual de stock.
     * Solo administradores. Requiere token CSRF válido.
     */
    private function ajuste(): void
    {
        $this->requerirAdmin('Solo el administrador puede ajustar el stock.');
        $this->verificarCSRF();

        $res = $this->model->ajusteInicial(
            (int)($_POST['producto_id'] ?? 0),
            (float)($_POST['cantidad']  ?? 0),
            (int)$_SESSION['user']['id']
        );

        $res === true
            ? $this->redirectOk('inventario', 'ajuste_ok')
            : $this->redirectError('inventario', $res);
    }
}

(new ProductoController())->ejecutar();