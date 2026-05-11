<?php
/*Bloquear acceso directo a esta vista por URL*/
if (!defined('CHOCOTUMAC_APP')) {
    header("Location: /chocoTumac/index.php");
    exit();
}
session_start();

/* Prevenir caché del navegador */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

/*Protección de ruta — redirigir si no hay sesión activa*/
if (!isset($_SESSION['user'])) {
    header("Location: /chocoTumac/index.php?view=login&error=" . urlencode("Tu sesión ha expirado. Inicia sesión nuevamente."));
    exit();
}

require_once 'models/Proveedor.php';
$model       = new Proveedor();
$proveedores = $model->obtener();
$rol         = $_SESSION['user']['rol_id'];
?>
<!-- Este código es la plantilla para la página de proveedores en la aplicación Chocolate Tumaco. Muestra una lista de proveedores registrados en el sistema, con opciones para registrar nuevos proveedores, editar o eliminar los existentes, dependiendo del rol del usuario. El diseño está basado en Bootstrap para una apariencia moderna y responsiva. El código también maneja mensajes de error y éxito para informar al usuario sobre el resultado de sus acciones, proporcionando una experiencia de usuario clara y amigable al gestionar los proveedores en la aplicación. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proveedores – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/chocoTumac/public/css/styles.css">
</head>
<body>
<?php require_once 'views/layout/navbar.php'; ?>

