<?php
if (!isset($_SESSION['user']) || $_SESSION['user']['rol_id'] != 1) {
    header("Location: /chocoTumac/index.php?view=dashboard&error=" . urlencode("Acceso no permitido.")); exit();
}
?>
<!-- Este código es la plantilla para la página de edición de usuarios en la aplicación. Muestra un formulario prellenado con los datos del usuario seleccionado, permitiendo al administrador modificar la información y guardar los cambios. El formulario incluye campos para el nombre completo, correo electrónico, teléfono y rol del usuario. Al enviar el formulario, se envía una solicitud POST al UsuarioController para actualizar los datos del usuario en la base de datos. Además, se manejan mensajes de error y éxito para informar al administrador sobre el resultado de la acción realizada. En general, este código proporciona una interfaz clara y funcional para que los administradores puedan gestionar la información de los usuarios en la aplicación. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Usuario – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="/chocoTumac/public/css/styles.css">
</head>
<body>
<?php require_once __DIR__ . '/layout/navbar.php'; ?>

<!-- La sección de encabezado de la página muestra el título "Editar Usuario" y un botón para volver a la lista de usuarios. Esto proporciona una navegación clara para el usuario, permitiéndole regresar fácilmente a la vista principal de usuarios después de editar la información. El título indica claramente la acción que se está realizando, mientras que el botón de volver mejora la usabilidad al ofrecer una forma rápida de regresar sin necesidad de usar el navegador. -->
<div class="container mt-4" style="max-width:600px;">
    <div class="page-header">
        <h2>Editar Usuario</h2>
        <a href="/chocoTumac/index.php?view=dashboard" class="btn btn-sm btn-outline-secondary">← Volver</a>
    </div>

    <!-- El formulario para editar el usuario se muestra dentro de una tarjeta de Bootstrap para mejorar la presentación visual. El formulario está prellenado con los datos del usuario seleccionado, lo que facilita la edición de la información. Al enviar el formulario, se envía una solicitud POST al UsuarioController para actualizar los datos del usuario en la base de datos. Además, se manejan mensajes de error y éxito para informar al administrador sobre el resultado de la acción realizada. En general, este bloque proporciona una interfaz clara y funcional para que los administradores puedan gestionar la información de los usuarios en la aplicación. -->
    <div class="card p-4">
        <form method="POST" action="/chocoTumac/controllers/UsuarioController.php?action=actualizar" data-validate>
            <input type="hidden" name="id"         value="<?= $usuario['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Este bloque maneja los campos para editar la información del usuario, como el nombre completo, correo electrónico, teléfono y rol. Estos campos son esenciales para la gestión de usuarios en la aplicación, ya que permiten mantener actualizada la información de contacto y el rol de cada usuario. Al enviar el formulario, se envía una solicitud POST al UsuarioController para actualizar estos datos en la base de datos, asegurando que la información del usuario esté actualizada y sea precisa. Esto facilita la administración de usuarios y garantiza que los datos reflejen correctamente la información actual de cada usuario en la aplicación. -->
            <div class="mb-3">
                <label for="fld-nombre" class="form-label fw-semibold">Nombre completo</label>
                <input id="fld-nombre" class="form-control" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required minlength="2">
                <div class="invalid-feedback">El nombre es obligatorio.</div>
            </div>
            <div class="mb-3">
                <label for="fld-email" class="form-label fw-semibold">Correo electrónico</label>
                <input id="fld-email" class="form-control" type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                <div class="invalid-feedback">Ingresa un correo válido.</div>
            </div>
            <div class="mb-3">
                <label for="fld-telefono" class="form-label fw-semibold">Teléfono</label>
                <input id="fld-telefono" class="form-control" name="telefono" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>" placeholder="3001234567">
            </div>
            <div class="mb-4">
                <label for="fld-rol_id" class="form-label fw-semibold">Rol</label>
                <select id="fld-rol_id" class="form-select" name="rol_id" required>
                    <option value="1" <?= $usuario['rol_id'] == 1 ? 'selected' : '' ?>>Administrador</option>
                    <option value="2" <?= $usuario['rol_id'] == 2 ? 'selected' : '' ?>>Gerente</option>
                    <option value="3" <?= $usuario['rol_id'] == 3 ? 'selected' : '' ?>>Empleado</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-ct-primary px-4">Guardar cambios</button>
                <a href="/chocoTumac/index.php?view=dashboard" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<!-- El código JavaScript al final del documento incluye la biblioteca de Bootstrap para manejar componentes interactivos como modales y alertas, así como un archivo personalizado app.js para funcionalidades específicas de la aplicación. Esto asegura que los componentes de la interfaz funcionen correctamente y que cualquier funcionalidad personalizada esté disponible para mejorar la experiencia del usuario al editar la información del usuario en la aplicación. -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVzdl1" crossorigin="anonymous"></script>
<script src="/chocoTumac/public/js/app.js"></script>
</body>
</html>