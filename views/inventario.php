<?php
/**
 * Vista: Módulo de Inventario – ChocoTumac Sprint 2.
 *
 * Muestra el stock actual de todos los productos registrados.
 * Solo el administrador puede:
 *   - Agregar nuevos productos
 *   - Editar productos existentes
 *   - Realizar ajuste inicial de stock
 * Todos los roles pueden ver el inventario.
 * Las entradas y salidas de stock se generan automáticamente
 * desde los módulos de Compras y Ventas.
 *
 * @package ChocoTumac
 * @sprint  2
 */

// Bloquear acceso directo por URL (solo desde index.php)
if (!defined('CHOCOTUMAC_APP')) {
    header("Location: /chocoTumac/index.php");
    exit();
}

// Prevenir caché del navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// Verificar sesión activa
if (!isset($_SESSION['user'])) {
    header("Location: /chocoTumac/index.php");
    exit();
}

require_once 'models/Producto.php';

$modelProducto = new Producto();
$productos     = $modelProducto->obtener();
$movimientos   = $modelProducto->obtenerMovimientos();
$rol           = $_SESSION['user']['rol_id'];

/**
 * Determina el color del badge de stock según el nivel actual vs mínimo.
 *
 * @param float $actual  Stock actual del producto
 * @param float $minimo  Stock mínimo configurado
 * @return string        Clase CSS de Bootstrap para el badge
 */
function badgeStock($actual, $minimo) {
    if ($actual <= 0)            return 'bg-danger';
    if ($actual <= $minimo)      return 'bg-warning text-dark';
    return 'bg-success';
}

/** Etiquetas legibles por tipo de movimiento */
$tipo_mov_label = [
    'entrada'        => ['label' => '⬆ Entrada',   'color' => 'text-success'],
    'salida'         => ['label' => '⬇ Salida',    'color' => 'text-danger'],
    'ajuste_inicial' => ['label' => '⚙ Ajuste',    'color' => 'text-primary'],
];

