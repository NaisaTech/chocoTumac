<?php
/**
 * Controlador: Producto – ChocoTumac Sprint 2.
 *
 * Gestiona las peticiones HTTP relacionadas con el catálogo de productos
 * y el ajuste manual de stock en el inventario.
 *
 * Acciones disponibles:
 *   - crear:      Registra un nuevo producto (solo admin)
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
define("BASE_URL", "/chocoTumac/");

class ProductoController {
    use Redirectable;


    /** @var Producto Instancia del modelo de productos */
    private $model;

    public function __construct() {
        $this->model = new Producto();
    }

    // ── Helpers de seguridad ──────────────────────────────────────────────

    /**
     * Verifica que el usuario autenticado tenga rol de Administrador.
     *
     * @return bool true si es admin, false si no
     */
    private function esAdmin() {
        return isset($_SESSION['user']) && $_SESSION['user']['rol_id'] == 1;
    }

    /**
     * Verifica que el token CSRF del formulario coincida con el de la sesión.
     * Redirige con error si el token no es válido.
     * Previene ataques de falsificación de petición entre sitios.
     */
    private function verificarCSRF() {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $this->redirectError('inventario', 'Petición no válida. Recarga la página.');
        }
    }

    /**
     * Verifica que exista una sesión activa.
     * Redirige al login si no hay sesión.
     */
    private function verificarSesion() {
        if (!isset($_SESSION['user'])) {
            $this->redirectError('login', 'Tu sesión ha expirado.');
        }
    }

    // ── Acciones ──────────────────────────────────────────────────────────

    /**
     * Punto de entrada del controlador.
     * Lee el parámetro 'action' de la URL y ejecuta la acción correspondiente.
     */
    public function ejecutar() {
        $accion = $_GET['action'] ?? '';
        $this->verificarSesion();

        switch ($accion) {

            /**
             * Acción: crear
             * Registra un nuevo producto en el catálogo.
             * Solo administradores. Requiere token CSRF válido.
             */
            case 'crear':
                if (!$this->esAdmin()) {
                    $this->redirectError('inventario', 'Solo el administrador puede crear productos.');
                }
                $this->verificarCSRF();

                $res = $this->model->crear([
                    'nombre'        => $_POST['nombre']        ?? '',
                    'tipo_id'       => $_POST['tipo_id']       ?? '',
                    'presentacion'  => $_POST['presentacion']  ?? '',
                    'stock_inicial' => $_POST['stock_inicial'] ?? 0,
                    'stock_minimo'  => $_POST['stock_minimo']  ?? 0,
                    'precio_venta'  => $_POST['precio_venta']  ?? 0,
                ]);

                if ($res === true) {
                    $this->redirectOk('inventario', 'producto_creado');} else {
                    $this->redirectError('inventario', $res);}
                break;

            /**
             * Acción: crearTipo
             * Registra un nuevo tipo de producto (solo admin).
             */
            case 'crearTipo':
                if (!$this->esAdmin()) {
                    $this->redirectError('inventario', 'Solo el administrador puede crear tipos de producto.');
                }
                $this->verificarCSRF();

                $res = $this->model->crearTipo([
                    'nombre'                => $_POST['nombre']                ?? '',
                    'unidad'                => $_POST['unidad']                ?? 'kg',
                    'unidad_venta'          => $_POST['unidad_venta']          ?? 'und',
                    'requiere_presentacion' => $_POST['requiere_presentacion'] ?? null,
                    'descripcion'           => $_POST['descripcion']           ?? '',
                ]);

                if ($res === true) {
                    $this->redirectOk('inventario', 'tipo_creado');} else {
                    $this->redirectError('inventario', $res);}
                break;

            /**
             * Acción: editar
             * Carga la vista de edición de un producto.
             * Solo administradores. Recibe el ID por GET.
             */
            case 'editar':
                if (!$this->esAdmin()) {
                    $this->redirectError('inventario', 'Solo el administrador puede editar productos.');
                }

                $producto = $this->model->obtenerPorId($_GET['id'] ?? 0);
                if (!$producto) {
                    $this->redirectError('inventario', 'Producto no encontrado.');
                }

                // Redirigir a index.php para que tenga el contexto completo (sesión, navbar, variables)
                $this->redirect("index.php?view=editar_producto&id=" . (int)($_GET['id'] ?? 0));
                exit();

            /**
             * Acción: actualizar
             * Guarda los cambios de un producto editado.
             * Solo administradores. Requiere token CSRF válido.
             */
            case 'actualizar':
                if (!$this->esAdmin()) {
                    $this->redirectError('inventario', 'Solo el administrador puede editar productos.');
                }
                $this->verificarCSRF();

                $res = $this->model->actualizar($_POST['id'] ?? 0, [
                    'nombre'        => $_POST['nombre']        ?? '',
                    'tipo_id'       => $_POST['tipo_id']       ?? '',
                    'presentacion'  => $_POST['presentacion']  ?? '',
                    'stock_minimo'  => $_POST['stock_minimo']  ?? 0,
                    'precio_venta'  => $_POST['precio_venta']  ?? 0,
                    'activo'        => isset($_POST['activo']) ? 1 : 0,
                ]);

                if ($res === true) {
                    $this->redirectOk('inventario', 'producto_actualizado');} else {
                    $this->redirectError('inventario', $res);}
                break;

            /**
             * Acción: ajuste
             * Establece el stock de un producto a una cantidad específica.
             * Usado para carga inicial o corrección manual.
             * Solo administradores. Requiere token CSRF válido.
             */
            case 'ajuste':
                if (!$this->esAdmin()) {
                    $this->redirectError('inventario', 'Solo el administrador puede ajustar el stock.');
                }
                $this->verificarCSRF();

                $res = $this->model->ajusteInicial(
                    $_POST['producto_id'] ?? 0,
                    $_POST['cantidad']    ?? 0,
                    $_SESSION['user']['id']
                );

                if ($res === true) {
                    $this->redirectOk('inventario', 'ajuste_ok');} else {
                    $this->redirectError('inventario', $res);}
                break;
                    default:
                $this->redirectError('inventario', 'Acción no reconocida.');
                break;
}
    }
}

(new ProductoController())->ejecutar();