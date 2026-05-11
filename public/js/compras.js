/**
 * Al seleccionar producto, actualiza la unidad de medida automáticamente
 * según el tipo de producto (viene del data-unidad del option).
 */
document.getElementById('sel-producto-compra')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const unidad = opt.dataset.unidad || 'kg';
    const txtU = document.getElementById('txt-unidad-compra');
    const hidU = document.getElementById('hid-unidad');
    const inpC = document.getElementById('inp-cantidad');

    if (txtU) txtU.value = unidad;
    if (hidU) hidU.value = unidad;

    // Enteros para und, decimales para kg/g/lb
    if (inpC) {
        const esUnidad = (unidad === 'und');
        inpC.step = esUnidad ? '1' : '0.01';
        inpC.min = esUnidad ? '1' : '0.01';
        inpC.placeholder = esUnidad ? '0' : '0.00';
        inpC.value = '';
    }
});

/**
 * Calcula automáticamente el total de la compra
 * multiplicando cantidad × precio unitario en tiempo real.
 */
function calcularTotal() {
    const cantidad = parseFloat(document.getElementById('inp-cantidad')?.value) || 0;
    const precio = parseFloat(document.getElementById('inp-precio')?.value) || 0;
    const total = document.getElementById('inp-total');
    if (total) {
        total.value = (cantidad * precio).toLocaleString('es-CO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}

document.getElementById('inp-cantidad')?.addEventListener('input', calcularTotal);
document.getElementById('inp-precio')?.addEventListener('input', calcularTotal);

