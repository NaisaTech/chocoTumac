<?php
// Bloquear acceso directo a esta vista por URL
/* Este bloque de código se encarga de proteger la vista del dashboard para que solo los usuarios autenticados puedan acceder a ella. Primero, verifica si la constante 'CHOCOTUMAC_APP' está definida, lo que indica que la aplicación ha sido cargada correctamente. Si no está definida, redirige al usuario a la página principal de la aplicación. Luego, inicia una sesión para manejar la autenticación del usuario. A continuación, se envían encabezados HTTP para prevenir el almacenamiento en caché del navegador, lo que ayuda a garantizar que los usuarios vean siempre la información más actualizada y evita problemas relacionados con sesiones expiradas o datos obsoletos. Finalmente, verifica si hay una sesión activa para el usuario; si no la hay, redirige al usuario a la página de inicio de sesión con un mensaje de error indicando que su sesión ha expirado. Esto asegura que solo los usuarios autenticados puedan acceder al dashboard y protege la información sensible de la aplicación.
 */
if (!defined('CHOCOTUMAC_APP')) {
    header("Location: /chocoTumac/index.php");
    exit();
}
session_start();

// Prevenir caché del navegador
/* Estos encabezados HTTP se utilizan para prevenir el almacenamiento en caché de la página por parte del navegador. Esto es importante para garantizar que los usuarios vean siempre la información más actualizada, especialmente en aplicaciones donde los datos pueden cambiar con frecuencia o donde la seguridad es una preocupación. Al establecer "Cache-Control" con "no-store, no-cache, must-revalidate, max-age=0", se indica al navegador que no almacene ni cachee la página. El encabezado "Pragma: no-cache" es una medida adicional para compatibilidad con navegadores más antiguos. Finalmente, el encabezado "Expires" se establece en una fecha pasada para asegurar que el contenido se considere expirado inmediatamente. En conjunto, estos encabezados ayudan a mejorar la seguridad y la experiencia del usuario al evitar problemas relacionados con sesiones expiradas o datos obsoletos.
 */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// Protección de ruta — redirigir si no hay sesión activa
/* Este bloque de código verifica si hay una sesión activa para el usuario. Si no se encuentra una sesión activa (es decir, si el usuario no ha iniciado sesión), se redirige al usuario a la página de inicio de sesión con un mensaje de error indicando que su sesión ha expirado. Esto es una medida de seguridad para proteger la información sensible del dashboard y garantizar que solo los usuarios autenticados puedan acceder a esta sección de la aplicación. Al redirigir a los usuarios no autenticados, se evita el acceso no autorizado y se mejora la seguridad general de la aplicación.
 */
if (!isset($_SESSION['user'])) {
    header("Location: /chocoTumac/index.php?view=login&error=" . urlencode("Tu sesión ha expirado. Inicia sesión nuevamente."));
    exit();
}

/* Se incluye el archivo de la barra de navegación y se carga el modelo de Usuario para obtener la lista de usuarios registrados en el sistema. Luego, se obtiene el rol del usuario actual desde la sesión para determinar qué funcionalidades mostrar en el dashboard. Esto permite personalizar la experiencia del usuario según su rol, mostrando solo las opciones y acciones que están permitidas para ese rol específico. */
require_once 'views/layout/navbar.php';
require_once 'models/Usuario.php';
$model    = new Usuario();
$usuarios = $model->obtener();
$rol      = $_SESSION['user']['rol_id'];
?>
<!-- Este código es la plantilla para el dashboard de la aplicación. Muestra un mensaje de bienvenida al usuario, información sobre su rol y acceso, y una tabla con la lista de usuarios registrados en el sistema. Para los usuarios con rol de Administrador, también se muestra un formulario para crear nuevos usuarios. La tabla incluye acciones para editar o eliminar usuarios, dependiendo del rol del usuario actual. Además, se manejan mensajes de error y éxito para informar al usuario sobre las acciones realizadas. En general, este código proporciona una interfaz clara y funcional para la gestión de usuarios en el dashboard de la aplicación. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/chocoTumac/public/css/styles.css">
</head>
<body>

