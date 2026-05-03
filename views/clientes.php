<?php
// Bloquear acceso directo a esta vista por URL
if (!defined('CHOCOTUMAC_APP')) {
    header("Location: /choco_tumac/index.php");
    exit();
}
session_start();

// Prevenir caché del navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// Protección de ruta — redirigir si no hay sesión activa
if (!isset($_SESSION['user'])) {
    header("Location: /choco_tumac/index.php?view=login&error=" . urlencode("Tu sesión ha expirado. Inicia sesión nuevamente."));
    exit();
}

require_once 'models/Cliente.php';
$model    = new Cliente();
$clientes = $model->obtener();
$rol      = $_SESSION['user']['rol_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clientes – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/choco_tumac/public/css/styles.css">
</head>
<body>
<?php require 'views/layout/navbar.php'; ?>

<div class="container mt-4">

    <div class="page-header">
        <h2>Clientes</h2>
        <?php if ($rol == 2): ?>
            <span class="badge bg-info fs-6">Solo lectura</span>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-auto alert-dismissible" role="alert">
            <strong>Error:</strong> <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

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
    <?php if (in_array($rol, [1, 3])): ?>
    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Registrar Cliente</h5>
        <form method="POST" action="/choco_tumac/controllers/ClienteController.php?action=crear" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="row g-2 mb-2">
                <!-- Nombre -->
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Nombre / Razón social <span class="text-danger">*</span></label>
                    <input class="form-control" name="nombre" placeholder="Nombre completo o empresa" required minlength="2">
                    <div class="invalid-feedback">El nombre es obligatorio.</div>
                </div>
                <!-- Tipo doc -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tipo doc. <span class="text-danger">*</span></label>
                    <select class="form-select" name="tipo_doc" id="tipo_doc_cli" required>
                        <option value="CC">CC</option>
                        <option value="NIT">NIT</option>
                        <option value="CE">CE</option>
                        <option value="Pasaporte">Pasaporte</option>
                    </select>
                </div>
                <!-- Número doc -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">N° documento <span class="text-danger">*</span></label>
                    <input class="form-control" name="num_doc" placeholder="Ej: 800123456" required
                           pattern="[0-9\-]+" title="Solo números y guiones">
                    <div class="invalid-feedback">Ingresa el número de documento.</div>
                </div>
                <!-- Dígito verificación (solo NIT) -->
                <div class="col-md-1" id="div_digito_cli" style="display:none;">
                    <label class="form-label small fw-semibold">DV</label>
                    <input class="form-control" name="digito_ver" placeholder="0" maxlength="1"
                           pattern="[0-9]" title="Un solo dígito">
                </div>
                <!-- Teléfono -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Teléfono</label>
                    <input class="form-control" name="telefono" placeholder="Ej: 3001234567">
                </div>
            </div>

            <div class="row g-2 mb-3">
                <!-- Email -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Correo electrónico</label>
                    <input class="form-control" type="email" name="email" placeholder="correo@ejemplo.com">
                </div>
                <!-- Dirección -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Dirección</label>
                    <input class="form-control" name="direccion" placeholder="Dirección">
                </div>
                <!-- Ciudad -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Ciudad</label>
                    <input class="form-control" name="ciudad" placeholder="Ej: Tumaco">
                </div>
                <!-- Departamento -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Departamento</label>
                    <input class="form-control" name="departamento" placeholder="Ej: Nariño">
                </div>
                <div class="col-md-auto d-flex align-items-end">
                    <button class="btn btn-ct-primary px-4">Registrar</button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- TABLA -->
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
                           href="/choco_tumac/controllers/ClienteController.php?action=editar&id=<?= $c['id'] ?>">
                            Editar
                        </a>
                        <?php if ($rol == 1): ?>
                        <button class="btn btn-danger btn-sm btn-confirmar-eliminar"
                                data-url="/choco_tumac/controllers/ClienteController.php?action=eliminar&id=<?= $c['id'] ?>"
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
<script src="/choco_tumac/public/js/app.js"></script>
<script>
// Mostrar/ocultar campo dígito verificación según tipo doc
document.getElementById('tipo_doc_cli')?.addEventListener('change', function () {
    document.getElementById('div_digito_cli').style.display =
        this.value === 'NIT' ? '' : 'none';
});
</script>
</body>
</html>
