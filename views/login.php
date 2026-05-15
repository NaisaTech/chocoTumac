<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user'])) {
    header("Location: /chocoTumac/index.php?view=dashboard");
    exit();
}
?>
<!-- Este código es la plantilla para la página de inicio de sesión de la aplicación. Muestra un formulario donde los usuarios pueden ingresar su correo electrónico y contraseña para acceder al sistema. El formulario está diseñado con Bootstrap para una apariencia moderna y responsiva. Al enviar el formulario, se envía una solicitud POST al UsuarioController para procesar el inicio de sesión. Además, se manejan mensajes de error para informar a los usuarios sobre problemas como credenciales incorrectas o expiración de sesión, proporcionando una experiencia de usuario clara y amigable durante el proceso de autenticación. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="/chocoTumac/public/css/styles.css">
</head>
<body>

<!-- El bloque de código PHP al inicio verifica si el usuario ya ha iniciado sesión. Si es así, redirige automáticamente al dashboard principal de la aplicación para evitar que los usuarios autenticados vean la página de inicio de sesión nuevamente. Esto mejora la experiencia del usuario al proporcionar un acceso directo a la funcionalidad principal de la aplicación sin necesidad de pasar por la pantalla de inicio de sesión cada vez que acceden al sitio. -->
<div class="login-wrapper">
    <div class="card login-card shadow-lg">

        <div class="login-logo"></div>
        <p class="login-subtitle">Sistema de Gestión<br><strong>Chocolate Tumaco</strong></p>

        <!-- El bloque de código PHP maneja la visualización de mensajes de error para informar a los usuarios sobre problemas durante el proceso de inicio de sesión. Si hay un mensaje de error específico (como 'login' para credenciales incorrectas o 'sesion' para expiración de sesión), se muestra una alerta de Bootstrap con el mensaje correspondiente. Si hay otro tipo de error, se muestra el mensaje directamente. Esto proporciona retroalimentación clara y útil a los usuarios, ayudándoles a entender por qué no pudieron iniciar sesión y qué acciones pueden tomar para resolver el problema. -->
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

        <!-- El formulario de inicio de sesión 
         permite a los usuarios ingresar su correo electrónico y contraseña para acceder al sistema. El formulario está diseñado con Bootstrap para una apariencia moderna y responsiva. Al enviar el formulario, se envía una solicitud POST al UsuarioController para procesar el inicio de sesión. Además, se manejan mensajes de error para informar a los usuarios sobre problemas como credenciales incorrectas o expiración de sesión, proporcionando una experiencia de usuario clara y amigable durante el proceso de autenticación. -->
        <form method="POST"
              action="/chocoTumac/controllers/UsuarioController.php?action=login"
              novalidate>

            <div class="mb-3">
                <label for="fld-email" class="form-label fw-semibold">Correo electrónico</label>
                <input id="fld-email" class="form-control" type="email" name="email"
                       placeholder="usuario@ejemplo.com"
                       autocomplete="username" required>
            </div>

            <div class="mb-4">
                <label for="fld-password" class="form-label fw-semibold">Contraseña</label>
                <input id="fld-password" class="form-control" type="password" name="password"
                       placeholder="••••••••"
                       autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn-ct-primary w-100 py-2 fw-semibold">
                Ingresar
            </button>
        </form>

    </div>
</div>

<!-- El código JavaScript al final del documento incluye la biblioteca de Bootstrap para manejar componentes interactivos como modales y alertas, así como un archivo personalizado app.js para funcionalidades específicas de la aplicación. Esto asegura que los componentes de la interfaz funcionen correctamente y que cualquier funcionalidad personalizada esté disponible para mejorar la experiencia del usuario durante el proceso de inicio de sesión en la aplicación. -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVzdl1" crossorigin="anonymous"></script>
<script src="/chocoTumac/public/js/app.js"></script>
</body>
</html>