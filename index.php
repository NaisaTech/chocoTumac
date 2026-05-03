<?php
ini_set('display_errors', 0);
session_start();

// Prevenir caché en todo el sistema
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// Token CSRF global
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$view = $_GET['view'] ?? 'login';

// Vistas protegidas
$vistas_protegidas = ['dashboard', 'perfil', 'clientes', 'proveedores'];

if (in_array($view, $vistas_protegidas) && !isset($_SESSION['user'])) {
    header("Location: /choco_tumac/index.php?view=login&error=" . urlencode("Tu sesión ha expirado. Inicia sesión nuevamente."));
    exit();
}

// Bloquear acceso directo a las vistas por URL
// Las vistas solo deben cargarse desde index.php
if (!defined('CHOCOTUMAC_APP')) {
    define('CHOCOTUMAC_APP', true);
}

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
    default:
        require_once 'views/login.php';
        break;
}