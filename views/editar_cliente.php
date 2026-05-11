<!-- Este bloque de código maneja la visualización de mensajes de error o éxito para informar al usuario sobre las acciones realizadas. Si hay un mensaje de error (indicado por la variable 'error' en la URL), se muestra una alerta de Bootstrap con el mensaje correspondiente. Si hay un mensaje de éxito (indicado por la variable 'msg' en la URL), se muestra una alerta con el tipo y texto correspondiente según el valor de 'msg'. Esto proporciona retroalimentación visual al usuario sobre el resultado de sus acciones, como crear, actualizar o eliminar clientes. */
<?php
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['rol_id'], [1, 3])) {
    header("Location: /chocoTumac/index.php?view=clientes&error=" . urlencode("Acceso no permitido.")); exit();
}
?>


<!-- Este código es la plantilla para la página de edición de clientes en la aplicación. Muestra un formulario prellenado
  con los datos del cliente seleccionado, permitiendo al usuario modificar la información y guardar los cambios. 
  El formulario incluye campos para el nombre, tipo y número de documento, teléfono, correo electrónico, dirección, c
  iudad y departamento del cliente. Al enviar el formulario, se envía una solicitud POST al ClienteController para actualizar 
  los datos del cliente en la base de datos. Además, se manejan mensajes de error y éxito para informar al usuario sobre el 
  resultado de la acción realizada. En general, este código proporciona una interfaz clara 
  y funcional para editar la información de los clientes en la aplicación. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Cliente – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="/chocoTumac/public/css/styles.css">
