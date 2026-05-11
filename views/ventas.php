<?php
/**
 * Vista: Módulo de Ventas – ChocoTumac Sprint 2.
 *
 * Permite registrar ventas de cualquier producto.
 * Soporta dos tipos de cliente:
 *   - Cliente registrado: seleccionado del desplegable
 *   - Cliente ocasional: nombre escrito libremente (no requiere registro)
 *
 * Al registrar una venta el stock se descuenta automáticamente.
 * Si no hay stock suficiente el sistema bloquea la operación.
 *
 * Permisos:
 *   - Administrador (1): registrar y eliminar ventas
 *   - Empleado (3):      registrar ventas
 *   - Gerente (2):       solo lectura
 *
 * @package ChocoTumac
 * @sprint  2
 */

if (!defined('CHOCOTUMAC_APP')) {
    header("Location: /chocoTumac/index.php"); exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

require_once 'models/Venta.php';
require_once 'models/Cliente.php';
require_once 'models/Producto.php';

$modelVenta    = new Venta();
$modelCliente  = new Cliente();
$modelProducto = new Producto();

$ventas   = $modelVenta->obtener();
$clientes = $modelCliente->obtener()->fetchAll(PDO::FETCH_ASSOC);

/** Solo productos activos con stock mayor a cero */
$productos = array_filter(
    $modelProducto->obtenerActivos(),
    fn($p) => (float)$p['stock_actual'] > 0
);

$rol = $_SESSION['user']['rol_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ventas – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/chocoTumac/public/css/styles.css">
</head>
<body>
<?php require 'views/layout/navbar.php'; ?>

<div class="container mt-4">

    <div class="page-header">
        <h2>Ventas</h2>
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
            'creado'   => ['success', '✓ Venta registrada correctamente. El inventario fue actualizado.'],
            'eliminado'=> ['warning', '✓ Venta eliminada. El stock fue restaurado.'],
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

    <!-- ── Aviso sin stock ───────────────────────────────────────────── -->
    <?php if (in_array($rol, [1, 3]) && empty($productos)): ?>
        <div class="alert alert-warning" role="alert">
            <strong>⚠️ Sin stock disponible.</strong>
            No hay productos con stock para registrar una venta.
            Registra una compra o realiza un ajuste de inventario primero.
        </div>
    <?php endif; ?>

    <!-- ── Formulario registrar venta ───────────────────────────────── -->
    <?php if (in_array($rol, [1, 3]) && !empty($productos)): ?>
    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Registrar Venta</h5>
        <form method="POST"
              action="/chocoTumac/controllers/VentaController.php?action=crear"
              data-validate>

            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- ── Selector tipo de cliente ───────────────────────── -->
            <div class="mb-3">
                <label class="form-label small fw-semibold">Tipo de cliente</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio"
                               name="tipo_cliente" id="radio-registrado"
                               value="registrado" checked>
                        <label class="form-check-label" for="radio-registrado">
                            Cliente registrado
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio"
                               name="tipo_cliente" id="radio-ocasional"
                               value="ocasional">
                        <label class="form-check-label" for="radio-ocasional">
                            Cliente ocasional (sin registro)
                        </label>
                    </div>
                </div>
            </div>

            <div class="row g-2 mb-2">

                <!-- Cliente registrado (desplegable) -->
                <div class="col-md-3" id="div-cliente-registrado">
                    <label class="form-label small fw-semibold">
                        Cliente <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" name="cliente_id" id="sel-cliente">
                        <option value="">— Selecciona cliente —</option>
                        <?php foreach ($clientes as $cli): ?>
                        <option value="<?= $cli['id'] ?>">
                            <?= htmlspecialchars($cli['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Cliente ocasional (campo de texto) -->
                <div class="col-md-3" id="div-cliente-ocasional" style="display:none;">
                    <label class="form-label small fw-semibold">
                        Nombre del cliente
                        <span class="text-muted small">(opcional)</span>
                    </label>
                    <input class="form-control" name="cliente_ocasional"
                           id="inp-cliente-ocasional"
                           placeholder="Ej: Juan García o dejar vacío">
                    <div class="form-text text-muted">
                        Si no sabes el nombre, déjalo vacío — se registrará como "Cliente general".
                    </div>
                </div>

                <!-- Documento cliente ocasional -->
                <div class="col-md-3" id="div-doc-ocasional" style="display:none;">
                    <label class="form-label small fw-semibold">
                        Documento
                        <span class="text-muted small">(opcional)</span>
                    </label>
                    <div class="input-group">
                        <select class="form-select" name="doc_ocasional_tipo"
                                id="sel-doc-tipo" style="max-width:90px;">
                            <option value="CC">CC</option>
                            <option value="NIT">NIT</option>
                            <option value="CE">CE</option>
                            <option value="Pasaporte">Pasaporte</option>
                        </select>
                        <input class="form-control" type="text"
                               name="doc_ocasional_num" id="inp-doc-num"
                               placeholder="Número">
                    </div>
                    <div class="form-text text-muted">Aparecerá en la factura.</div>
                </div>

                <!-- Producto -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">
                        Producto <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" name="producto_id"
                            id="sel-producto-venta" required>
                        <option value="">— Selecciona producto —</option>
                        <?php foreach ($productos as $prod): ?>
        <option value="<?= $prod['id'] ?>"
                data-stock="<?= $prod['stock_actual'] ?>"
                data-tipo="<?= htmlspecialchars($prod['tipo_slug']) ?>"
                data-unidad="<?= htmlspecialchars($prod['unidad']) ?>"
                data-unidad-venta="<?= htmlspecialchars($prod['unidad_venta']) ?>"
                data-precio="<?= $prod['precio_venta'] ?>">
            <?= htmlspecialchars($prod['nombre']) ?>
            (Stock: <?= number_format($prod['stock_actual'], 2) ?> <?= $prod['unidad'] ?>)
        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Selecciona un producto.</div>
                </div>

                <!-- Fecha -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">
                        Fecha <span class="text-danger">*</span>
                    </label>
                    <input class="form-control" type="date" name="fecha"
                           value="<?= date('Y-m-d') ?>" required>
                </div>

                <!-- Cantidad -->
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">
                        Cantidad <span class="text-danger">*</span>
                    </label>
                    <input class="form-control" type="number" name="cantidad"
                           id="inp-cant-venta" min="1" step="1"
                           placeholder="0" required>
                    <div class="invalid-feedback">Ingresa la cantidad.</div>
                </div>

            </div>

            <div class="row g-2 mb-3">

                <!-- Unidad (solo lectura, se llena automáticamente) -->
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Unidad</label>
                    <input class="form-control" type="text" id="txt-unidad-venta"
                           readonly style="background:#f8f3ec;" placeholder="—">
                </div>

                <!-- Precio unitario -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">
                        Precio unit. ($) <span class="text-danger">*</span>
                    </label>
                    <input class="form-control" type="number" name="precio_unitario"
                           id="inp-precio-venta" min="0.01" step="0.01"
                           placeholder="0.00" required>
                    <div class="invalid-feedback">Ingresa el precio.</div>
                </div>

                <!-- Total calculado -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Total ($)</label>
                    <input class="form-control fw-bold" type="text"
                           id="inp-total-venta" readonly placeholder="0.00"
                           style="background:#f8f3ec;">
                </div>

                <!-- IVA -->
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">IVA</label>
                    <select class="form-select" name="iva_porcentaje" id="sel-iva">
                        <option value="0">0%</option>
                        <option value="5">5%</option>
                        <option value="19">19%</option>
                    </select>
                </div>

                <!-- Forma de pago -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Forma de pago</label>
                    <select class="form-select" name="forma_pago">
                        <option value="contado">Contado</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>

                <!-- Stock disponible informativo -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Stock disponible</label>
                    <input class="form-control text-success fw-bold" type="text"
                           id="txt-stock-disp" readonly placeholder="—"
                           style="background:#f0faf0;">
                </div>

                <!-- Observaciones -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Observaciones</label>
                    <input class="form-control" name="observaciones"
                           placeholder="Notas adicionales (opcional)">
                </div>

                <div class="col-md-auto d-flex align-items-end">
                    <button class="btn btn-ct-primary px-4">Registrar venta</button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- ── Historial de ventas ───────────────────────────────────────── -->
    <div class="card p-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Historial de Ventas</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Producto</th>
                        <th class="text-end">Cantidad</th>
                        <th>Unidad</th>
                        <th class="text-end">Precio unit.</th>
                        <th class="text-end">Total</th>
                        <th>Observaciones</th>
                        <th>Registrado por</th>
                        <th class="text-center">Factura</th>
                        <?php if ($rol == 1): ?>
                        <th class="text-center">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($ventas)): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">
                            No hay ventas registradas aún.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($ventas as $v): ?>
                    <tr>
                        <td class="fw-semibold text-nowrap" style="color:#5C3317;">
                            <?= htmlspecialchars($v['codigo'] ?? '—') ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($v['fecha'])) ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($v['cliente_nombre']) ?></td>
                        <td>
                            <?php if ($v['cliente_id']): ?>
                                <span class="badge bg-success">Registrado</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Ocasional</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($v['producto_nombre']) ?></td>
                        <td class="text-end"><?= number_format($v['cantidad'], 2) ?></td>
                        <td><?= htmlspecialchars($v['unidad_venta'] ?? 'und') ?></td>
                        <td class="text-end">$<?= number_format($v['precio_unitario'], 2) ?></td>
                        <td class="text-end fw-bold text-success">
                            $<?= number_format($v['total'], 2) ?>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($v['observaciones'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($v['usuario_nombre']) ?></td>
                        <td class="text-center">
                            <a href="/chocoTumac/index.php?view=factura&id=<?= $v['id'] ?>"
                               class="btn btn-sm btn-outline-secondary"
                               target="_blank"
                               title="Ver e imprimir factura">
                                🖨 Imprimir
                            </a>
                        </td>
                        <?php if ($rol == 1): ?>
                        <td class="text-center">
                            <button class="btn btn-danger btn-sm btn-confirmar-eliminar"
                                    data-url="/chocoTumac/controllers/VentaController.php?action=eliminar&id=<?= $v['id'] ?>"
                                    data-nombre="la venta <?= htmlspecialchars($v['codigo'] ?? $v['id']) ?> — <?= htmlspecialchars($v['cliente_nombre']) ?>">
                                🗑 Eliminar
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
                <h5 class="modal-title text-danger">⚠️ Confirmar eliminación</h5>
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
<script src="/chocoTumac/public/js/ventas.js"></script>
</body>
</html>
