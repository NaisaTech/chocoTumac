<?php
/**
 * Vista: Factura de Venta – ChocoTumac Sprint 2.
 *
 * Cumple los campos mínimos exigidos por la DIAN para factura de venta
 * según Resolución 42 de 2020 y Decreto 1929 de 2007:
 *   - Identificación del emisor (NIT, razón social, régimen, dirección)
 *   - Identificación del adquirente (doc, nombre, dirección)
 *   - Numeración consecutiva autorizada
 *   - Fecha y hora de expedición
 *   - Descripción, cantidad, unidad, precio unitario, subtotal
 *   - Desglose de impuestos (IVA)
 *   - Total en letras
 *   - Forma de pago
 *   - Firma / CUFE (simulado para ambiente no habilitado electrónicamente)
 *
 * @package ChocoTumac
 * @sprint  2
 */

if (!defined('CHOCOTUMAC_APP')) {
    header("Location: /chocoTumac/index.php"); exit();
}

require_once 'models/Venta.php';

$modelVenta = new Venta();
$id         = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$venta      = $modelVenta->obtenerPorId($id);

if (!$venta) {
    header("Location: /chocoTumac/index.php?view=ventas&error=" . urlencode("Factura no encontrada."));
    exit();
}

/* ── Unidad de venta ─────────────────────────────────────────────────── */
$unidad_venta = $venta['unidad_venta'] ?? 'und';

/* ── Documento del cliente ───────────────────────────────────────────── */
$doc_tipo = '';
$doc_num  = '';
if ($venta['cliente_id'] && !empty($venta['cliente_num_doc'])) {
    $doc_tipo = $venta['cliente_tipo_doc'];
    $doc_num  = $venta['cliente_num_doc'];
    if ($doc_tipo === 'NIT' && !empty($venta['cliente_digito_ver'])) {
        $doc_num .= '-' . $venta['cliente_digito_ver'];
    }
} elseif (!$venta['cliente_id'] && !empty($venta['doc_ocasional_num'])) {
    $doc_tipo = $venta['doc_ocasional_tipo'];
    $doc_num  = $venta['doc_ocasional_num'];
}

/* ── Subtotal / IVA / Total ──────────────────────────────────────────── */
$subtotal    = isset($venta['subtotal'])       ? (float)$venta['subtotal']       : (float)$venta['total'];
$iva_pct     = isset($venta['iva_porcentaje']) ? (float)$venta['iva_porcentaje'] : 0;
$iva_valor   = isset($venta['iva_valor'])      ? (float)$venta['iva_valor']      : 0;
$total       = (float)$venta['total'];
$forma_pago  = $venta['forma_pago'] ?? 'contado';

/* ── Total en letras (helper simple) ────────────────────────────────── */
function numeroALetras(float $n): string {
    $entero = (int)floor($n);
    $cents  = round(($n - $entero) * 100);
    $u = ['','un','dos','tres','cuatro','cinco','seis','siete','ocho','nueve',
          'diez','once','doce','trece','catorce','quince','dieciséis','diecisiete',
          'dieciocho','diecinueve'];
    $d = ['','','veinte','treinta','cuarenta','cincuenta','sesenta','setenta','ochenta','noventa'];
    $c = ['','ciento','doscientos','trescientos','cuatrocientos','quinientos',
          'seiscientos','setecientos','ochocientos','novecientos'];

    $conv = function(int $n) use ($u,$d,$c,&$conv): string {
        if ($n === 0)  return 'cero';
        if ($n === 100) return 'cien';
        if ($n < 20)   return $u[$n];
        if ($n < 100) {
            $r = $d[(int)($n/10)];
            return $n%10 ? $r.' y '.$u[$n%10] : $r;
        }
        $r = $c[(int)($n/100)];
        return $n%100 ? $r.' '.$conv($n%100) : $r;
    };

    if ($entero >= 1000000) {
        $mill = (int)floor($entero/1000000);
        $rest = $entero % 1000000;
        $s = ($mill===1 ? 'un millón' : $conv($mill).' millones');
        if ($rest) $s .= ' '.$conv($rest);
    } elseif ($entero >= 1000) {
        $mil  = (int)floor($entero/1000);
        $rest = $entero % 1000;
        $s = ($mil===1 ? 'mil' : $conv($mil).' mil');
        if ($rest) $s .= ' '.$conv($rest);
    } else {
        $s = $conv($entero);
    }

    return strtoupper($s) . ($cents > 0 ? ' CON ' . str_pad($cents,2,'0',STR_PAD_LEFT) . '/100' : '') . ' M/CTE';
}

