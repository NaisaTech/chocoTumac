<?php
/**
 * Vista: Módulo de Reportes – ChocoTumac Sprint 3.
 *
 * Solo accesible por Gerente (rol_id = 2).
 * Incluye:
 *   - Resumen general con métricas clave
 *   - Reporte de ventas por cliente filtrable por fecha, cliente y búsqueda
 *   - Reporte de compras por proveedor filtrable por fecha, proveedor y búsqueda
 *   - Reporte de inventario actualizado con estado de stock
 *   - Productos más vendidos (top 10 con gráfico de barras)
 *
 * @package ChocoTumac
 * @sprint  3
 */

if (!defined('CHOCOTUMAC_APP')) {
    header("Location: /chocoTumac/index.php"); exit();
}

if (($_SESSION['user']['rol_id'] ?? 0) != 2) {
    header("Location: /chocoTumac/index.php?view=dashboard&error=" . urlencode("Solo el Gerente puede acceder al módulo de Reportes."));
    exit();
}

require_once 'models/Reporte.php';

$reporte = new Reporte();

/**
 * Escapa una cadena para salida HTML segura.
 * Previene XSS (Cross-Site Scripting) en todas las vistas.
 *
 * @param  mixed  $val  Valor a escapar.
 * @return string       Cadena segura para insertar en HTML.
 */
