<?php
// Prevenir exposición de errores en producción
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

// Token CSRF global
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$view = $_GET['view'] ?? 'login';

// Vistas protegidas que requieren sesión
$vistas_protegidas = ['dashboard', 'perfil', 'clientes', 'proveedores'];

if (in_array($view, $vistas_protegidas) && !isset($_SESSION['user'])) {
    header("Location: /choco_tumac/index.php?view=login&error=sesion");
    exit();
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