$total_letras = numeroALetras($total);

/* ── CUFE simulado (en producción se genera con WS DIAN) ────────────── */
$cufe_simulado = strtoupper(md5($venta['codigo'] . $venta['fecha'] . $total . '900000000'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Factura <?= htmlspecialchars($venta['codigo']) ?> – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/chocoTumac/public/css/styles.css">
</head>
<body>

<div class="factura-wrapper">

    <!-- ══ ENCABEZADO ══════════════════════════════════════════════════ -->
    <div class="factura-header">
        <div>
            <div class="factura-logo">
                Chocolate Tumaco
                <small>Artesanos del cacao fino de aroma</small>
            </div>
        </div>
        <div class="factura-codigo-box">
            <div class="factura-tipo-doc">FACTURA DE VENTA</div>
            <div class="factura-codigo"><?= htmlspecialchars($venta['codigo']) ?></div>
            <div class="factura-fecha">
                Fecha: <?= date('d/m/Y', strtotime($venta['fecha'])) ?>
                &nbsp;·&nbsp;
                Hora: <?= date('H:i', strtotime($venta['created_at'])) ?>
            </div>
        </div>
    </div>

    <!-- ══ CUERPO ═══════════════════════════════════════════════════════ -->
    <div class="factura-body">

        <!-- ── Emisor / Adquirente ─────────────────────────────────── -->
        <div class="factura-partes">

            <!-- EMISOR -->
            <div class="factura-parte">
                <h6>Emisor</h6>
                <p>
                    <span class="nombre">Chocolate Tumaco</span><br>
                    <span class="fac-label">NIT:</span> 900.000.000-0<br>
                    <span class="fac-label">Régimen:</span> Responsable de IVA<br>
                    <span class="fac-label">Actividad CIIU:</span> 1082 – Elaboración cacao y chocolate<br>
                    <span class="fac-label">Dirección:</span> Calle Principal # 5-32, Centro<br>
                    <span class="fac-label">Ciudad:</span> Tumaco, Nariño – Colombia<br>
                    <span class="fac-label">Tel:</span> (602) 727-0000 · 310 000 0000<br>
                    <span class="fac-label">Email:</span> contacto@chocolatetumaco.com
                </p>
            </div>

            <!-- ADQUIRENTE -->
            <div class="factura-parte">
                <h6>Adquirente</h6>
                <p>
                    <span class="nombre"><?= htmlspecialchars($venta['cliente_nombre']) ?></span><br>
                    <?php if ($doc_tipo && $doc_num): ?>
                        <span class="fac-label"><?= htmlspecialchars($doc_tipo) ?>:</span>
                        <?= htmlspecialchars($doc_num) ?><br>
                    <?php else: ?>
                        <span class="text-muted" style="font-size:.8rem;">Sin identificación registrada</span><br>
                    <?php endif; ?>
                    <?php if (!empty($venta['cliente_direccion'])): ?>
                        <span class="fac-label">Dirección:</span> <?= htmlspecialchars($venta['cliente_direccion']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($venta['cliente_ciudad'])): ?>
                        <span class="fac-label">Ciudad:</span>
                        <?= htmlspecialchars($venta['cliente_ciudad']) ?>
                        <?= !empty($venta['cliente_departamento']) ? ', '.htmlspecialchars($venta['cliente_departamento']) : '' ?><br>
                    <?php endif; ?>
                    <?php if (!empty($venta['cliente_telefono'])): ?>
                        <span class="fac-label">Tel:</span> <?= htmlspecialchars($venta['cliente_telefono']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($venta['cliente_email'])): ?>
                        <span class="fac-label">Email:</span> <?= htmlspecialchars($venta['cliente_email']) ?><br>
                    <?php endif; ?>
                    <span class="fac-label">Forma de pago:</span>
                    <?= $forma_pago === 'contado' ? 'Contado' : 'Crédito' ?>
                </p>
            </div>
        </div>

        <!-- ── Tabla de ítems ──────────────────────────────────────── -->
        <table class="factura-tabla">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Descripción del producto / servicio</th>
                    <th class="text-center" style="width:80px">Cant.</th>
                    <th style="width:60px">Und.</th>
                    <th class="text-end" style="width:120px">Precio unit.</th>
                    <th class="text-end" style="width:120px">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td>
                        <?= htmlspecialchars($venta['producto_nombre']) ?>
                        <?php if (!empty($venta['producto_presentacion'])): ?>
                            <span class="text-muted" style="font-size:.8rem;">
                                – <?= htmlspecialchars($venta['producto_presentacion']) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= number_format($venta['cantidad'], 2) ?></td>
                    <td><?= htmlspecialchars($unidad_venta) ?></td>
                    <td class="text-end">$ <?= number_format($venta['precio_unitario'], 2, ',', '.') ?></td>
                    <td class="text-end">$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>

        <!-- ── Desglose tributario ─────────────────────────────────── -->
        <div class="factura-totales">
            <table class="factura-totales-tabla">
                <thead class="visually-hidden">
                    <tr>
                        <th scope="col">Concepto</th>
                        <th scope="col">Valor</th>
                    </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="fac-label">Subtotal (base gravable):</td>
                    <td class="text-end">$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                </tr>
                <?php if ($iva_pct > 0): ?>
                <tr>
                    <td class="fac-label">IVA <?= number_format($iva_pct, 0) ?>%:</td>
                    <td class="text-end">$ <?= number_format($iva_valor, 2, ',', '.') ?></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td class="fac-label text-muted" style="font-size:.8rem;">
                        IVA: Excluido / No aplica
                        <span style="font-size:.72rem;">(Art. 424 E.T.)</span>
                    </td>
                    <td class="text-end text-muted">$ 0,00</td>
                </tr>
                <?php endif; ?>
                <tr class="factura-total-row">
                    <td class="fac-label">TOTAL A PAGAR:</td>
                    <td class="text-end total-amount">$ <?= number_format($total, 2, ',', '.') ?></td>
                </tr>
            </table>
        </div>

        <!-- ── Total en letras ─────────────────────────────────────── -->
        <div class="factura-letras">
            <span class="fac-label">Son:</span>
            <?= htmlspecialchars($total_letras) ?>
        </div>

        <!-- ── Sello ───────────────────────────────────────────────── -->
        <div class="text-end mb-2">
            <div class="sello-pagado">✓ <?= strtoupper($forma_pago === 'contado' ? 'Pagado' : 'Crédito') ?></div>
        </div>

        <!-- ── Observaciones ──────────────────────────────────────── -->
        <?php if (!empty($venta['observaciones'])): ?>
        <div class="factura-obs">
            <strong>Observaciones:</strong> <?= htmlspecialchars($venta['observaciones']) ?>
        </div>
        <?php endif; ?>

        <!-- ── CUFE / Firma ────────────────────────────────────────── -->
        <div class="factura-cufe">
            <div class="fac-label" style="font-size:.7rem; color:#888;">
                CUFE (Código Único de Factura Electrónica):
            </div>
            <div style="font-size:.65rem; color:#aaa; word-break:break-all; font-family:monospace;">
                <?= $cufe_simulado ?>
            </div>
            <div style="font-size:.68rem; color:#bbb; margin-top:3px;">
                Documento equivalente – No habilitado electrónicamente ante la DIAN.
                Válido como soporte interno de transacción.
            </div>
        </div>

        <!-- ── Pie ─────────────────────────────────────────────────── -->
        <div class="factura-footer">
            Resolución de facturación No. 000000 de <?= date('Y') ?> · Rango autorizado: FAC-<?= date('Y') ?>-0001 a FAC-<?= date('Y') ?>-9999<br>
            Regido por: Estatuto Tributario Art. 615 y ss · Res. DIAN 042/2020<br>
            <strong>Chocolate Tumaco</strong> · Tumaco, Nariño, Colombia ·
            contacto@chocolatetumaco.com
        </div>

    </div><!-- /factura-body -->

    <!-- ── Barra de acciones (no se imprime) ── -->
    <div class="acciones-barra">
        <a href="/chocoTumac/index.php?view=ventas"
           class="btn btn-outline-secondary btn-sm">
            ← Volver a ventas
        </a>
        <button onclick="window.print()"
                class="btn btn-sm text-white"
                style="background:var(--ct-brand);">
            Imprimir / Guardar PDF
        </button>
    </div>

</div><!-- /factura-wrapper -->
</body>
</html>