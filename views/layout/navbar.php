<?php
/**
 * Barra de navegación global – ChocoTumac.
 *
 * Muestra módulos según el rol:
 *   - Administrador (1): acceso total
 *   - Empleado     (3): compras, ventas, clientes, proveedores, inventario
 *   - Gerente      (2): solo lectura en todos los módulos
 *
 * @package ChocoTumac
 * @sprint  1, 2
 */
if (session_status() === PHP_SESSION_NONE) session_start();

$rol            = $_SESSION['user']['rol_id'] ?? 0;
$nombre_usuario = $_SESSION['user']['nombre'] ?? '';
$vista_actual   = $_GET['view'] ?? '';

$roles_label = [1 => 'Administrador', 2 => 'Gerente', 3 => 'Empleado'];
$rol_label   = $roles_label[$rol] ?? 'Usuario';

/** Retorna clase CSS 'active fw-bold' si la vista coincide con la actual */
$activo = fn($v) => $vista_actual === $v ? 'active fw-bold' : '';
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background:#5C3317;">
<div class="container-fluid">

    <a class="navbar-brand d-flex align-items-center gap-2"
       href="/chocoTumac/index.php?view=dashboard">
        <span>Chocolate Tumaco</span>
    </a>

    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#navMain">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
        <ul class="navbar-nav me-auto">

            <?php if ($rol == 1): ?>
            <li class="nav-item">
                <a class="nav-link <?= $activo('dashboard') ?>"
                   href="/chocoTumac/index.php?view=dashboard">Usuarios</a>
            </li>
            <?php endif; ?>

            <?php if (in_array($rol, [1, 2, 3])): ?>
            <li class="nav-item">
                <a class="nav-link <?= $activo('clientes') ?>"
                   href="/chocoTumac/index.php?view=clientes">Clientes</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activo('proveedores') ?>"
                   href="/chocoTumac/index.php?view=proveedores">Proveedores</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activo('inventario') ?>"
                   href="/chocoTumac/index.php?view=inventario">Inventario</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activo('compras') ?>"
                   href="/chocoTumac/index.php?view=compras">Compras</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activo('ventas') ?>"
                   href="/chocoTumac/index.php?view=ventas">Ventas</a>
            </li>
            <?php endif; ?>

            <li class="nav-item">
                <a class="nav-link <?= $activo('perfil') ?>"
                   href="/chocoTumac/index.php?view=perfil">Mi Perfil</a>
            </li>

        </ul>

        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-lg-block">
                <div class="text-white fw-semibold" style="font-size:.9rem; line-height:1.2;">
                    <?= htmlspecialchars($nombre_usuario) ?>
                </div>
                <div style="font-size:.75rem; color:#f5c98a;"><?= $rol_label ?></div>
            </div>
            <a href="/chocoTumac/controllers/UsuarioController.php?action=logout"
               class="btn btn-sm btn-outline-light">Cerrar sesión</a>
        </div>
    </div>

</div>
</nav>
