<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: /choco_tumac/index.php"); exit(); }

require_once 'models/Proveedor.php';
$model       = new Proveedor();
$proveedores = $model->obtener();
$rol         = $_SESSION['user']['rol_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proveedores – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/choco_tumac/public/css/styles.css">
</head>
<body>
<?php require 'views/layout/navbar.php'; ?>

<div class="container mt-4">

    <div class="page-header">
        <h2>Proveedores de Cacao</h2>
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
    <?php if (in_array($rol, [1, 3])): ?>
    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Registrar Proveedor</h5>
        <form method="POST" action="/choco_tumac/controllers/ProveedorController.php?action=crear" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="row g-2 mb-2">
                <!-- Nombre -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Nombre / Razón social <span class="text-danger">*</span></label>
                    <input class="form-control" name="nombre" placeholder="Nombre o empresa" required minlength="2">
                    <div class="invalid-feedback">El nombre es obligatorio.</div>
                </div>
                <!-- Tipo doc -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tipo doc. <span class="text-danger">*</span></label>
                    <select class="form-select" name="tipo_doc" id="tipo_doc_prov" required>
                        <option value="CC">CC</option>
                        <option value="NIT">NIT</option>
                        <option value="CE">CE</option>
                        <option value="Pasaporte">Pasaporte</option>
                    </select>
                </div>
                <!-- Número doc -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">N° documento <span class="text-danger">*</span></label>
                    <input class="form-control" name="num_doc" placeholder="Ej: 12345678" required
                           pattern="[0-9\-]+" title="Solo números y guiones">
                    <div class="invalid-feedback">Ingresa el número de documento.</div>
                </div>
                <!-- DV NIT -->
                <div class="col-md-1" id="div_digito_prov" style="display:none;">
                    <label class="form-label small fw-semibold">DV</label>
                    <input class="form-control" name="digito_ver" placeholder="0" maxlength="1" pattern="[0-9]">
                </div>
                <!-- Tipo proveedor -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tipo proveedor <span class="text-danger">*</span></label>
                    <select class="form-select" name="tipo_proveedor" required>
                        <option value="Agricultor">Agricultor</option>
                        <option value="Intermediario">Intermediario</option>
                        <option value="Cooperativa">Cooperativa</option>
                        <option value="Empresa">Empresa</option>
                    </select>
                </div>
                <!-- Persona de contacto -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Persona de contacto</label>
                    <input class="form-control" name="persona_contacto" placeholder="Nombre contacto">
                </div>
            </div>

            <div class="row g-2 mb-3">
                <!-- Teléfono -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Teléfono</label>
                    <input class="form-control" name="telefono" placeholder="3001234567">
                </div>
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
                           href="/choco_tumac/controllers/ProveedorController.php?action=editar&id=<?= $p['id'] ?>">
                            Editar
                        </a>
                        <?php if ($rol == 1): ?>
                        <button class="btn btn-danger btn-sm btn-confirmar-eliminar"
                                data-url="/choco_tumac/controllers/ProveedorController.php?action=eliminar&id=<?= $p['id'] ?>"
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
document.getElementById('tipo_doc_prov')?.addEventListener('change', function () {
    document.getElementById('div_digito_prov').style.display =
        this.value === 'NIT' ? '' : 'none';
});
</script>
</body>
</html>
