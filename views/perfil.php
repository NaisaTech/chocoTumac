<!-- Este código es la plantilla para la página de perfil del usuario en la aplicación Chocolate Tumaco. Muestra la información personal del usuario, como su nombre, correo electrónico y teléfono, y permite al usuario actualizar estos datos. Además, incluye una sección para cambiar la contraseña. El diseño está basado en Bootstrap para una apariencia moderna y responsiva. El código también maneja mensajes de error y éxito para informar al usuario sobre el resultado de sus acciones, proporcionando una experiencia de usuario clara y amigable al gestionar su perfil en la aplicación. -->
<?php
/* Bloquear acceso directo a esta vista por URL */
if (!defined('CHOCOTUMAC_APP')) {
    header("Location: /chocoTumac/index.php");
    exit();
}

session_start();

/* Prevenir caché del navegador*/
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

/* Protección de ruta — redirigir si no hay sesión activa */
if (!isset($_SESSION['user'])) {
    header("Location: /chocoTumac/index.php?view=login&error=" . urlencode("Tu sesión ha expirado. Inicia sesión nuevamente."));
    exit();
}
$user = $_SESSION['user'];
?>
<!-- El bloque de código PHP al inicio verifica si el usuario ha iniciado sesión. Si no es así, redirige al usuario a la página de inicio de sesión con un mensaje de error indicando que la sesión ha expirado. Esto garantiza que solo los usuarios autenticados puedan acceder a la página de perfil, protegiendo la información personal del usuario y manteniendo la seguridad de la aplicación. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi Perfil – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/chocoTumac/public/css/styles.css">
</head>
<body>
<?php require 'views/layout/navbar.php'; ?>

<div class="container mt-4" style="max-width:640px;">

    <div class="page-header">
        <h2>Mi Perfil</h2>
    </div>

    <!-- El bloque de código PHP maneja la visualización de mensajes de error o éxito para informar al usuario sobre las acciones realizadas. Si hay un mensaje de error (indicado por la variable 'error' en la URL), se muestra una alerta de Bootstrap con el mensaje correspondiente. Si hay un mensaje de éxito (indicado por la variable 'msg' en la URL), se muestra una alerta con el tipo y texto correspondiente según el valor de 'msg'. Esto proporciona retroalimentación visual al usuario sobre el resultado de sus acciones, como actualizar los datos personales o cambiar la contraseña. La alerta es automática y se puede cerrar manualmente por el usuario. -->
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
    <!-- Este bloque de código maneja la visualización de mensajes de error o éxito para informar al usuario sobre las acciones realizadas. Si hay un mensaje de error (indicado por la variable 'error' en la URL), se muestra una alerta de Bootstrap con el mensaje correspondiente. Si hay un mensaje de éxito (indicado por la variable 'msg' en la URL), se muestra una alerta con el tipo y texto correspondiente según el valor de 'msg'. Esto proporciona retroalimentación visual al usuario sobre el resultado de sus acciones, como actualizar los datos personales o cambiar la contraseña. La alerta es automática y se puede cerrar manualmente por el usuario. -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Datos Personales</h5>
        <form method="POST" action="/chocoTumac/controllers/UsuarioController.php?action=actualizarPerfil" data-validate>
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
    <!-- Este bloque de código maneja la visualización de mensajes de error o éxito para informar al usuario sobre las acciones realizadas. Si hay un mensaje de error (indicado por la variable 'error' en la URL), se muestra una alerta de Bootstrap con el mensaje correspondiente. Si hay un mensaje de éxito (indicado por la variable 'msg' en la URL), se muestra una alerta con el tipo y texto correspondiente según el valor de 'msg'. Esto proporciona retroalimentación visual al usuario sobre el resultado de sus acciones, como actualizar los datos personales o cambiar la contraseña. La alerta es automática y se puede cerrar manualmente por el usuario. -->
    <div class="card p-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Cambiar Contraseña</h5>
        <form method="POST" action="/chocoTumac/controllers/UsuarioController.php?action=cambiarPassword" data-validate>
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

<!-- El código JavaScript al final del documento incluye la biblioteca de Bootstrap para manejar componentes interactivos como modales y alertas, así como un archivo personalizado app.js para funcionalidades específicas de la aplicación. Esto asegura que los componentes de la interfaz funcionen correctamente y que cualquier funcionalidad personalizada esté disponible para mejorar la experiencia del usuario al gestionar su perfil en la aplicación. -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/chocoTumac/public/js/app.js"></script>
</body>
</html>