</head>
<body>
<?php require_once __DIR__ . '/layout/navbar.php'; ?>
<div class="container mt-4" style="max-width:760px;">
    <!-- La sección de encabezado de la página muestra el título "Editar Cliente" y un botón para volver a la lista de clientes. Esto proporciona una navegación clara para el usuario, permitiéndole regresar fácilmente a la vista principal de clientes después de editar la información. El título indica claramente la acción que se está realizando, mientras que el botón de volver mejora la usabilidad al ofrecer una forma rápida de regresar sin necesidad de usar el navegador. -->
    <div class="page-header">
        <h2>Editar Cliente</h2>
        <a href="/chocoTumac/index.php?view=clientes" class="btn btn-sm btn-outline-secondary">← Volver</a>
    </div>
    <div class="card p-4">
        <form method="POST" action="/chocoTumac/controllers/ClienteController.php?action=actualizar" data-validate>
            <input type="hidden" name="id"         value="<?= $cliente['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Este bloque de código maneja la visualización de mensajes de error o éxito para informar al usuario sobre las acciones realizadas. Si hay un mensaje de error (indicado por la variable 'error' en la URL), se muestra una alerta de Bootstrap con el mensaje correspondiente. Si hay un mensaje de éxito (indicado por la variable 'msg' en la URL), se muestra una alerta con el tipo y texto correspondiente según el valor de 'msg'. Esto proporciona retroalimentación visual al usuario sobre el resultado de sus acciones, como actualizar los datos del cliente. La alerta es automática y se puede cerrar manualmente por el usuario. -->
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="fld-nombre" class="form-label fw-semibold">Nombre / Razón social <span class="text-danger">*</span></label>
                    <input id="fld-nombre" class="form-control" name="nombre" value="<?= htmlspecialchars($cliente['nombre']) ?>" required minlength="2">
                    <div class="invalid-feedback">El nombre es obligatorio.</div>
                </div>
                <div class="col-md-2">
                    <label for="fld-tipo_doc" class="form-label fw-semibold">Tipo doc. <span class="text-danger">*</span></label>
                    <select id="fld-tipo_doc" class="form-select" name="tipo_doc" id="tipo_doc_edit" required>
                        <?php foreach (['CC','NIT','CE','Pasaporte'] as $t): ?>
                        <option value="<?= $t ?>" <?= $cliente['tipo_doc'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fld-num_doc" class="form-label fw-semibold">N° documento <span class="text-danger">*</span></label>
                    <input id="fld-num_doc" class="form-control" name="num_doc" value="<?= htmlspecialchars($cliente['num_doc']) ?>"
                           required pattern="[0-9\-]+" title="Solo números y guiones">
                    <div class="invalid-feedback">Obligatorio.</div>
                </div>
                <div class="col-md-2" id="div_dv_edit" style="display:<?= $cliente['tipo_doc'] === 'NIT' ? '' : 'none' ?>">
                    <label for="fld-digito_ver" class="form-label fw-semibold">DV</label>
                    <input id="fld-digito_ver" class="form-control" name="digito_ver" value="<?= htmlspecialchars($cliente['digito_ver'] ?? '') ?>"
                           maxlength="1" pattern="[0-9]">
                </div>
            </div>

            <!-- Este bloque de código muestra los campos adicionales para editar la información del cliente, como el teléfono, correo electrónico, dirección, ciudad y departamento. Estos campos son opcionales y permiten al usuario actualizar la información de contacto y ubicación del cliente. Al enviar el formulario, se envía una solicitud POST al ClienteController para actualizar los datos del cliente en la base de datos. Esto proporciona una interfaz completa para editar toda la información relevante del cliente en un solo lugar. -->
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="fld-telefono" class="form-label fw-semibold">Teléfono</label>
                    <input id="fld-telefono" class="form-control" name="telefono" value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>" placeholder="3001234567">
                </div>
                <div class="col-md-4">
                    <label for="fld-email" class="form-label fw-semibold">Correo electrónico</label>
                    <input id="fld-email" class="form-control" type="email" name="email" value="<?= htmlspecialchars($cliente['email'] ?? '') ?>">
                </div>
                <div class="col-md-5">
                    <label for="fld-direccion" class="form-label fw-semibold">Dirección</label>
                    <input id="fld-direccion" class="form-control" name="direccion" value="<?= htmlspecialchars($cliente['direccion'] ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="fld-ciudad" class="form-label fw-semibold">Ciudad</label>
                    <input id="fld-ciudad" class="form-control" name="ciudad" value="<?= htmlspecialchars($cliente['ciudad'] ?? '') ?>" placeholder="Ej: Tumaco">
                </div>
                <div class="col-md-4">
                    <label for="fld-departamento" class="form-label fw-semibold">Departamento</label>
                    <input id="fld-departamento" class="form-control" name="departamento" value="<?= htmlspecialchars($cliente['departamento'] ?? '') ?>" placeholder="Ej: Nariño">
                </div>
            </div>

            <!-- Al final del formulario, se muestran dos botones: uno para guardar los cambios realizados en la información del cliente y otro para cancelar la edición y volver a la lista de clientes. El botón de guardar envía el formulario al ClienteController para procesar la actualización, mientras que el botón de cancelar redirige al usuario de vuelta a la vista principal de clientes sin realizar ningún cambio. Esto proporciona una navegación clara y opciones para que el usuario decida si desea guardar los cambios o no. -->
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-ct-primary px-4">Guardar cambios</button>
                <a href="/chocoTumac/index.php?view=clientes" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<!-- El código JavaScript al final del documento incluye la biblioteca de Bootstrap para manejar componentes interactivos como modales y alertas, así como un archivo personalizado app.js para funcionalidades específicas de la aplicación. Además, se agrega un script para mostrar u ocultar el campo de dígito de verificación (DV) dependiendo del tipo de documento seleccionado. Si el usuario selecciona "NIT" como tipo de documento, el campo DV se muestra; de lo contrario, se oculta. Esto mejora la usabilidad del formulario al mostrar solo los campos relevantes según la selección del usuario. -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVzdl1" crossorigin="anonymous"></script>
<script src="/chocoTumac/public/js/app.js"></script>
<script>
document.getElementById('tipo_doc_edit')?.addEventListener('change', function () {
    document.getElementById('div_dv_edit').style.display = this.value === 'NIT' ? '' : 'none';
});
</script>
</body>
</html>