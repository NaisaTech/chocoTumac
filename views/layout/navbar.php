<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$rol = $_SESSION['user']['rol_id'] ?? 0;
$nombre_usuario = $_SESSION['user']['nombre'] ?? '';

$roles_label = [1 => 'Administrador', 2 => 'Gerente', 3 => 'Empleado'];
$rol_label = $roles_label[$rol] ?? 'Usuario';
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background:#5C3317;">
<div class="container-fluid">

    <a class="navbar-brand d-flex align-items-center gap-2"
       href="/choco_tumac/index.php?view=dashboard">
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
                <a class="nav-link <?= (($_GET['view'] ?? '') == 'dashboard') ? 'active fw-bold' : '' ?>"
                   href="/choco_tumac/index.php?view=dashboard">
                    Usuarios
                </a>
            </li>
            <?php endif; ?>

            <?php if (in_array($rol, [1, 2, 3])): ?>
            <li class="nav-item">
                <a class="nav-link <?= (($_GET['view'] ?? '') == 'clientes') ? 'active fw-bold' : '' ?>"
                   href="/choco_tumac/index.php?view=clientes">
                    Clientes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (($_GET['view'] ?? '') == 'proveedores') ? 'active fw-bold' : '' ?>"
                   href="/choco_tumac/index.php?view=proveedores">
                    Proveedores
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-item">
                <a class="nav-link <?= (($_GET['view'] ?? '') == 'perfil') ? 'active fw-bold' : '' ?>"
                   href="/choco_tumac/index.php?view=perfil">
                    Mi Perfil
                </a>
            </li>

        </ul>

        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-lg-block">
                <div class="text-white fw-semibold" style="font-size:.9rem; line-height:1.2;">
                    <?= htmlspecialchars($nombre_usuario) ?>
                </div>
                <div style="font-size:.75rem; color:#f5c98a;"><?= $rol_label ?></div>
            </div>
            <a href="/choco_tumac/controllers/UsuarioController.php?action=logout"
               class="btn btn-sm btn-outline-light">
               Cerrar sesión
            </a>
        </div>
    </div>

</div>
</nav>
