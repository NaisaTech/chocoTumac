<?php
/**
 * Vista: Módulo de Compras – ChocoTumac Sprint 2.
 *
 * Permite registrar compras de cacao en grano seco a proveedores.
 * Al registrar una compra, el stock del producto en inventario
 * se incrementa automáticamente.
 *
 * Permisos:
 *   - Administrador (1): registrar y eliminar compras
 *   - Empleado (3):      registrar compras
 *   - Gerente (2):       solo lectura (historial)
 *
 * @package ChocoTumac
 * @sprint  2
 */

if (!defined('CHOCOTUMAC_APP')) {
    header("Location: /chocoTumac/index.php");
    exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

if (!isset($_SESSION['user'])) {
    header("Location: /chocoTumac/index.php");
    exit();
}

require_once 'models/Compra.php';
require_once 'models/Proveedor.php';
require_once 'models/Producto.php';

$modelCompra    = new Compra();
$modelProveedor = new Proveedor();
$modelProducto  = new Producto();

$compras     = $modelCompra->obtener();
$proveedores = $modelProveedor->obtener()->fetchAll(PDO::FETCH_ASSOC);

/** Todos los productos activos para compras (cualquier tipo) */
$productos_compra = $modelProducto->obtenerActivos();

$rol = $_SESSION['user']['rol_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compras – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/chocoTumac/public/css/styles.css">
</head>
<body>
<?php require 'views/layout/navbar.php'; ?>

<div class="container mt-4">

    <div class="page-header">
        <h2>Compras de Cacao</h2>
        <?php if ($rol == 2): ?>
            <span class="badge bg-info fs-6">Solo lectura</span>
        <?php endif; ?>
    </div>

    <!-- ── Alertas ──────────────────────────────────────────────────── -->
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-auto alert-dismissible" role="alert">
            <strong>Error:</strong> <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg'])): ?>
        <?php
        $msgs = [
            'creado'   => ['success', '✓ Compra registrada correctamente. El inventario fue actualizado.'],
            'eliminado'=> ['warning', '✓ Compra eliminada. El inventario fue revertido.'],
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

    <!-- ── Formulario registrar compra (admin y empleado) ───────────── -->
    <?php if (in_array($rol, [1, 3])): ?>
    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Registrar Compra</h5>
        <form method="POST"
              action="/chocoTumac/controllers/CompraController.php?action=crear"
              data-validate id="form-compra">

            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="row g-2 mb-2">
                <!-- Proveedor -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Proveedor <span class="text-danger">*</span></label>
                    <select class="form-select" name="proveedor_id" required>
                        <option value="">— Selecciona proveedor —</option>
                        <?php foreach ($proveedores as $prov): ?>
                        <option value="<?= $prov['id'] ?>">
                            <?= htmlspecialchars($prov['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Selecciona un proveedor.</div>
                </div>
                <!-- Producto -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Producto <span class="text-danger">*</span></label>
                    <select class="form-select" name="producto_id" id="sel-producto-compra" required>
                        <option value="">— Selecciona producto —</option>
                        <?php foreach ($productos_compra as $prod): ?>
                        <option value="<?= $prod['id'] ?>"
                                data-unidad="<?= htmlspecialchars($prod['unidad']) ?>">
                            <?= htmlspecialchars($prod['nombre']) ?>
                            (<?= $prod['unidad'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Selecciona un producto.</div>
                </div>
                <!-- Fecha -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Fecha <span class="text-danger">*</span></label>
                    <input class="form-control" type="date" name="fecha"
                           value="<?= date('Y-m-d') ?>" required>
                    <div class="invalid-feedback">La fecha es obligatoria.</div>
                </div>
                <!-- Cantidad -->
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Cantidad <span class="text-danger">*</span></label>
                    <input class="form-control" type="number" name="cantidad"
                           id="inp-cantidad" min="0.01" step="0.01"
                           placeholder="0.00" required>
                    <div class="invalid-feedback">Ingresa la cantidad.</div>
                </div>
                <!-- Unidad (solo lectura, fijada por el tipo de producto) -->
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Unidad</label>
                    <input class="form-control text-center fw-bold" type="text"
                           id="txt-unidad-compra" readonly
                           value="kg" style="background:#f8f3ec; color:#5C3317;">
                    <input type="hidden" name="unidad" id="hid-unidad" value="kg">
                </div>
                <!-- Precio unitario -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Precio por unidad ($) <span class="text-danger">*</span></label>
                    <input class="form-control" type="number" name="precio_unitario"
                           id="inp-precio" min="0.01" step="0.01"
                           placeholder="0.00" required>
                    <div class="invalid-feedback">Ingresa el precio unitario.</div>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <!-- Total calculado (solo lectura) -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Total ($)</label>
                    <input class="form-control fw-bold" type="text"
                           id="inp-total" readonly placeholder="0.00"
                           style="background:#f8f3ec;">
                </div>
                <!-- Observaciones -->
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Observaciones</label>
                    <input class="form-control" name="observaciones"
                           placeholder="Notas adicionales (opcional)">
                </div>
                <div class="col-md-auto d-flex align-items-end">
                    <button class="btn btn-ct-primary px-4">Registrar compra</button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- ── Historial de compras ──────────────────────────────────────── -->
    <div class="card p-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Historial de Compras</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>Producto</th>
                        <th class="text-end">Cantidad</th>
                        <th>Unidad</th>
                        <th class="text-end">Precio unit.</th>
                        <th class="text-end">Total</th>
                        <th>Observaciones</th>
                        <th>Registrado por</th>
                        <?php if ($rol == 1): ?>
                        <th class="text-center">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($compras)): ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            No hay compras registradas aún.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($compras as $c): ?>
                    <tr>
                        <td class="fw-semibold text-nowrap" style="color:#5C3317;">
                            <?= htmlspecialchars($c['codigo'] ?? '—') ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($c['proveedor_nombre']) ?></td>
                        <td><?= htmlspecialchars($c['producto_nombre']) ?></td>
                        <td class="text-end"><?= number_format($c['cantidad'], 2) ?></td>
                        <td><?= htmlspecialchars($c['unidad']) ?></td>
                        <td class="text-end">$<?= number_format($c['precio_unitario'], 2) ?></td>
                        <td class="text-end fw-bold">$<?= number_format($c['total'], 2) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($c['observaciones'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($c['usuario_nombre']) ?></td>
                        <?php if ($rol == 1): ?>
                        <td class="text-center">
                            <button class="btn btn-danger btn-sm btn-confirmar-eliminar"
                                    data-url="/chocoTumac/controllers/CompraController.php?action=eliminar&id=<?= $c['id'] ?>"
                                    data-nombre="la compra del <?= date('d/m/Y', strtotime($c['fecha'])) ?> a <?= htmlspecialchars($c['proveedor_nombre']) ?>">
                                Eliminar
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /container -->

<!-- Modal confirmación eliminación -->
<div class="modal fade" id="modalConfirmarEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">Confirmar eliminación</h5>
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
<script src="/chocoTumac/public/js/compras.js"></script>
</body>
</html>
