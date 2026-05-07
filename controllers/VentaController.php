<?php
/**
 * Controlador: Venta – ChocoTumac Sprint 2.
 *
 * Gestiona las peticiones HTTP relacionadas con el registro
 * de ventas de productos a clientes.
 *
 * Al registrar una venta, el modelo verifica que haya stock
 * suficiente y luego decrementa el inventario automáticamente.
 * Si no hay stock suficiente, el sistema retorna un error claro.
 *
 * Al eliminar una venta, el stock del producto se restaura.
 *
 * Acciones disponibles:
 *   - crear:    Registra una nueva venta (admin y empleado)
 *   - eliminar: Elimina una venta y restaura el stock (solo admin)
 *
 * Seguridad:
 *   - Verifica sesión activa en todas las acciones
 *   - Verifica token CSRF en acciones POST
 *   - Restringe la eliminación al rol Administrador (rol_id = 1)
 *   - Permite crear a Administrador (1) y Empleado (3)
 *
 * @package ChocoTumac
 * @sprint  2
 */

ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../models/Venta.php';

/** URL base del sistema para redirecciones */
define("BASE_URL", "/chocoTumac/");

class VentaController {

    /** @var Venta Instancia del modelo de ventas */
    private $model;

    public function __construct() {
        $this->model = new Venta();
    }

    // ── Helpers de seguridad ──────────────────────────────────────────────

    /**
     * Verifica que el usuario tenga permiso para gestionar ventas.
     * Roles permitidos: Administrador (1) y Empleado (3).
     *
     * @return bool true si puede gestionar, false si no
     */
    private function puedeGestionar() {
        return isset($_SESSION['user'])
            && in_array($_SESSION['user']['rol_id'], [1, 3]);
    }

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
     */
    private function verificarCSRF() {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            header("Location: " . BASE_URL . "index.php?view=ventas&error="
                . urlencode("Petición no válida. Recarga la página."));
            exit();
        }
    }

    /**
     * Verifica que exista una sesión activa.
     * Redirige al login si no hay sesión.
     */
    private function verificarSesion() {
        if (!isset($_SESSION['user'])) {
            header("Location: " . BASE_URL . "index.php?view=login&error="
                . urlencode("Tu sesión ha expirado."));
            exit();
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
             * Registra una nueva venta.
             * El modelo valida el stock antes de insertar y
             * descuenta el inventario automáticamente al guardar.
             * Permitido para Administrador y Empleado.
             */
            case 'crear':
                if (!$this->puedeGestionar()) {
                    header("Location: " . BASE_URL . "index.php?view=ventas&error="
                        . urlencode("No tienes permisos para registrar ventas."));
                    exit();
                }
                $this->verificarCSRF();

                $res = $this->model->crear([
                    'tipo_cliente'       => $_POST['tipo_cliente']       ?? 'registrado',
                    'cliente_id'         => $_POST['cliente_id']         ?? 0,
                    'cliente_ocasional'  => $_POST['cliente_ocasional']  ?? '',
                    'doc_ocasional_tipo' => $_POST['doc_ocasional_tipo'] ?? '',
                    'doc_ocasional_num'  => $_POST['doc_ocasional_num']  ?? '',
                    'producto_id'        => $_POST['producto_id']        ?? 0,
                    'fecha'              => $_POST['fecha']              ?? '',
                    'cantidad'           => $_POST['cantidad']           ?? 0,
                    'precio_unitario'    => $_POST['precio_unitario']    ?? 0,
                    'iva_porcentaje'     => $_POST['iva_porcentaje']     ?? 0,
                    'forma_pago'         => $_POST['forma_pago']         ?? 'contado',
                    'observaciones'      => $_POST['observaciones']      ?? '',
                ], $_SESSION['user']['id']);

                if (is_int($res)) {
                    // Redirigir directo a la factura imprimible
                    header("Location: " . BASE_URL . "index.php?view=factura&id=" . $res);
                } else {
                    header("Location: " . BASE_URL . "index.php?view=ventas&error=" . urlencode($res));
                }
                break;

            /**
             * Acción: eliminar
             * Elimina una venta y restaura el stock del producto.
             * Solo administradores. Recibe el ID por GET.
             */
            case 'eliminar':
                if (!$this->esAdmin()) {
                    header("Location: " . BASE_URL . "index.php?view=ventas&error="
                        . urlencode("Solo el administrador puede eliminar ventas."));
                    exit();
                }

                $res = $this->model->eliminar(
                    $_GET['id']  ?? 0,
                    $_SESSION['user']['id']
                );

                if ($res === true) {
                    header("Location: " . BASE_URL . "index.php?view=ventas&msg=eliminado");
                } else {
                    header("Location: " . BASE_URL . "index.php?view=ventas&error=" . urlencode($res));
                }
                break;
        }
    }
}

(new VentaController())->ejecutar();
