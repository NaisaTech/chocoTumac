<?php
// Bloquear acceso directo a esta vista por URL
/* El bloque de código al inicio del archivo es una medida de seguridad para evitar que los usuarios accedan directamente a esta vista sin pasar por el controlador correspondiente. Verifica si la constante 'CHOCOTUMAC_APP' está definida, 
*lo que indica que la aplicación se ha inicializado correctamente. Si esta constante no está definida, se redirige al usuario a la página principal de la aplicación (index.php) y se detiene la ejecución del script. Esto ayuda a proteger la aplicación 
*contra accesos no autorizados y asegura que las vistas solo se carguen a través de los controladores adecuados. 
*/
if (!defined('CHOCOTUMAC_APP')) {
    header("Location: /chocoTumac/index.php");
    exit();
}
session_start();

// Prevenir caché del navegador
/* Estas cabeceras HTTP se utilizan para prevenir que el navegador almacene en caché esta página. Esto es importante para garantizar que los usuarios siempre vean la información más actualizada,
* especialmente después de realizar acciones como crear, editar o eliminar clientes. Al establecer estas cabeceras, se indica al navegador que no guarde una copia de la página y que siempre solicite 
*una nueva versión al servidor cada vez que se acceda a ella. Esto ayuda a evitar problemas de visualización de datos obsoletos y mejora la experiencia del usuario al interactuar con la aplicación. 
*/
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// Protección de ruta — redirigir si no hay sesión activa
/* Este bloque de código verifica si el usuario ha iniciado sesión antes de permitir el acceso a esta vista. Si no hay una sesión activa (es decir, si la variable de sesión 'user' no está establecida), se redirige al usuario a la página de inicio de sesión con un mensaje de error indicando que la sesión ha expirado. Esto es una medida de seguridad para proteger las rutas que requieren autenticación y garantizar que solo los usuarios autorizados puedan acceder a esta sección de la aplicación. */
if (!isset($_SESSION['user'])) {
    header("Location: /chocoTumac/index.php?view=login&error=" . urlencode("Tu sesión ha expirado. Inicia sesión nuevamente."));
    exit();
}

/* Se incluye el modelo de Cliente para poder interactuar con la base de datos y obtener la lista de clientes registrados. Luego, se crea una instancia del modelo y se llama al método obtener() para recuperar los datos de los clientes. Además, se obtiene el rol del usuario desde la sesión para controlar la visibilidad de ciertas funcionalidades en la vista. */
require_once 'models/Cliente.php';
$model    = new Cliente();
$clientes = $model->obtener();
$rol      = $_SESSION['user']['rol_id'];
?>

<!-- El bloque de código HTML a continuación es la estructura de la página de clientes. Se utiliza Bootstrap para el diseño y estilo de la página. La página incluye una barra de navegación, un formulario para registrar nuevos clientes (visible solo para ciertos roles) y una tabla que muestra la lista de clientes registrados. También se manejan mensajes de error y éxito para informar al usuario sobre las acciones realizadas. Además, se incluye un modal para confirmar la eliminación de un cliente. La página es responsive, lo que significa que se adapta a diferentes tamaños de pantalla para una mejor experiencia de usuario en dispositivos móviles. -->
<!DOCTYPE html>
<html lang="es">
<!-- El bloque de código al inicio del archivo es una medida de seguridad para evitar que los usuarios accedan directamente a esta vista sin pasar por el controlador correspondiente. Verifica si la constante 'CHOCOTUMAC_APP' está definida, lo que indica que la aplicación se ha inicializado correctamente. Si esta constante no está definida, se redirige al usuario a la página principal de la aplicación (index.php) y se detiene la ejecución del script. Esto ayuda a proteger la aplicación contra accesos no autorizados y asegura que las vistas solo se carguen a través de los controladores adecuados. -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clientes – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/chocoTumac/public/css/styles.css">
</head>
<body>
<?php require_once 'views/layout/navbar.php'; ?>