/** Tipos de producto dinámicos desde la BD */
$tipos_producto = $modelProducto->obtenerTodosTipos();
// Mapa slug → nombre para etiquetas en tablas
$tipo_label = [];
foreach ($tipos_producto as $t) {
    $tipo_label[$t['slug']] = $t['nombre'];
}
// Tipos activos para el formulario de crear producto
$tipos_activos = array_filter($tipos_producto, fn($t) => $t['activo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventario – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/chocoTumac/public/css/styles.css">
</head>
<body>
<?php require_once 'views/layout/navbar.php'; ?>

<div class="container mt-4">

    <div class="page-header">
        <h2>Inventario</h2>
        <?php if ($rol == 2): ?>
            <span class="badge bg-info fs-6">Solo lectura</span>
        <?php endif; ?>
    </div>

    <!-- ── Alertas de operación ─────────────────────────────────────── -->
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-auto alert-dismissible" role="alert">
            <strong>Error:</strong> <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg'])): ?>
        <?php
        $msgs = [
            'producto_creado'     => ['success', '✓ Producto registrado correctamente.'],
            'producto_actualizado'=> ['success', '✓ Producto actualizado correctamente.'],
            'ajuste_ok'           => ['success', '✓ Stock ajustado correctamente.'],
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

    <!-- ── Formulario agregar producto (solo admin) ──────────────────── -->
    <?php if ($rol == 1): ?>
    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Agregar Producto</h5>
        <form method="POST"
              action="/chocoTumac/controllers/ProductoController.php?action=crear"
              data-validate id="form-producto">

            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="row g-2 mb-2">
                <!-- Nombre -->
                <div class="col-md-3">
                    <label for="fld-nombre" class="form-label small fw-semibold">Nombre <span class="text-danger">*</span></label>
                    <input id="fld-nombre" class="form-control" name="nombre"
                           placeholder="Ej: Chocolate de Mesa 750g" required minlength="2">
                    <div class="invalid-feedback">El nombre es obligatorio.</div>
                </div>
                <!-- Tipo (dinámico desde BD) -->
                <div class="col-md-2">
                    <label for="fld-tipo_id" class="form-label small fw-semibold">Tipo <span class="text-danger">*</span></label>
                    <select id="fld-tipo_id" class="form-select" name="tipo_id" id="sel-tipo" required>
                        <option value="">— Selecciona tipo —</option>
                        <?php foreach ($tipos_activos as $t): ?>
                        <option value="<?= $t['id'] ?>"
                                data-unidad="<?= $t['unidad'] ?>"
                                data-requiere-presentacion="<?= $t['requiere_presentacion'] ?>">
                            <?= htmlspecialchars($t['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Presentación (condicional según el tipo) -->
                <div class="col-md-2" id="div-presentacion" style="display:none;">
                    <label for="fld-presentacion" class="form-label small fw-semibold">Presentación</label>
                    <input id="fld-presentacion" class="form-control" name="presentacion" id="inp-presentacion"
                           placeholder="Ej: 750g">
                    <div class="form-text text-muted" style="font-size:.7rem;">
                        Peso o tamaño del empaque
                    </div>
                </div>
                <!-- Unidad (automática según tipo, solo lectura) -->
                <div class="col-md-2">
                    <label for="fld-unidad-aj" class="form-label small fw-semibold">Unidad</label>
                    <input class="form-control text-center fw-bold" type="text"
                           id="txt-unidad-inv" readonly value="—"
                           style="background:#f8f3ec; color:#5C3317;"
                           title="La unidad se asigna automáticamente según el tipo">
                    <div class="form-text text-muted" style="font-size:.7rem;">
                        Automática por tipo
                    </div>
                </div>
                <!-- Stock mínimo -->
                <div class="col-md-1">
                    <label for="fld-stock_minimo" class="form-label small fw-semibold">Stock mín.</label>
                    <input id="fld-stock_minimo" class="form-control" name="stock_minimo" type="number"
                           min="0" step="0.01" value="0" placeholder="0">
                </div>
                <!-- Stock inicial -->
                <div class="col-md-1">
                    <label for="fld-stock_inicial" class="form-label small fw-semibold">Stock inicial</label>
                    <input id="fld-stock_inicial" class="form-control" name="stock_inicial" id="inp-stock-inicial"
                           type="number" min="0" step="0.01" value="0" placeholder="0">
                    <div class="form-text text-muted" style="font-size:.7rem;">Opcional</div>
                </div>
                <!-- Precio venta -->
                <div class="col-md-2">
                    <label for="fld-precio_venta" class="form-label small fw-semibold">Precio venta ($)</label>
                    <input id="fld-precio_venta" class="form-control" name="precio_venta" type="number"
                           min="0" step="0.01" value="0" placeholder="0.00">
                </div>
            </div>
            <button class="btn btn-ct-primary px-4">Agregar producto</button>
        </form>
    </div>

    <?php if ($rol == 1): ?>
    <!-- ── Gestión de Tipos de Producto (solo admin) ─────────────────── -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">⚙ Tipos de Producto</h5>
        <p class="text-muted small mb-3">
            Define los tipos disponibles. La unidad y la unidad de venta se aplican automáticamente
            a todos los productos de ese tipo.
        </p>

        <!-- Formulario crear tipo -->
        <form method="POST"
              action="/chocoTumac/controllers/ProductoController.php?action=crearTipo"
              class="mb-4" id="form-tipo">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label for="fld-nombre" class="form-label small fw-semibold">Nombre del tipo <span class="text-danger">*</span></label>
                    <input id="fld-nombre" class="form-control" name="nombre" placeholder="Ej: Cacao en pasta" required>
                </div>
                <div class="col-md-1">
                    <label for="fld-unidad" class="form-label small fw-semibold">Unidad inv.</label>
                    <select id="fld-unidad" class="form-select" name="unidad">
                        <option value="kg">kg – Kilogramos</option>
                        <option value="g">g – Gramos</option>
                        <option value="und">und – Unidades</option>
                        <option value="lb">lb – Libras</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label for="fld-unidad_venta" class="form-label small fw-semibold">Unidad venta</label>
                    <select id="fld-unidad_venta" class="form-select" name="unidad_venta">
                        <option value="und">und – Unidades</option>
                        <option value="kg">kg – Kilogramos</option>
                        <option value="g">g – Gramos</option>
                        <option value="lb">lb – Libras</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fld-descripcion" class="form-label small fw-semibold">Descripción</label>
                    <input id="fld-descripcion" class="form-control" name="descripcion" placeholder="Opcional">
                </div>
                <div class="col-md-2 d-flex align-items-center gap-2 pt-3">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox"
                               name="requiere_presentacion" id="chk-pres" value="1">
                        <label class="form-check-label small" for="chk-pres">
                            Pide presentación
                        </label>
                    </div>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-ct-primary px-3">Agregar tipo</button>
                </div>
            </div>
        </form>

        <!-- Tabla de tipos existentes -->
        <div class="table-responsive">
            <table class="table table-hover align-middle small mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Slug</th>
                        <th>Unidad inv.</th>
                        <th>Unidad venta</th>
                        <th>Pide presentación</th>
                        <th>Descripción</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tipos_producto as $t): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($t['nombre']) ?></td>
                    <td><code><?= htmlspecialchars($t['slug']) ?></code></td>
                    <td><span class="badge bg-secondary"><?= $t['unidad'] ?></span></td>
                    <td><span class="badge bg-info text-dark"><?= $t['unidad_venta'] ?></span></td>
                    <td><?= $t['requiere_presentacion'] ? '✅ Sí' : '—' ?></td>
                    <td class="text-muted"><?= htmlspecialchars($t['descripcion'] ?? '—') ?></td>
                    <td class="text-center">
                        <?php if ($t['activo']): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactivo</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Ajuste inicial de stock ───────────────────────────────────── -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">⚙ Ajuste de Stock</h5>
        <p class="text-muted small mb-3">
            Usa esta opción para establecer el stock inicial de un producto
            o corregir el stock manualmente cuando sea necesario.
        </p>
        <form method="POST"
              action="/chocoTumac/controllers/ProductoController.php?action=ajuste"
              data-validate>

            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="fld-producto_id" class="form-label small fw-semibold">Producto <span class="text-danger">*</span></label>
                    <select id="fld-producto_id" class="form-select" name="producto_id" required>
                        <option value="">— Selecciona un producto —</option>
                        <?php foreach ($productos as $prod): ?>
                        <option value="<?= $prod['id'] ?>">
                            <?= htmlspecialchars($prod['nombre']) ?>
                            (Stock actual: <?= number_format($prod['stock_actual'], 2) ?> <?= $prod['unidad'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fld-cantidad" class="form-label small fw-semibold">Nuevo stock <span class="text-danger">*</span></label>
                    <input id="fld-cantidad" class="form-control" name="cantidad" type="number"
                           min="0" step="0.01" placeholder="0.00" required>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-warning fw-semibold px-4">Aplicar ajuste</button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- ── Tabla de productos / stock actual ────────────────────────── -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Stock Actual</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Presentación</th>
                        <th>Unidad</th>
                        <th class="text-end">Stock actual</th>
                        <th class="text-end">Stock mínimo</th>
                        <th class="text-end">Precio venta</th>
                        <th class="text-center">Estado</th>
                        <?php if ($rol == 1): ?>
                        <th class="text-center">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($productos)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            No hay productos registrados aún.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($productos as $prod): ?>
                    <tr class="<?= !$prod['activo'] ? 'table-secondary text-muted' : '' ?>">
                        <td><?= $i++ ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($prod['nombre']) ?></td>
                        <td><?= htmlspecialchars($prod['tipo_nombre'] ?? ($tipo_label[$prod['tipo_slug']] ?? '—')) ?></td>
                        <td><?= htmlspecialchars($prod['presentacion'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($prod['unidad']) ?></td>
                        <td class="text-end fw-bold">
                            <?= number_format($prod['stock_actual'], 2) ?>
                        </td>
                        <td class="text-end text-muted">
                            <?= number_format($prod['stock_minimo'], 2) ?>
                        </td>
                        <td class="text-end">
                            $<?= number_format($prod['precio_venta'], 2) ?>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= badgeStock($prod['stock_actual'], $prod['stock_minimo']) ?>">
                                <?php
                                if ($prod['stock_actual'] <= 0)           echo 'Sin stock';
                                elseif ($prod['stock_actual'] <= $prod['stock_minimo']) echo 'Stock bajo';
                                else echo 'OK';
                                ?>
                            </span>
                            <?php if (!$prod['activo']): ?>
                                <span class="badge bg-secondary ms-1">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($rol == 1): ?>
                        <td class="text-center">
                            <a class="btn btn-warning btn-sm"
                               href="/chocoTumac/index.php?view=editar_producto&id=<?= $prod['id'] ?>">
                               ✏ Editar
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Historial de movimientos ─────────────────────────────────── -->
    <div class="card p-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Historial de Movimientos</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Movimiento</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Stock antes</th>
                        <th class="text-end">Stock después</th>
                        <th>Origen</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($movimientos)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No hay movimientos registrados aún.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movimientos as $mov):
                        $info = $tipo_mov_label[$mov['tipo']] ?? ['label' => $mov['tipo'], 'color' => ''];
                    ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($mov['fecha'])) ?></td>
                        <td><?= htmlspecialchars($mov['producto']) ?></td>
                        <td class="<?= $info['color'] ?> fw-semibold">
                            <?= $info['label'] ?>
                        </td>
                        <td class="text-end">
                            <?= number_format($mov['cantidad'], 2) ?>
                            <?= htmlspecialchars($mov['unidad']) ?>
                        </td>
                        <td class="text-end text-muted">
                            <?= number_format($mov['stock_antes'], 2) ?>
                        </td>
                        <td class="text-end fw-bold">
                            <?= number_format($mov['stock_despues'], 2) ?>
                        </td>
                        <td class="text-capitalize">
                            <?= htmlspecialchars($mov['referencia_tipo']) ?>
                            <?= $mov['referencia_id'] ? '#'.$mov['referencia_id'] : '' ?>
                        </td>
                        <td><?= htmlspecialchars($mov['usuario']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/chocoTumac/public/js/app.js"></script>
<script src="/chocoTumac/public/js/inventario.js"></script>

</body>
</html>