<!-- La sección de encabezado de la página muestra el título "Proveedores de Cacao" y, si el usuario tiene un rol de solo lectura (rol_id = 2), se muestra una etiqueta indicando "Solo lectura". Esto proporciona una navegación clara para el usuario, indicando la sección actual de la aplicación y cualquier restricción de acceso que pueda tener según su rol. El título indica claramente el contenido de la página, mientras que la etiqueta de solo lectura informa al usuario sobre las limitaciones en sus acciones dentro de esta sección. -->
<div class="container mt-4">

    <div class="page-header">
        <h2>Proveedores de Cacao</h2>
        <?php if ($rol == 2): ?>
            <span class="badge bg-info fs-6">Solo lectura</span>
        <?php endif; ?>
    </div>

    <!-- El bloque de código PHP maneja la visualización de mensajes de error o éxito para informar al usuario sobre las acciones realizadas. Si hay un mensaje de error (indicado por la variable 'error' en la URL), se muestra una alerta de Bootstrap con el mensaje correspondiente. Si hay un mensaje de éxito (indicado por la variable 'msg' en la URL), se muestra una alerta con el tipo y texto correspondiente según el valor de 'msg'. Esto proporciona retroalimentación visual al usuario sobre el resultado de sus acciones, como crear, actualizar o eliminar proveedores. La alerta es automática y se puede cerrar manualmente por el usuario. -->
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-auto alert-dismissible" role="alert">
            <strong>Error:</strong> <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg'])): ?>
        <?php
        $msgs = [
            'creado'      => ['success', '✓ Proveedor registrado correctamente.'],
            'actualizado' => ['success', '✓ Datos del proveedor actualizados.'],
            'eliminado'   => ['warning', '✓ Proveedor eliminado del sistema.'],
        ];
        if (isset($msgs[$_GET['msg']])):
            [$tipo, $texto] = $msgs[$_GET['msg']];
        ?>
        <div class="alert alert-<?= $tipo ?> alert-auto alert-dismissible" role="alert">
            <?= $texto ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- FORMULARIO REGISTRAR -->
    <!-- Este bloque de código muestra un formulario para registrar nuevos proveedores, pero solo si el usuario tiene un rol que lo permita (rol_id 1 o 3). El formulario incluye campos para el nombre, tipo y número de documento, tipo de proveedor, persona de contacto, teléfono, correo electrónico, dirección, ciudad y departamento del proveedor. Al enviar el formulario, se envía una solicitud POST al ProveedorController para crear un nuevo proveedor en la base de datos. Además, se manejan mensajes de error y éxito para informar al usuario sobre el resultado de la acción realizada. En general, este código proporciona una interfaz clara y funcional para agregar nuevos proveedores al sistema. -->
    <?php if (in_array($rol, [1, 3])): ?>
    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Registrar Proveedor</h5>
        <form method="POST" action="/chocoTumac/controllers/ProveedorController.php?action=crear" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="row g-2 mb-2">
                <!-- Nombre -->
                <div class="col-md-3">
                    <label for="fld-nombre" class="form-label small fw-semibold">Nombre / Razón social <span class="text-danger">*</span></label>
                    <input id="fld-nombre" class="form-control" name="nombre" placeholder="Nombre o empresa" required minlength="2">
                    <div class="invalid-feedback">El nombre es obligatorio.</div>
                </div>
                <!-- Tipo doc -->
                <div class="col-md-2">
                    <label for="fld-tipo_doc" class="form-label small fw-semibold">Tipo doc. <span class="text-danger">*</span></label>
                    <select id="fld-tipo_doc" class="form-select" name="tipo_doc" id="tipo_doc_prov" required>
                        <option value="CC">CC</option>
                        <option value="NIT">NIT</option>
                        <option value="CE">CE</option>
                        <option value="Pasaporte">Pasaporte</option>
                    </select>
                </div>
                <!-- Número doc -->
                <div class="col-md-2">
                    <label for="fld-num_doc" class="form-label small fw-semibold">N° documento <span class="text-danger">*</span></label>
                    <input id="fld-num_doc" class="form-control" name="num_doc" placeholder="Ej: 12345678" required
                           pattern="[0-9\-]+" title="Solo números y guiones">
                    <div class="invalid-feedback">Ingresa el número de documento.</div>
                </div>
                <!-- DV NIT -->
                <div class="col-md-1" id="div_digito_prov" style="display:none;">
                    <label for="fld-digito_ver" class="form-label small fw-semibold">DV</label>
                    <input id="fld-digito_ver" class="form-control" name="digito_ver" placeholder="0" maxlength="1" pattern="[0-9]">
                </div>
                <!-- Tipo proveedor -->
                <div class="col-md-2">
                    <label for="fld-tipo_proveedor" class="form-label small fw-semibold">Tipo proveedor <span class="text-danger">*</span></label>
                    <select id="fld-tipo_proveedor" class="form-select" name="tipo_proveedor" required>
                        <option value="Agricultor">Agricultor</option>
                        <option value="Intermediario">Intermediario</option>
                        <option value="Cooperativa">Cooperativa</option>
                        <option value="Empresa">Empresa</option>
                    </select>
                </div>
                <!-- Persona de contacto -->
                <div class="col-md-2">
                    <label for="fld-persona_contacto" class="form-label small fw-semibold">Persona de contacto</label>
                    <input id="fld-persona_contacto" class="form-control" name="persona_contacto" placeholder="Nombre contacto">
                </div>
            </div>

            <div class="row g-2 mb-3">
                <!-- Teléfono -->
                <div class="col-md-2">
                    <label for="fld-telefono" class="form-label small fw-semibold">Teléfono</label>
                    <input id="fld-telefono" class="form-control" name="telefono" placeholder="3001234567">
                </div>
                <!-- Email -->
                <div class="col-md-3">
                    <label for="fld-email" class="form-label small fw-semibold">Correo electrónico</label>
                    <input id="fld-email" class="form-control" type="email" name="email" placeholder="correo@ejemplo.com">
                </div>
                <!-- Dirección -->
                <div class="col-md-3">
                    <label for="fld-direccion" class="form-label small fw-semibold">Dirección</label>
                    <input id="fld-direccion" class="form-control" name="direccion" placeholder="Dirección">
                </div>
                <!-- Ciudad -->
                <div class="col-md-2">
                    <label for="fld-ciudad" class="form-label small fw-semibold">Ciudad</label>
                    <input id="fld-ciudad" class="form-control" name="ciudad" placeholder="Ej: Tumaco">
                </div>
                <!-- Departamento -->
                <div class="col-md-2">
                    <label for="fld-departamento" class="form-label small fw-semibold">Departamento</label>
                    <input id="fld-departamento" class="form-control" name="departamento" placeholder="Ej: Nariño">
                </div>
                <div class="col-md-auto d-flex align-items-end">
                    <button class="btn btn-ct-primary px-4">Registrar</button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- TABLA -->
    <!-- Este bloque de código muestra una tabla con la lista de proveedores registrados en el sistema. La tabla incluye columnas para el nombre, identificación, tipo de proveedor, persona de contacto, teléfono, correo electrónico, dirección, ciudad y departamento de cada proveedor. Si el usuario tiene un rol que lo permita (rol_id 1 o 3), también se muestran acciones para editar o eliminar cada proveedor. La tabla es responsiva y utiliza Bootstrap para un diseño limpio y funcional. Si no hay proveedores registrados, se muestra un mensaje indicando que la lista está vacía. En general, este código proporciona una vista clara y organizada de los proveedores en el sistema, facilitando su gestión por parte del usuario. -->
    <div class="card p-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Proveedores Registrados</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre / Razón social</th>
                        <th>Identificación</th>
                        <th>Tipo</th>
                        <th>Persona contacto</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Dirección</th>
                        <th>Ciudad</th>
                        <th>Departamento</th>
                        <?php if (in_array($rol, [1, 3])): ?>
                        <th class="text-center">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                $lista = $proveedores->fetchAll(PDO::FETCH_ASSOC);
                $badge_tipo = [
                    'Agricultor'   => 'bg-success',
                    'Intermediario'=> 'bg-warning text-dark',
                    'Cooperativa'  => 'bg-primary',
                    'Empresa'      => 'bg-dark',
                ];
                if (empty($lista)):
                ?>
                <!-- Si no hay proveedores registrados, se muestra una fila que indica que la lista está vacía. Esto proporciona una retroalimentación visual al usuario, informándole que aún no se han agregado proveedores al sistema. La fila ocupa todas las columnas de la tabla y utiliza un estilo de texto atenuado para diferenciarla de las filas con datos reales. -->
                <tr>
                    <td colspan="11" class="text-center text-muted py-4">
                        No hay proveedores registrados aún.
                    </td>
                </tr>
                <?php else: foreach ($lista as $p): ?>
                <tr>
                    <td class="text-muted"><?= $i++ ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($p['nombre']) ?></td>
                    <td>
                        <span class="badge bg-secondary"><?= htmlspecialchars($p['tipo_doc']) ?></span>
                        <?= htmlspecialchars($p['num_doc']) ?>
                        <?php if ($p['tipo_doc'] === 'NIT' && $p['digito_ver'] !== null): ?>
                            <span class="text-muted">-<?= htmlspecialchars($p['digito_ver']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $badge_tipo[$p['tipo_proveedor']] ?? 'bg-secondary' ?>">
                            <?= htmlspecialchars($p['tipo_proveedor']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($p['persona_contacto'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['telefono'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['email'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['direccion'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['ciudad'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['departamento'] ?? '—') ?></td>
                    <?php if (in_array($rol, [1, 3])): ?>
                    <td class="text-center">
                        <a class="btn btn-warning btn-sm"
                           href="/chocoTumac/controllers/ProveedorController.php?action=editar&id=<?= $p['id'] ?>">
                            Editar
                        </a>
                        <!-- Solo los usuarios con rol_id 1 pueden eliminar proveedores. El botón de eliminación incluye atributos de datos para manejar la confirmación de eliminación mediante un modal. Esto proporciona una capa adicional de seguridad, evitando eliminaciones accidentales al requerir una confirmación explícita por parte del usuario antes de proceder con la acción de eliminación. -->
                        <?php if ($rol == 1): ?>
                        <button class="btn btn-danger btn-sm btn-confirmar-eliminar"
                                data-url="/chocoTumac/controllers/ProveedorController.php?action=eliminar&id=<?= $p['id'] ?>"
                                data-nombre="<?= htmlspecialchars($p['nombre']) ?>">
                             Eliminar
                        </button>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal confirmar eliminación -->
<!-- Este código define un modal de Bootstrap que se utiliza para confirmar la eliminación de un proveedor. Cuando un usuario hace clic en el botón de eliminar, se muestra este modal con un mensaje que solicita confirmación para eliminar el proveedor específico. El modal incluye un botón para cancelar la acción y otro para confirmar la eliminación, que redirige a la URL correspondiente para realizar la acción de eliminación en el servidor. Esto proporciona una experiencia de usuario segura y clara, evitando eliminaciones accidentales al requerir una confirmación explícita antes de proceder. -->
<div class="modal fade" id="modalConfirmarEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger"> Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalEliminarTexto"></div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a id="modalEliminarLink" href="#" class="btn btn-danger">Sí, eliminar</a>
            </div>
        </div>
    </div>
</div>

<!-- El código JavaScript al final del documento incluye la biblioteca de Bootstrap para manejar componentes interactivos como modales y alertas, así como un archivo personalizado app.js para funcionalidades específicas de la aplicación. Esto asegura que los componentes de la interfaz funcionen correctamente y que cualquier funcionalidad personalizada esté disponible para mejorar la experiencia del usuario al gestionar los proveedores en la aplicación. -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/chocoTumac/public/js/app.js"></script>
<script>
document.getElementById('tipo_doc_prov')?.addEventListener('change', function () {
    document.getElementById('div_digito_prov').style.display =
        this.value === 'NIT' ? '' : 'none';
});
</script>
</body>
</html>