<!-- Este código es la plantilla para la página de edición de proveedores en la aplicación. 
 Muestra un formulario prellenado con los datos del proveedor seleccionado, permitiendo al usuario modificar la información 
 y guardar los cambios. El formulario incluye campos para el nombre, tipo y número de documento, tipo de proveedor, 
 persona de contacto, teléfono, correo electrónico, dirección, ciudad y departamento del proveedor. Al enviar el formulario, 
 se envía una solicitud POST al ProveedorController para actualizar los datos del proveedor en la base de datos. Además, 
 se manejan mensajes de error y éxito para informar al usuario sobre el resultado de la acción realizada. En general, 
 este código proporciona una interfaz clara y funcional para editar la información de los proveedores en la aplicación. 
 -->
<?php
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['rol_id'], [1, 3])) {
    header("Location: /chocoTumac/index.php?view=proveedores&error=" . urlencode("Acceso no permitido.")); exit();
}
?>
<!-- El bloque de código PHP al inicio verifica si el usuario ha iniciado sesión y tiene un rol permitido (Administrador o Empleado) para acceder a la página de edición de proveedores. 
 Si el usuario no cumple con estas condiciones, se redirige a la lista de proveedores con un mensaje de error indicando que el acceso no está permitido. Esto es una medida de seguridad para restringir el acceso a esta funcionalidad solo a usuarios autorizados. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Proveedor – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="/chocoTumac/public/css/styles.css">
