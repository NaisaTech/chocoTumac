/**
 * Alterna entre el desplegable de cliente registrado
 * y el campo de texto para cliente ocasional según el radio seleccionado.
 */
document.querySelectorAll('input[name="tipo_cliente"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        const esOcasional = this.value === 'ocasional';

        const divRegistrado = document.getElementById('div-cliente-registrado');
        const divOcasional = document.getElementById('div-cliente-ocasional');
        const divDoc = document.getElementById('div-doc-ocasional');
        const selCliente = document.getElementById('sel-cliente');

        divRegistrado.style.display = esOcasional ? 'none' : '';
        divOcasional.style.display = esOcasional ? '' : 'none';
        divDoc.style.display = esOcasional ? '' : 'none';

        if (esOcasional) {
            selCliente.value = '';
        } else {
            document.getElementById('inp-cliente-ocasional').value = '';
            document.getElementById('inp-doc-num').value = '';
        }
    });
});

/**
 * Al seleccionar un producto, actualiza automáticamente:
 *   - Unidad de medida
 *   - Stock disponible actual
 *   - Precio de venta sugerido
 *   - Total calculado
 */
document.getElementById('sel-producto-venta')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const stock = opt.dataset.stock || '';
    const unidad = opt.dataset.unidad || '';  // unidad del inventario
    const unidadVenta = opt.dataset.unidadVenta || 'und'; // unidad de la factura
    const precio = opt.dataset.precio || '';

    const txtUnidad = document.getElementById('txt-unidad-venta');
    const txtStock = document.getElementById('txt-stock-disp');
    const inpPrecio = document.getElementById('inp-precio-venta');
    const inpCant = document.getElementById('inp-cant-venta');

    if (txtUnidad) txtUnidad.value = unidadVenta;
    if (txtStock) txtStock.value = stock
        ? parseFloat(stock).toFixed(unidad === 'und' ? 0 : 2) + ' ' + unidad : '—';
    if (inpPrecio && precio) inpPrecio.value = precio;

    // Ajustar cantidad: enteros para und, decimales para kg/g/lb
    if (inpCant) {
        const esUnidad = (unidad === 'und');
        inpCant.step = esUnidad ? '1' : '0.01';
        inpCant.min = esUnidad ? '1' : '0.01';
        inpCant.placeholder = esUnidad ? '0' : '0.00';
        inpCant.value = '';
    }

    calcularTotalVenta();
});

/**
 * Calcula el total de la venta en tiempo real (cantidad × precio).
 */
function calcularTotalVenta() {
    const cantidad = parseFloat(document.getElementById('inp-cant-venta')?.value) || 0;
    const precio = parseFloat(document.getElementById('inp-precio-venta')?.value) || 0;
    const ivaPct = parseFloat(document.getElementById('sel-iva')?.value) || 0;
    const subtotal = cantidad * precio;
    const ivaValor = subtotal * ivaPct / 100;
    const total = document.getElementById('inp-total-venta');
    if (total) {
        total.value = (subtotal + ivaValor).toLocaleString('es-CO', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }
}

document.getElementById('inp-cant-venta')?.addEventListener('input', calcularTotalVenta);
document.getElementById('inp-precio-venta')?.addEventListener('input', calcularTotalVenta);
document.getElementById('sel-iva')?.addEventListener('change', calcularTotalVenta);