<div class="container mt-4">

    <!-- La sección de encabezado de la página muestra el título "Bienvenido" seguido del nombre del usuario actual. Esto proporciona una bienvenida personalizada al usuario cada vez que accede al dashboard, mejorando la experiencia del usuario y haciendo que la interfaz sea más amigable. El nombre del usuario se obtiene de la sesión y se muestra de manera segura utilizando htmlspecialchars para prevenir ataques de XSS. -->
    <div class="page-header">
        <h2>Bienvenido, <?= htmlspecialchars($_SESSION['user']['nombre']) ?> </h2>
    </div>
    <!-- La sección de información del usuario muestra el rol del usuario actual y un mensaje que indica su nivel de acceso. Esto ayuda a los usuarios a identificar rápidamente su rol dentro de la aplicación y las funcionalidades disponibles para ellos. Por ejemplo, un Administrador tendrá acceso completo, mientras que un Gerente tendrá acceso de solo lectura. Esta información es útil para orientar a los usuarios sobre lo que pueden hacer en el dashboard y para mejorar la usabilidad de la aplicación. -->
    <?php if ($rol != 1): ?>
        <div class="alert alert-info">
            <?php if ($rol == 2): ?>
                Tienes acceso de <strong>solo lectura</strong>. Puedes visualizar clientes y proveedores.
            <?php elseif ($rol == 3): ?>
                Puedes gestionar clientes y proveedores. Para administrar usuarios, contacta al administrador.
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <!-- Este bloque de código maneja la visualización de mensajes de error o éxito para informar al usuario sobre las acciones realizadas. Si hay un mensaje de error (indicado por la variable 'error' en la URL), se muestra una alerta de Bootstrap con el mensaje correspondiente. Si hay un mensaje de éxito (indicado por la variable 'msg' en la URL), se muestra una alerta con el tipo y texto correspondiente según el valor de 'msg'. Esto proporciona retroalimentación visual al usuario sobre el resultado de sus acciones, como crear, actualizar o eliminar usuarios. -->
    <?php if (isset($_GET['msg'])): ?>
        <?php
        $msgs = [
            'creado'      => ['success', '✓ Usuario creado correctamente.'],
            'actualizado' => ['success', '✓ Usuario actualizado correctamente.'],
            'eliminado'   => ['warning', '✓ Usuario eliminado del sistema.'],
        ];
        if (isset($msgs[$_GET['msg']])):
            [$tipo, $texto] = $msgs[$_GET['msg']];
        ?>
        <!-- Si el mensaje de éxito es válido, se muestra una alerta de Bootstrap con el tipo y texto correspondiente. Esto proporciona retroalimentación visual al usuario sobre el resultado de sus acciones, como crear, actualizar o eliminar usuarios. La alerta es automática y se puede cerrar manualmente por el usuario. -->
        <div class="alert alert-<?= $tipo ?> alert-auto alert-dismissible" role="alert">
            <?= $texto ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Si el usuario tiene rol de Administrador (1), se muestra un formulario para crear nuevos usuarios y una tabla con la lista de usuarios registrados en el sistema. Para los usuarios con otros roles, solo se muestra la tabla sin el formulario de creación. Esto permite personalizar la experiencia del usuario según su rol, mostrando solo las opciones y acciones que están permitidas para ese rol específico. -->
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-auto alert-dismissible" role="alert">
            <strong>Error:</strong> <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($rol == 1): ?>

    <!-- FORMULARIO CREAR USUARIO -->
    <!-- Este bloque de código muestra un formulario para crear nuevos usuarios en el sistema. Este formulario solo se muestra para usuarios con rol de Administrador (1). El formulario incluye campos para el nombre del usuario, correo electrónico, teléfono, contraseña y rol. Al enviar el formulario, se envía una solicitud POST al UsuarioController para crear un nuevo usuario en la base de datos. El formulario también incluye validaciones HTML5 para garantizar que los datos ingresados sean correctos antes de enviarlos al servidor. Esto ayuda a mejorar la experiencia del usuario y a mantener la integridad de los datos en la aplicación. -->  
    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Crear Nuevo Usuario</h5>
        <form method="POST" action="/chocoTumac/controllers/UsuarioController.php?action=crear" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="row g-2 mb-2">
                <div class="col-md">
                    <label for="fld-nombre" class="form-label small fw-semibold">Nombre</label>
                    <input id="fld-nombre" class="form-control" name="nombre" placeholder="Nombre completo" required minlength="2">
                    <div class="invalid-feedback">El nombre es obligatorio.</div>
                </div>
                <div class="col-md">
                    <label for="fld-email" class="form-label small fw-semibold">Correo electrónico</label>
                    <input id="fld-email" class="form-control" type="email" name="email" placeholder="correo@ejemplo.com" required>
                    <div class="invalid-feedback">Ingresa un correo válido.</div>
                </div>
                <div class="col-md">
                    <label for="fld-telefono" class="form-label small fw-semibold">Teléfono</label>
                    <input id="fld-telefono" class="form-control" name="telefono" placeholder="3001234567">
                </div>
                <div class="col-md">
                    <label for="fld-password" class="form-label small fw-semibold">Contraseña</label>
                    <input id="fld-password" class="form-control" type="password" name="password" id="input-password" placeholder="Mínimo 8 caracteres" required>
                    <div id="feedback-password" class="form-text"></div>
                </div>
                <div class="col-md-2">
                    <label for="fld-rol_id" class="form-label small fw-semibold">Rol</label>
                    <select id="fld-rol_id" class="form-select" name="rol_id" required>
                        <option value="1">Administrador</option>
                        <option value="2">Gerente</option>
                        <option value="3" selected>Empleado</option>
                    </select>
                </div>
                <div class="col-md-auto d-flex align-items-end">
                    <button class="btn btn-ct-primary px-4">Crear</button>
                </div>
            </div>
        </form>
    </div>

    <!-- TABLA USUARIOS -->
     <!-- La tabla muestra la lista de usuarios registrados en el sistema. Cada fila de la tabla representa un usuario y muestra información como el nombre, correo electrónico, teléfono, rol y último acceso. Para usuarios con rol de Administrador (1), se muestran acciones adicionales para editar o eliminar usuarios. El botón de editar redirige al usuario a un formulario prellenado con los datos del usuario para realizar modificaciones, mientras que el botón de eliminar muestra un modal de confirmación antes de proceder con la eliminación del usuario. Esta tabla proporciona una vista clara y organizada de los usuarios registrados, facilitando su gestión por parte de los administradores del sistema. -->
    <div class="card p-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Usuarios Registrados</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Último acceso</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ($usuarios as $u): ?>
                <tr>
                    <td class="text-muted"><?= $i++ ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($u['nombre']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['telefono'] ?? '—') ?></td>
                    <td>
                        <?php
                        $badgeClass = ['Administrador' => 'badge-admin', 'Gerente' => 'badge-gerente', 'Empleado' => 'badge-empleado'][$u['rol']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($u['rol']) ?></span>
                    </td>
                    <td class="text-muted">
                        <?= $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Nunca' ?>
                    </td>
                    <td class="text-center">
                        <?php if ($u['rol'] !== 'Administrador' || $u['id'] == $_SESSION['user']['id']): ?>
                            <a class="btn btn-warning btn-sm"
                               href="/chocoTumac/controllers/UsuarioController.php?action=editar&id=<?= $u['id'] ?>">
                                Editar
                            </a>
                            <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                            <button class="btn btn-danger btn-sm btn-confirmar-eliminar"
                                    data-url="/chocoTumac/controllers/UsuarioController.php?action=eliminar&id=<?= $u['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($u['nombre']) ?>">
                                 Eliminar
                            </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-secondary">Protegido</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/chocoTumac/public/js/app.js"></script>
</body>
</html>