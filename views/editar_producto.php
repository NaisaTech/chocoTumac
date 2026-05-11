<?php
/**
 * Vista: Editar Producto – ChocoTumac Sprint 2.
 * Cargada por index.php — sesión, $producto y $modelProducto ya validados.
 *
 * @package ChocoTumac
 * @sprint  2
 */
if (!defined('CHOCOTUMAC_APP')) {
    header("Location: /chocoTumac/index.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Producto – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/chocoTumac/public/css/styles.css">
</head>
<body>
<?php require_once __DIR__ . '/layout/navbar.php'; ?>

<div class="container mt-4" style="max-width:640px;">
    <div class="page-header">
        <h2>Editar Producto</h2>
        <a href="/chocoTumac/index.php?view=inventario" class="btn btn-sm btn-outline-secondary">← Volver</a>
    </div>

    <div class="card p-4">
        <form method="POST"
              action="/chocoTumac/controllers/ProductoController.php?action=actualizar"
              data-validate>

            <input type="hidden" name="id"         value="<?= $producto['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="mb-3">
                <label for="fld-nombre" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                <input id="fld-nombre" class="form-control" name="nombre"
                       value="<?= htmlspecialchars($producto['nombre']) ?>" required minlength="2">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="fld-tipo_id" class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                    <?php $tipos = $modelProducto->obtenerTipos(); ?>
                    <select id="fld-tipo_id" class="form-select" name="tipo_id" id="sel-tipo-edit" required>
                        <?php foreach ($tipos as $t): ?>
                        <option value="<?= $t['id'] ?>"
                                data-unidad="<?= $t['unidad'] ?>"
                                data-requiere-presentacion="<?= $t['requiere_presentacion'] ?>"
                                <?= $producto['tipo_id'] == $t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6" id="div-pres-edit"
                     style="display:<?= $producto['requiere_presentacion'] ? '' : 'none' ?>">
                    <label for="fld-presentacion" class="form-label fw-semibold">Presentación</label>
                    <input id="fld-presentacion" class="form-control" name="presentacion" id="inp-pres-edit"
                           value="<?= htmlspecialchars($producto['presentacion'] ?? '') ?>"
                           placeholder="Ej: 250g, 500g">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="txt-unidad-edit" class="form-label fw-semibold">Unidad</label>
                    <input class="form-control text-center fw-bold" type="text"
                           id="txt-unidad-edit" readonly
                           value="<?= htmlspecialchars($producto['unidad']) ?>"
                           style="background:#f8f3ec; color:#5C3317;"
                           title="La unidad se determina automáticamente por el tipo">
                    <div class="form-text text-muted" style="font-size:.7rem;">Automática por tipo</div>
                </div>
                <div class="col-md-4">
                    <label for="fld-stock_minimo" class="form-label fw-semibold">Stock mínimo</label>
                    <input id="fld-stock_minimo" class="form-control" type="number" name="stock_minimo"
                           min="0" step="0.01"
                           value="<?= $producto['stock_minimo'] ?>">
                </div>
                <div class="col-md-4">
                    <label for="fld-precio-venta" class="form-label fw-semibold text-success">
                        💲 Precio de venta ($)
                    </label>
                    <input id="fld-precio-venta" class="form-control border-success fw-bold" type="number"
                           name="precio_venta" min="0" step="0.01"
                           value="<?= $producto['precio_venta'] ?>">
                    <div class="form-text text-muted" style="font-size:.7rem;">
                        Precio actual: $<?= number_format($producto['precio_venta'], 2) ?>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="activo"
                           id="chk-activo" value="1"
                           <?= $producto['activo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="chk-activo">Producto activo</label>
                </div>
                <small class="text-muted">
                    Un producto inactivo no aparece en los formularios de compra y venta.
                </small>
            </div>

            <!-- Stock actual: solo informativo, no editable aquí -->
            <div class="alert alert-info py-2 small mb-4">
                <strong>Stock actual:</strong>
                <?= number_format($producto['stock_actual'], 2) ?> <?= $producto['unidad'] ?>.
                Para modificarlo ve a
                <a href="/chocoTumac/index.php?view=inventario">Inventario → Ajuste de Stock</a>.
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-ct-primary px-4">Guardar cambios</button>
                <a href="/chocoTumac/index.php?view=inventario" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/chocoTumac/public/js/app.js"></script>
<script src="/chocoTumac/public/js/inventario.js"></script>
</body>
</html>