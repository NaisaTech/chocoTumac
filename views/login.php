<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user'])) {
    header("Location: /choco_tumac/index.php?view=dashboard");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/choco_tumac/public/css/styles.css">
</head>
<body>

<div class="login-wrapper">
    <div class="card login-card shadow-lg">

        <div class="login-logo"></div>
        <p class="login-subtitle">Sistema de Gestión<br><strong>Chocolate Tumaco</strong></p>

        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] === 'login'): ?>
                <div class="alert alert-danger alert-auto" role="alert">
                    <strong>Acceso denegado.</strong> Correo o contraseña incorrectos.
                </div>
            <?php elseif ($_GET['error'] === 'sesion'): ?>
                <div class="alert alert-warning alert-auto" role="alert">
                    Tu sesión ha expirado. Inicia sesión nuevamente.
                </div>
            <?php else: ?>
                <div class="alert alert-danger alert-auto" role="alert">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST"
              action="/choco_tumac/controllers/UsuarioController.php?action=login"
              data-validate>

            <div class="mb-3">
                <label class="form-label fw-semibold">Correo electrónico</label>
                <input class="form-control" type="email" name="email"
                       placeholder="usuario@ejemplo.com"
                       autocomplete="username" required>
                <div class="invalid-feedback">Ingresa un correo válido.</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Contraseña</label>
                <input class="form-control" type="password" name="password"
                       placeholder="••••••••"
                       autocomplete="current-password" required>
                <div class="invalid-feedback">Ingresa tu contraseña.</div>
            </div>

            <button type="submit" class="btn btn-ct-primary w-100 py-2 fw-semibold">
                Ingresar
            </button>
        </form>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/choco_tumac/public/js/app.js"></script>
</body>
</html>