<div class="container mt-4">
    <!-- La sección de encabezado de la página muestra el título "Clientes" y, si el rol del usuario es 2 (Gerente), se muestra una etiqueta que indica que tiene acceso de solo lectura. Esto ayuda a los usuarios a identificar rápidamente su nivel de acceso y las funcionalidades disponibles en esta sección. -->
    <div class="page-header">
        <h2>Clientes</h2>
        <?php if ($rol == 2): ?>
            <span class="badge bg-info fs-6">Solo lectura</span>
        <?php endif; ?>
    </div>
    <!-- Mensajes de error o éxito -->
     <!-- Este bloque de código maneja la visualización de mensajes de error o éxito para informar al usuario sobre las acciones realizadas. Si hay un mensaje de error (indicado por la variable 'error' en la URL), se muestra una alerta de Bootstrap con el mensaje correspondiente. Si hay un mensaje de éxito (indicado por la variable 'msg' en la URL), se muestra una alerta con el tipo y texto correspondiente según el valor de 'msg'. Esto proporciona retroalimentación visual al usuario sobre el resultado de sus acciones, como crear, actualizar o eliminar clientes. -->
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-auto alert-dismissible" role="alert">
            <strong>Error:</strong> <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Este bloque de código maneja la visualización de mensajes de éxito para informar al usuario sobre las acciones realizadas. Si hay un mensaje de éxito (indicado por la variable 'msg' en la URL), se muestra una alerta con el tipo y texto correspondiente según el valor de 'msg'. Esto proporciona retroalimentación visual al usuario sobre el resultado de sus acciones, como crear, actualizar o eliminar clientes. -->
    <?php if (isset($_GET['msg'])): ?>
        <?php
        $msgs = [
            'creado'      => ['success', '✓ Cliente registrado correctamente.'],
            'actualizado' => ['success', '✓ Datos del cliente actualizados.'],
            'eliminado'   => ['warning', '✓ Cliente eliminado del sistema.'],
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
     <!-- formulario para registrar nuevos clientes. Este formulario solo se muestra para usuarios con rol de Administrador (1) o Empleado (3). 
     El formulario incluye campos para el nombre del cliente, tipo de documento, número de documento, dígito de verificación (solo para NIT), teléfono, correo electrónico, dirección, ciudad y departamento. Al enviar el formulario, 
     se envía una solicitud POST al ClienteController para crear un nuevo cliente en la base de datos. El formulario también incluye validaciones HTML5 para garantizar que los datos ingresados sean correctos antes de enviarlos al servidor. 
     Esto ayuda a mejorar la experiencia del usuario y a mantener la integridad de los datos en la aplicación. -->
    <?php if (in_array($rol, [1, 3])): ?>
    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Registrar Cliente</h5>
        <form method="POST" action="/chocoTumac/controllers/ClienteController.php?action=crear" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="row g-2 mb-2">
                <!-- Nombre -->
                <div class="col-md-4">
                    <label for="fld-nombre" class="form-label small fw-semibold">Nombre / Razón social <span class="text-danger">*</span></label>
                    <input id="fld-nombre" class="form-control" name="nombre" placeholder="Nombre completo o empresa" required minlength="2">
                    <div class="invalid-feedback">El nombre es obligatorio.</div>
                </div>
                <!-- Tipo doc -->
                <div class="col-md-2">
                    <label for="fld-tipo_doc" class="form-label small fw-semibold">Tipo doc. <span class="text-danger">*</span></label>
                    <select id="fld-tipo_doc" class="form-select" name="tipo_doc" id="tipo_doc_cli" required>
                        <option value="CC">CC</option>
                        <option value="NIT">NIT</option>
                        <option value="CE">CE</option>
                        <option value="Pasaporte">Pasaporte</option>
                    </select>
                </div>
                <!-- Número doc -->
                <div class="col-md-2">
                    <label for="fld-num_doc" class="form-label small fw-semibold">N° documento <span class="text-danger">*</span></label>
                    <input id="fld-num_doc" class="form-control" name="num_doc" placeholder="Ej: 800123456" required
                           pattern="[0-9\-]+" title="Solo números y guiones">
                    <div class="invalid-feedback">Ingresa el número de documento.</div>
                </div>
                <!-- Dígito verificación (solo NIT) -->
                <div class="col-md-1" id="div_digito_cli" style="display:none;">
                    <label for="fld-digito_ver" class="form-label small fw-semibold">DV</label>
                    <input id="fld-digito_ver" class="form-control" name="digito_ver" placeholder="0" maxlength="1"
                           pattern="[0-9]" title="Un solo dígito">
                </div>
                <!-- Teléfono -->
                <div class="col-md-2">
                    <label for="fld-telefono" class="form-label small fw-semibold">Teléfono</label>
                    <input id="fld-telefono" class="form-control" name="telefono" placeholder="Ej: 3001234567">
                </div>
            </div>

            <div class="row g-2 mb-3">
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
     <!-- La tabla muestra la lista de clientes registrados en el sistema. Cada fila de la tabla representa un cliente y muestra información como el nombre, tipo y número de documento, teléfono, correo electrónico, dirección, ciudad y departamento. Para usuarios con rol de Administrador (1) o Empleado (3), se muestran acciones adicionales para editar o eliminar clientes.
     El botón de editar redirige al usuario a un formulario prellenado con los datos del cliente para realizar modificaciones, mientras que el botón de eliminar muestra un modal de confirmación antes de proceder con la eliminación del cliente. Esta tabla proporciona una vista clara y organizada de los clientes registrados, facilitando su gestión por parte de los usuarios autorizados.
    -->
    <div class="card p-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Clientes Registrados</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre / Razón social</th>
                        <th>Identificación</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Dirección</th>
                        <th>Ciudad</th>
                        <th>Dpto.</th>
                        <?php if (in_array($rol, [1, 3])): ?>
                        <th class="text-center">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                $lista = $clientes->fetchAll(PDO::FETCH_ASSOC);
                if (empty($lista)):
                ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No hay clientes registrados aún.
                    </td>
                </tr>
                <?php else: foreach ($lista as $c): ?>
                <tr>
                    <td class="text-muted"><?= $i++ ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($c['nombre']) ?></td>
                    <td>
                        <span class="badge bg-secondary"><?= htmlspecialchars($c['tipo_doc']) ?></span>
                        <?= htmlspecialchars($c['num_doc']) ?>
                        <?php if ($c['tipo_doc'] === 'NIT' && $c['digito_ver'] !== null): ?>
                            <span class="text-muted">-<?= htmlspecialchars($c['digito_ver']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($c['telefono'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($c['email'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($c['direccion'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($c['ciudad'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($c['departamento'] ?? '—') ?></td>
                    <?php if (in_array($rol, [1, 3])): ?>
                    <td class="text-center">
                        <a class="btn btn-warning btn-sm"
                           href="/chocoTumac/controllers/ClienteController.php?action=editar&id=<?= $c['id'] ?>">
                            Editar
                        </a>
                        <?php if ($rol == 1): ?>
                        <button class="btn btn-danger btn-sm btn-confirmar-eliminar"
                                data-url="/chocoTumac/controllers/ClienteController.php?action=eliminar&id=<?= $c['id'] ?>"
                                data-nombre="<?= htmlspecialchars($c['nombre']) ?>">
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
 <!-- El modal de confirmación de eliminación se utiliza para evitar eliminaciones accidentales de clientes. Cuando un usuario con permisos para eliminar hace clic en el botón de eliminar, se muestra este modal que solicita al usuario que confirme su acción. El modal muestra el nombre del cliente que se va a eliminar para que el usuario pueda verificar que está eliminando el cliente correcto. 
  Si el usuario confirma la eliminación, se redirige a la URL especificada en el atributo 'data-url' del botón, donde se ejecuta la acción de eliminación en el controlador correspondiente. Si el usuario cancela, simplemente se cierra el modal sin realizar ninguna acción. Esta es una práctica común para mejorar la usabilidad y seguridad de las aplicaciones web al prevenir acciones irreversibles sin confirmación previa.
  -->
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
<!-- El modal de confirmación de eliminación se utiliza para evitar eliminaciones accidentales de clientes. Cuando un usuario con permisos para eliminar hace clic en el botón de eliminar, se muestra este modal que solicita al usuario que confirme su acción. El modal muestra el nombre del cliente que se va a eliminar para que el usuario pueda verificar que está eliminando el cliente correcto. 
 Si el usuario confirma la eliminación, se redirige a la URL especificada en el atributo 'data-url' del botón, donde se ejecuta la acción de eliminación en el controlador correspondiente. Si el usuario cancela, simplemente se cierra el modal sin realizar ninguna acción. Esta es una práctica común para mejorar la usabilidad y seguridad de las aplicaciones web al prevenir acciones irreversibles sin confirmación previa.
 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/chocoTumac/public/js/app.js"></script>
<script>
// Mostrar/ocultar campo dígito verificación según tipo doc
<!-- Este bloque de código JavaScript se encarga de mostrar u ocultar el campo de dígito de verificación en el formulario de registro de clientes según el tipo de documento seleccionado. Si el usuario selecciona "NIT" como tipo de documento, se muestra el campo para ingresar el dígito de verificación. Si se selecciona cualquier otro tipo de documento, este campo se oculta. Esto mejora la usabilidad del formulario al mostrar solo los campos relevantes según la selección del usuario, evitando confusiones y simplificando la interfaz. -->
document.getElementById('tipo_doc_cli')?.addEventListener('change', function () {
    document.getElementById('div_digito_cli').style.display =
        this.value === 'NIT' ? '' : 'none';
});
</script>
</body>
</html>