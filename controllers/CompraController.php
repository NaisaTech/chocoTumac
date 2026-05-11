<?php
/**
 * Controlador: Compra – ChocoTumac Sprint 2.
 *
 * Gestiona las peticiones HTTP relacionadas con el registro
 * de compras de cacao en grano seco a proveedores.
 *
 * Al registrar una compra, el inventario del producto se
 * incrementa automáticamente a través del modelo Compra.
 *
 * Al eliminar una compra, el inventario se revierte.
 *
 * Acciones disponibles:
 *   - crear:    Registra una nueva compra (admin y empleado)
 *   - eliminar: Elimina una compra y revierte el stock (solo admin)
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

require_once __DIR__ . '/../models/Compra.php';
require_once __DIR__ . '/Redirectable.php';

/** URL base del sistema para redirecciones */
define("BASE_URL", "/chocoTumac/");

class CompraController {
    use Redirectable;


    /** @var Compra Instancia del modelo de compras */
    private $model;

    public function __construct() {
        $this->model = new Compra();
    }

    // ── Helpers de seguridad ──────────────────────────────────────────────

    /**
     * Verifica que el usuario tenga permiso para gestionar compras.
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
            $this->redirectError('compras', 'Petición no válida. Recarga la página.');
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
             * Registra una nueva compra de cacao.
             * El modelo calcula el total y actualiza el inventario automáticamente.
             * Permitido para Administrador y Empleado.
             */
            case 'crear':
                if (!$this->puedeGestionar()) {
                    $this->redirectError('compras', 'No tienes permisos para registrar compras.');
                }
                $this->verificarCSRF();

                $res = $this->model->crear([
                    'proveedor_id'    => $_POST['proveedor_id']    ?? 0,
                    'producto_id'     => $_POST['producto_id']     ?? 0,
                    'fecha'           => $_POST['fecha']           ?? '',
                    'cantidad'        => $_POST['cantidad']        ?? 0,
                    'unidad'          => $_POST['unidad']          ?? 'lb',
                    'precio_unitario' => $_POST['precio_unitario'] ?? 0,
                    'observaciones'   => $_POST['observaciones']   ?? '',
                ], $_SESSION['user']['id']);

                if ($res === true) {
                    $this->redirectOk('compras', 'creado');} else {
                    $this->redirectError('compras', $res);}
                break;

            /**
             * Acción: eliminar
             * Elimina una compra y revierte el stock del inventario.
             * Solo administradores. Recibe el ID por GET.
             */
            case 'eliminar':
                if (!$this->esAdmin()) {
                    $this->redirectError('compras', 'Solo el administrador puede eliminar compras.');
                }

                $res = $this->model->eliminar(
                    $_GET['id']  ?? 0,
                    $_SESSION['user']['id']
                );

                if ($res === true) {
                    $this->redirectOk('compras', 'eliminado');} else {
                    $this->redirectError('compras', $res);}
                break;
        }
    }
}

(new CompraController())->ejecutar();