function h($val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Filtros activos (sanitizados contra XSS) ──────────────────────
$tabs_validos  = ['ventas', 'compras', 'inventario', 'top'];
$tab_raw       = $_GET['tab'] ?? 'ventas';
$tab           = in_array($tab_raw, $tabs_validos, true) ? $tab_raw : 'ventas';

$desde        = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde'] ?? '')
                    ? $_GET['desde'] : '';
$hasta        = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta'] ?? '')
                    ? $_GET['hasta'] : '';
$busqueda     = trim($_GET['busqueda'] ?? '');
$cliente_id   = (int)($_GET['cliente_id']   ?? 0) ?: null;
$proveedor_id = (int)($_GET['proveedor_id'] ?? 0) ?: null;

// ── Datos ─────────────────────────────────────────────────────────
$resumen    = $reporte->resumenGeneral();
$clientes   = $reporte->listaClientes();
$proveedores= $reporte->listaProveedores();

$ventas     = $reporte->ventas($desde ?: null, $hasta ?: null, $cliente_id, $busqueda ?: null);
$tot_ventas = $reporte->totalesVentas($desde ?: null, $hasta ?: null, $cliente_id, $busqueda ?: null);

$compras    = $reporte->compras($desde ?: null, $hasta ?: null, $proveedor_id, $busqueda ?: null);
$tot_compras= $reporte->totalesCompras($desde ?: null, $hasta ?: null, $proveedor_id, $busqueda ?: null);

$inventario = $reporte->inventario($busqueda ?: null);
$top_prod   = $reporte->productosMasVendidos($desde ?: null, $hasta ?: null);

$hay_filtros = $desde || $hasta || $busqueda || $cliente_id || $proveedor_id;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportes – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/chocoTumac/public/css/styles.css">
    
</head>
<body>

<?php require_once __DIR__ . '/layout/navbar.php'; ?>

<div class="container-fluid mt-4 px-4" style="max-width:1400px;">

    <!-- ── Encabezado ── -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <div>
            <h4 class="fw-bold mb-0" style="color:var(--ct-brand);">📊 Módulo de Reportes</h4>
            <small class="text-muted">Solo visible para Gerentes · Datos en tiempo real</small>
        </div>
        <button onclick="window.print()" class="btn btn-sm text-white no-print"
                style="background:var(--ct-brand);">
            🖨 Imprimir reporte
        </button>
    </div>

    <!-- ── KPI cards ── -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-num"><?= number_format($resumen['total_ventas']) ?></div>
            <div class="kpi-label">Total ventas</div>
        </div>
        <div class="kpi-card accent">
            <div class="kpi-num">$<?= number_format($resumen['ingresos_total'], 0, ',', '.') ?></div>
            <div class="kpi-label">Ingresos totales</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-num"><?= number_format($resumen['total_compras']) ?></div>
            <div class="kpi-label">Total compras</div>
        </div>
        <div class="kpi-card accent">
            <div class="kpi-num">$<?= number_format($resumen['egresos_total'], 0, ',', '.') ?></div>
            <div class="kpi-label">Egresos totales</div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-num"><?= number_format($resumen['clientes_activos']) ?></div>
            <div class="kpi-label">Clientes activos</div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-num"><?= number_format($resumen['productos_activos']) ?></div>
            <div class="kpi-label">Productos activos</div>
        </div>
        <div class="kpi-card red">
            <div class="kpi-num"><?= number_format($resumen['stock_bajo']) ?></div>
            <div class="kpi-label">Productos stock bajo</div>
        </div>
        <div class="kpi-card red">
            <div class="kpi-num"><?= number_format($resumen['sin_stock']) ?></div>
            <div class="kpi-label">Sin stock</div>
        </div>
    </div>

    <!-- ── Tabs ── -->
    <ul class="nav rep-tabs mb-0 no-print" id="repTabs">
        <?php foreach ([
            'ventas'    => '📋 Ventas',
            'compras'   => '🛒 Compras',
            'inventario'=> '📦 Inventario',
            'top'       => '🏆 Más vendidos',
        ] as $key => $label): ?>
        <li class="nav-item">
            <a class="nav-link <?= $tab === $key ? 'active' : '' ?>"
               href="?view=reportes&tab=<?= h($key) ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&busqueda=<?= urlencode($busqueda) ?>">
                <?= h($label) ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <!-- ── Filtros ── -->
    <form method="GET" action="index.php" class="filtros-bar no-print">
        <input type="hidden" name="view" value="reportes">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <div class="row g-2 align-items-end">

            <!-- Búsqueda general -->
            <div class="col-md-3">
                <label for="fld-busqueda" class="form-label small fw-semibold mb-1">🔍 Búsqueda rápida</label>
                <input id="fld-busqueda" class="form-control form-control-sm" name="busqueda"
                       placeholder="Código, cliente, proveedor, producto..."
                       value="<?= htmlspecialchars($busqueda) ?>">
            </div>

            <!-- Desde -->
            <div class="col-md-2">
                <label for="fld-desde" class="form-label small fw-semibold mb-1">Desde</label>
                <input id="fld-desde" class="form-control form-control-sm" type="date" name="desde"
                       value="<?= htmlspecialchars($desde) ?>">
            </div>

            <!-- Hasta -->
            <div class="col-md-2">
                <label for="fld-hasta" class="form-label small fw-semibold mb-1">Hasta</label>
                <input id="fld-hasta" class="form-control form-control-sm" type="date" name="hasta"
                       value="<?= htmlspecialchars($hasta) ?>">
            </div>

            <!-- Cliente (solo tab ventas) -->
            <?php if ($tab === 'ventas'): ?>
            <div class="col-md-2">
                <label for="fld-cliente_id" class="form-label small fw-semibold mb-1">Cliente</label>
                <select id="fld-cliente_id" class="form-select form-select-sm" name="cliente_id">
                    <option value="">— Todos —</option>
                    <?php foreach ($clientes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $cliente_id == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Proveedor (solo tab compras) -->
            <?php if ($tab === 'compras'): ?>
            <div class="col-md-2">
                <label for="fld-proveedor_id" class="form-label small fw-semibold mb-1">Proveedor</label>
                <select id="fld-proveedor_id" class="form-select form-select-sm" name="proveedor_id">
                    <option value="">— Todos —</option>
                    <?php foreach ($proveedores as $pv): ?>
                    <option value="<?= $pv['id'] ?>" <?= $proveedor_id == $pv['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($pv['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-md-auto">
                <button class="btn btn-sm text-white" style="background:var(--ct-brand);">
                    Filtrar
                </button>
                <?php if ($hay_filtros): ?>
                <a href="?view=reportes&tab=<?= h($tab) ?>" class="btn btn-sm btn-outline-secondary ms-1">
                    Limpiar
                </a>
                <?php endif; ?>
            </div>

        </div>
    </form>

    <!-- ══════════════════════════════════════════════════════════
         TAB: VENTAS
    ══════════════════════════════════════════════════════════ -->
    <?php if ($tab === 'ventas'): ?>
    <div class="rep-card">
        <h6 class="fw-bold mb-3" style="color:var(--ct-brand);">
            Reporte de Ventas por Cliente
            <?php if ($hay_filtros): ?>
            <span class="badge ms-2" style="background:var(--ct-accent);font-size:.72rem;">Filtrado</span>
            <?php endif; ?>
        </h6>

        <!-- Totales -->
        <?php if ($tot_ventas['total_transacciones'] > 0): ?>
        <div class="totales-bar">
            <div class="t-item"><strong><?= number_format($tot_ventas['total_transacciones']) ?></strong><br><small class="text-muted">Transacciones</small></div>
            <div class="t-item"><strong>$<?= number_format($tot_ventas['suma_subtotal'], 0, ',', '.') ?></strong><br><small class="text-muted">Subtotal</small></div>
            <div class="t-item"><strong>$<?= number_format($tot_ventas['suma_iva'], 0, ',', '.') ?></strong><br><small class="text-muted">IVA total</small></div>
            <div class="t-item"><strong>$<?= number_format($tot_ventas['suma_total'], 0, ',', '.') ?></strong><br><small class="text-muted">Total ingresos</small></div>
            <div class="t-item"><strong>$<?= number_format($tot_ventas['promedio_venta'], 0, ',', '.') ?></strong><br><small class="text-muted">Venta promedio</small></div>
            <div class="t-item"><strong>$<?= number_format($tot_ventas['venta_maxima'], 0, ',', '.') ?></strong><br><small class="text-muted">Venta máxima</small></div>
        </div>
        <?php endif; ?>

        <?php if (empty($ventas)): ?>
            <p class="text-muted text-center py-4">No hay ventas con los filtros aplicados.</p>
        <?php else: ?>
        <!-- Encabezado visible solo al imprimir -->
        <div class="rep-print-header" style="display:none;">
            <h2>Chocolate Tumaco &mdash; Reporte de Ventas</h2>
            <p>Generado el <?= date('d/m/Y H:i') ?><?= $desde || $hasta ? ' &nbsp;|&nbsp; Período: '.($desde?date('d/m/Y',strtotime($desde)):'inicio').' – '.($hasta?date('d/m/Y',strtotime($hasta)):'hoy') : '' ?></p>
        </div>
        <div class="rep-print-footer" style="display:none;">
            <span>ChocoTumac &copy; <?= date('Y') ?></span>
            <span>Reporte de Ventas &mdash; <?= date('d/m/Y H:i') ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle rep-table tbl-ventas mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Documento</th>
                        <th>Producto</th>
                        <th class="text-center">Cantidad</th>
                        <th>Unidad</th>
                        <th class="text-end">Precio unit.</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-center">IVA %</th>
                        <th class="text-end">IVA $</th>
                        <th class="text-end fw-bold">Total</th>
                        <th>Pago</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ventas as $v): ?>
                <tr>
                    <td class="fw-semibold text-nowrap" style="color:var(--ct-brand);">
                        <?= htmlspecialchars($v['codigo']) ?>
                    </td>
                    <td class="text-nowrap"><?= date('d/m/Y', strtotime($v['fecha'])) ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($v['cliente']) ?></td>
                    <td class="text-muted">
                        <?= $v['doc_tipo'] && $v['doc_num']
                            ? htmlspecialchars($v['doc_tipo'] . ' ' . $v['doc_num'])
                            : '—' ?>
                    </td>
                    <td><?= htmlspecialchars($v['producto']) ?></td>
                    <td class="text-center"><?= number_format($v['cantidad'], 2) ?></td>
                    <td><?= htmlspecialchars($v['unidad']) ?></td>
                    <td class="text-end">$<?= number_format($v['precio_unitario'], 0, ',', '.') ?></td>
                    <td class="text-end">$<?= number_format($v['subtotal'], 0, ',', '.') ?></td>
                    <td class="text-center"><?= number_format($v['iva_porcentaje'], 0) ?>%</td>
                    <td class="text-end text-muted">$<?= number_format($v['iva_valor'], 0, ',', '.') ?></td>
                    <td class="text-end fw-bold text-success">$<?= number_format($v['total'], 0, ',', '.') ?></td>
                    <td>
                        <span class="badge <?= $v['forma_pago'] === 'contado' ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= h(ucfirst($v['forma_pago'])) ?>
                        </span>
                    </td>
                    <td class="text-muted"><?= htmlspecialchars($v['registrado_por']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted text-end mt-2 mb-0" style="font-size:.78rem;">
            <?= count($ventas) ?> registro(s) encontrado(s)
        </p>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         TAB: COMPRAS
    ══════════════════════════════════════════════════════════ -->
    <?php elseif ($tab === 'compras'): ?>
    <div class="rep-card">
        <h6 class="fw-bold mb-3" style="color:var(--ct-brand);">
            Reporte de Compras por Proveedor
            <?php if ($hay_filtros): ?>
            <span class="badge ms-2" style="background:var(--ct-accent);font-size:.72rem;">Filtrado</span>
            <?php endif; ?>
        </h6>

        <?php if ($tot_compras['total_transacciones'] > 0): ?>
        <div class="totales-bar">
            <div class="t-item"><strong><?= number_format($tot_compras['total_transacciones']) ?></strong><br><small class="text-muted">Transacciones</small></div>
            <div class="t-item"><strong>$<?= number_format($tot_compras['suma_total'], 0, ',', '.') ?></strong><br><small class="text-muted">Total pagado</small></div>
            <div class="t-item"><strong>$<?= number_format($tot_compras['promedio_compra'], 0, ',', '.') ?></strong><br><small class="text-muted">Compra promedio</small></div>
            <div class="t-item"><strong>$<?= number_format($tot_compras['compra_maxima'], 0, ',', '.') ?></strong><br><small class="text-muted">Compra máxima</small></div>
        </div>
        <?php endif; ?>

        <?php if (empty($compras)): ?>
            <p class="text-muted text-center py-4">No hay compras con los filtros aplicados.</p>
        <?php else: ?>
        <!-- Encabezado visible solo al imprimir -->
        <div class="rep-print-header" style="display:none;">
            <h2>Chocolate Tumaco &mdash; Reporte de Compras</h2>
            <p>Generado el <?= date('d/m/Y H:i') ?><?= $desde || $hasta ? ' &nbsp;|&nbsp; Período: '.($desde?date('d/m/Y',strtotime($desde)):'inicio').' – '.($hasta?date('d/m/Y',strtotime($hasta)):'hoy') : '' ?></p>
        </div>
        <div class="rep-print-footer" style="display:none;">
            <span>ChocoTumac &copy; <?= date('Y') ?></span>
            <span>Reporte de Compras &mdash; <?= date('d/m/Y H:i') ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle rep-table tbl-compras mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>Documento</th>
                        <th>Producto</th>
                        <th class="text-center">Cantidad</th>
                        <th>Unidad</th>
                        <th class="text-end">Precio unit.</th>
                        <th class="text-end fw-bold">Total</th>
                        <th>Observaciones</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($compras as $c): ?>
                <tr>
                    <td class="fw-semibold text-nowrap" style="color:var(--ct-brand);">
                        <?= htmlspecialchars($c['codigo']) ?>
                    </td>
                    <td class="text-nowrap"><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($c['proveedor']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($c['proveedor_doc'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($c['producto']) ?></td>
                    <td class="text-center"><?= number_format($c['cantidad'], 2) ?></td>
                    <td><?= htmlspecialchars($c['unidad']) ?></td>
                    <td class="text-end">$<?= number_format($c['precio_unitario'], 0, ',', '.') ?></td>
                    <td class="text-end fw-bold text-danger">$<?= number_format($c['total'], 0, ',', '.') ?></td>
                    <td class="text-muted"><?= htmlspecialchars($c['observaciones'] ?? '—') ?></td>
                    <td class="text-muted"><?= htmlspecialchars($c['registrado_por']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted text-end mt-2 mb-0" style="font-size:.78rem;">
            <?= count($compras) ?> registro(s) encontrado(s)
        </p>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         TAB: INVENTARIO
    ══════════════════════════════════════════════════════════ -->
    <?php elseif ($tab === 'inventario'): ?>
    <div class="rep-card">
        <h6 class="fw-bold mb-3" style="color:var(--ct-brand);">
            Inventario Actualizado en Tiempo Real
        </h6>

        <?php if (empty($inventario)): ?>
            <p class="text-muted text-center py-4">No hay productos registrados.</p>
        <?php else: ?>
        <!-- Encabezado visible solo al imprimir -->
        <div class="rep-print-header" style="display:none;">
            <h2>Chocolate Tumaco &mdash; Inventario Actualizado</h2>
            <p>Generado el <?= date('d/m/Y H:i') ?></p>
        </div>
        <div class="rep-print-footer" style="display:none;">
            <span>ChocoTumac &copy; <?= date('Y') ?></span>
            <span>Inventario &mdash; <?= date('d/m/Y H:i') ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle rep-table tbl-inventario mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Presentación</th>
                        <th>Unidad inv.</th>
                        <th>Unidad venta</th>
                        <th class="text-end">Stock actual</th>
                        <th class="text-end">Stock mín.</th>
                        <th class="text-end">Precio venta</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Movimientos</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($inventario as $i => $p): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($p['tipo']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($p['presentacion'] ?? '—') ?></td>
                    <td><span class="badge bg-secondary"><?= h($p['unidad']) ?></span></td>
                    <td><span class="badge bg-info text-dark"><?= h($p['unidad_venta']) ?></span></td>
                    <td class="text-end fw-bold"><?= number_format($p['stock_actual'], 2) ?></td>
                    <td class="text-end text-muted"><?= number_format($p['stock_minimo'], 2) ?></td>
                    <td class="text-end">$<?= number_format($p['precio_venta'], 0, ',', '.') ?></td>
                    <td class="text-center">
                        <?php
                        $labels = [
                            'ok'         => ['bg-success', 'Stock OK'],
                            'stock_bajo' => ['bg-warning text-dark', 'Stock bajo'],
                            'sin_stock'  => ['bg-danger',  'Sin stock'],
                        ];
                        [$badge_class, $badge_text] = $labels[$p['estado_stock']] ?? ['bg-secondary', '—'];
                        ?>
                        <span class="badge <?= $badge_class ?>"><?= $badge_text ?></span>
                    </td>
                    <td class="text-center text-muted"><?= number_format($p['total_movimientos']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted text-end mt-2 mb-0" style="font-size:.78rem;">
            <?= count($inventario) ?> producto(s) · Actualizado: <?= date('d/m/Y H:i') ?>
        </p>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         TAB: PRODUCTOS MÁS VENDIDOS
    ══════════════════════════════════════════════════════════ -->
    <?php elseif ($tab === 'top'): ?>
    <div class="row g-3">

        <!-- Tabla top 10 -->
        <div class="col-lg-7">
            <div class="rep-card h-100">
                <h6 class="fw-bold mb-3" style="color:var(--ct-brand);">
                    🏆 Top 10 Productos Más Vendidos
                    <small class="text-muted fw-normal" style="font-size:.78rem;">
                        — por cantidad total
                        <?= $desde || $hasta ? '· ' . ($desde ? date('d/m/Y', strtotime($desde)) : '') . ($hasta ? ' a ' . date('d/m/Y', strtotime($hasta)) : '') : '' ?>
                    </small>
                </h6>

                <?php if (empty($top_prod)): ?>
                    <p class="text-muted text-center py-4">No hay ventas registradas en el período.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle rep-table tbl-top mb-0">
                        <thead>
                            <tr>
                                <th style="width:35px">#</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th class="text-center">Ventas</th>
                                <th class="text-center">Cantidad</th>
                                <th>Unidad</th>
                                <th class="text-end">Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($top_prod as $pos => $tp): ?>
                        <tr>
                            <td class="text-center fw-bold" style="color:<?= $pos === 0 ? '#C8860A' : ($pos === 1 ? '#666' : ($pos === 2 ? '#a05a2c' : '#999')) ?>">
                                <?= $pos < 3 ? ['🥇','🥈','🥉'][$pos] : ($pos+1) ?>
                            </td>
                            <td class="fw-semibold"><?= htmlspecialchars($tp['producto']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($tp['tipo']) ?></td>
                            <td class="text-center"><?= number_format($tp['num_ventas']) ?></td>
                            <td class="text-center fw-bold"><?= number_format($tp['cantidad_total'], 2) ?></td>
                            <td><?= htmlspecialchars($tp['unidad']) ?></td>
                            <td class="text-end text-success fw-bold">
                                $<?= number_format($tp['ingresos_total'], 0, ',', '.') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Gráfico de barras -->
        <div class="col-lg-5">
            <div class="rep-card h-100">
                <h6 class="fw-bold mb-3" style="color:var(--ct-brand);">
                    Cantidad vendida por producto
                </h6>

                <?php if (empty($top_prod)): ?>
                    <p class="text-muted text-center py-4">Sin datos.</p>
                <?php else:
                    $max_cant = max(array_column($top_prod, 'cantidad_total'));
                    ?>
                <?php foreach ($top_prod as $pos => $tp):
                    $pct = $max_cant > 0 ? round($tp['cantidad_total'] / $max_cant * 100) : 0;
                    $nombre_corto = strlen($tp['producto']) > 22 ? substr($tp['producto'], 0, 20).'…' : $tp['producto'];
                ?>
                <div class="bar-wrap">
                    <div class="bar-label d-flex justify-content-between">
                        <span class="fw-semibold"><?= htmlspecialchars($nombre_corto) ?></span>
                        <span class="text-muted"><?= number_format($tp['cantidad_total'], 2) ?> <?= $tp['unidad'] ?></span>
                    </div>
                    <div class="bar-bg">
                        <div class="bar-fill" style="width:<?= $pct ?>%">
                            <?php if ($pct > 15): ?>
                            <span class="bar-val"><?= $pct ?>%</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <?php endif; ?>

</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>