</head>
<body>
<?php require_once __DIR__ . '/layout/navbar.php'; ?>
<!-- La sección de encabezado de la página muestra el título "Editar Proveedor" y un botón para volver a la lista de proveedores. Esto proporciona una navegación clara para el usuario, permitiéndole regresar fácilmente a la vista principal de proveedores después de editar la información. El título indica claramente la acción que se está realizando, mientras que el botón de volver mejora la usabilidad al ofrecer una forma rápida de regresar sin necesidad de usar el navegador. -->
<div class="container mt-4" style="max-width:800px;">
    <div class="page-header">
        <h2>Editar Proveedor</h2>
        <a href="/chocoTumac/index.php?view=proveedores" class="btn btn-sm btn-outline-secondary">← Volver</a>
    </div>

    <div class="card p-4">
        <form method="POST" action="/chocoTumac/controllers/ProveedorController.php?action=actualizar" data-validate>
            <input type="hidden" name="id"         value="<?= $proveedor['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Este bloque maneja los datos obligatorios para identificar al proveedor, como el nombre, tipo y número de documento, tipo de proveedor y dígito de verificación (si aplica). Estos campos son esenciales para la gestión de proveedores en la aplicación, ya que permiten identificar de manera única a cada proveedor y clasificarlo según su tipo. Al enviar el formulario, se envía una solicitud POST al ProveedorController para actualizar estos datos en la base de datos. Esto asegura que la información crítica del proveedor esté actualizada y sea precisa. -->
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="fld-nombre" class="form-label fw-semibold">Nombre / Razón social <span class="text-danger">*</span></label>
                    <input id="fld-nombre" class="form-control" name="nombre" value="<?= htmlspecialchars($proveedor['nombre']) ?>" required minlength="2">
                    <div class="invalid-feedback">El nombre es obligatorio.</div>
                </div>
                <div class="col-md-2">
                    <label for="fld-tipo_doc" class="form-label fw-semibold">Tipo doc. <span class="text-danger">*</span></label>
                    <select id="fld-tipo_doc" class="form-select" name="tipo_doc" id="tipo_doc_prov_edit" required>
                        <?php foreach (['CC','NIT','CE','Pasaporte'] as $t): ?>
                        <option value="<?= $t ?>" <?= $proveedor['tipo_doc'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fld-num_doc" class="form-label fw-semibold">N° documento <span class="text-danger">*</span></label>
                    <input id="fld-num_doc" class="form-control" name="num_doc" value="<?= htmlspecialchars($proveedor['num_doc']) ?>"
                           required pattern="[0-9\-]+" title="Solo números y guiones">
                    <div class="invalid-feedback">Obligatorio.</div>
                </div>
                <!-- El campo de dígito de verificación (DV) solo se muestra si el tipo de documento seleccionado es "NIT". Esto se maneja mediante JavaScript que muestra u oculta el campo según la selección del usuario. El DV es un número adicional utilizado para validar el NIT en Colombia, por lo que solo es relevante para proveedores que utilizan este tipo de documento. Al incluir esta lógica, se mejora la usabilidad del formulario al mostrar solo los campos relevantes según la selección del usuario. -->
                <div class="col-md-1" id="div_dv_prov_edit" style="display:<?= $proveedor['tipo_doc'] === 'NIT' ? '' : 'none' ?>">
                    <label for="fld-digito_ver" class="form-label fw-semibold">DV</label>
                    <input id="fld-digito_ver" class="form-control" name="digito_ver" value="<?= htmlspecialchars($proveedor['digito_ver'] ?? '') ?>"
                           maxlength="1" pattern="[0-9]">
                </div>
                <!-- El campo de tipo de proveedor es obligatorio y permite clasificar al proveedor según su naturaleza, como agricultor, intermediario, cooperativa o empresa. Esta clasificación es importante para la gestión de proveedores en la aplicación, ya que puede influir en cómo se manejan las relaciones comerciales y los procesos asociados a cada tipo de proveedor. Al enviar el formulario, se envía una solicitud POST al ProveedorController para actualizar esta información en la base de datos, asegurando que la clasificación del proveedor esté actualizada y sea precisa. -->
                <div class="col-md-3">
                    <label for="fld-tipo_proveedor" class="form-label fw-semibold">Tipo proveedor <span class="text-danger">*</span></label>
                    <select id="fld-tipo_proveedor" class="form-select" name="tipo_proveedor" required>
                        <?php foreach (['Agricultor','Intermediario','Cooperativa','Empresa'] as $tp): ?>
                        <option value="<?= $tp ?>" <?= $proveedor['tipo_proveedor'] === $tp ? 'selected' : '' ?>><?= $tp ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Este bloque muestra los campos adicionales para editar la información de contacto y ubicación del proveedor, como la persona de contacto, teléfono, correo electrónico, dirección, ciudad y departamento. Estos campos son opcionales pero proporcionan información valiosa para la gestión de proveedores. Al enviar el formulario, se envía una solicitud POST al ProveedorController para actualizar estos datos en la base de datos, asegurando que la información de contacto y ubicación del proveedor esté actualizada y sea precisa. Esto facilita la comunicación y coordinación con los proveedores en futuras interacciones comerciales. -->
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="fld-persona_contacto" class="form-label fw-semibold">Persona de contacto</label>
                    <input id="fld-persona_contacto" class="form-control" name="persona_contacto" value="<?= htmlspecialchars($proveedor['persona_contacto'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="fld-telefono" class="form-label fw-semibold">Teléfono</label>
                    <input id="fld-telefono" class="form-control" name="telefono" value="<?= htmlspecialchars($proveedor['telefono'] ?? '') ?>">
                </div>
                <div class="col-md-5">
                    <label for="fld-email" class="form-label fw-semibold">Correo electrónico</label>
                    <input id="fld-email" class="form-control" type="email" name="email" value="<?= htmlspecialchars($proveedor['email'] ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="fld-direccion" class="form-label fw-semibold">Dirección</label>
                    <input id="fld-direccion" class="form-control" name="direccion" value="<?= htmlspecialchars($proveedor['direccion'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="fld-ciudad" class="form-label fw-semibold">Ciudad</label>
                    <input id="fld-ciudad" class="form-control" name="ciudad" value="<?= htmlspecialchars($proveedor['ciudad'] ?? '') ?>" placeholder="Ej: Tumaco">
                </div>
                <div class="col-md-4">
                    <label for="fld-departamento" class="form-label fw-semibold">Departamento</label>
                    <input id="fld-departamento" class="form-control" name="departamento" value="<?= htmlspecialchars($proveedor['departamento'] ?? '') ?>" placeholder="Ej: Nariño">
                </div>
            </div>

            <!-- Al final del formulario, se muestran dos botones: uno para guardar los cambios realizados en la información del proveedor y otro para cancelar la edición y volver a la lista de proveedores. El botón de guardar envía el formulario al ProveedorController para procesar la actualización, mientras que el botón de cancelar redirige al usuario de vuelta a la vista principal de proveedores sin realizar ningún cambio. Esto proporciona una navegación clara y opciones para que el usuario decida si desea guardar los cambios o no. -->
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-ct-primary px-4">Guardar cambios</button>
                <a href="/chocoTumac/index.php?view=proveedores" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<!-- El código JavaScript al final del documento incluye la biblioteca de Bootstrap para manejar componentes interactivos como modales y alertas, así como un archivo personalizado app.js para funcionalidades específicas de la aplicación. Además, se agrega un script para mostrar u ocultar el campo de dígito de verificación (DV) dependiendo del tipo de documento seleccionado. Si el usuario selecciona "NIT" como tipo de documento, el campo DV se muestra; de lo contrario, se oculta. Esto mejora la usabilidad del formulario al mostrar solo los campos relevantes según la selección del usuario. -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVzdl1" crossorigin="anonymous"></script>
<script src="/chocoTumac/public/js/app.js"></script>
<script>
document.getElementById('tipo_doc_prov_edit')?.addEventListener('change', function () {
    document.getElementById('div_dv_prov_edit').style.display = this.value === 'NIT' ? '' : 'none';
});
</script>
</body>
</html>