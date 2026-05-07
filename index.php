<?php
/**
 * Enrutador principal – ChocoTumac.
 *
 * Punto de entrada único del sistema. Gestiona:
 *   - Inicio de sesión PHP
 *   - Generación del token CSRF global
 *   - Protección de rutas que requieren autenticación
 *   - Carga de la vista correspondiente según el parámetro 'view'
 *
 * La constante CHOCOTUMAC_APP se define aquí para que las vistas
 * puedan verificar que fueron cargadas desde este enrutador
 * y no accedidas directamente por URL.
 *
 * @package ChocoTumac
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

// Prevenir caché del navegador en todo el sistema
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// Constante que indica que la carga viene desde el enrutador principal
// Las vistas la verifican para bloquear acceso directo por URL
define('CHOCOTUMAC_APP', true);

// Token CSRF global: se genera una vez por sesión
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$view = $_GET['view'] ?? 'login';

// Vistas que requieren sesión activa
$vistas_protegidas = ['dashboard', 'perfil', 'clientes', 'proveedores',
                      'inventario', 'compras', 'ventas', 'factura', 'editar_producto'];

if (in_array($view, $vistas_protegidas) && !isset($_SESSION['user'])) {
    header("Location: /chocoTumac/index.php?view=login&error="
        . urlencode("Tu sesión ha expirado. Inicia sesión nuevamente."));
    exit();
}

// Enrutar a la vista correspondiente
switch ($view) {
    case 'dashboard':
        require_once 'views/dashboard.php';
        break;
    case 'perfil':
        require_once 'views/perfil.php';
        break;
    case 'clientes':
        require_once 'views/clientes.php';
        break;
    case 'proveedores':
        require_once 'views/proveedores.php';
        break;
    case 'inventario':
        require_once 'views/inventario.php';
        break;
    case 'editar_producto':
        // Solo admin
        if ($_SESSION['user']['rol_id'] != 1) {
            header("Location: " . BASE_URL . "index.php?view=inventario&error=" . urlencode("Acceso no permitido."));
            exit();
        }
        require_once 'models/Producto.php';
        $modelProducto = new Producto();
        $producto = $modelProducto->obtenerPorId($_GET['id'] ?? 0);
        if (!$producto) {
            header("Location: " . BASE_URL . "index.php?view=inventario&error=" . urlencode("Producto no encontrado."));
            exit();
        }
        require_once 'views/editar_producto.php';
        break;
    case 'compras':
        require_once 'views/compras.php';
        break;
    case 'ventas':
        require_once 'views/ventas.php';
        break;
    case 'factura':
        require_once 'views/factura.php';
        break;
    default:
        require_once 'views/login.php';
        break;
}
