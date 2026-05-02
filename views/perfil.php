<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: /choco_tumac/index.php"); exit(); }
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi Perfil – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/choco_tumac/public/css/styles.css">
</head>
<body>
<?php require 'views/layout/navbar.php'; ?>

<div class="container mt-4" style="max-width:640px;">

    <div class="page-header">
        <h2>Mi Perfil</h2>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-auto alert-dismissible" role="alert">
            <strong>Error:</strong> <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'ok'): ?>
            <div class="alert alert-success alert-auto alert-dismissible" role="alert">
                ✓ Datos personales actualizados correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'pass'): ?>
            <div class="alert alert-success alert-auto alert-dismissible" role="alert">
                ✓ Contraseña cambiada correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- DATOS PERSONALES -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Datos Personales</h5>
        <form method="POST" action="/choco_tumac/controllers/UsuarioController.php?action=actualizarPerfil" data-validate>
            <input type="hidden" name="id"         value="<?= $user['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="mb-3">
                <label class="form-label fw-semibold">Nombre completo</label>
                <input class="form-control" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>" required minlength="2">
                <div class="invalid-feedback">El nombre es obligatorio.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Correo electrónico</label>
                <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                <div class="invalid-feedback">Ingresa un correo válido.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Teléfono</label>
                <input class="form-control" name="telefono" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>" placeholder="3001234567">
            </div>

            <button type="submit" class="btn btn-ct-primary">Guardar cambios</button>
        </form>
    </div>

    <!-- CAMBIAR CONTRASEÑA -->
    <div class="card p-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Cambiar Contraseña</h5>
        <form method="POST" action="/choco_tumac/controllers/UsuarioController.php?action=cambiarPassword" data-validate>
            <input type="hidden" name="id"         value="<?= $user['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="mb-3">
                <label class="form-label fw-semibold">Contraseña actual</label>
                <input class="form-control" type="password" name="actual" placeholder="••••••••" autocomplete="current-password" required>
                <div class="invalid-feedback">Este campo es obligatorio.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nueva contraseña</label>
                <input class="form-control" type="password" name="nueva" id="input-password"
                       placeholder="Mínimo 8 caracteres" autocomplete="new-password" required>
                <div id="feedback-password" class="form-text"></div>
                <div class="invalid-feedback">Ingresa la nueva contraseña.</div>
            </div>

            <button type="submit" class="btn btn-warning fw-semibold">Cambiar contraseña</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/choco_tumac/public/js/app.js"></script>
</body>
</html>
