/*editar producto - compras*/
document.getElementById('sel-tipo-edit')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const unidad = opt.dataset.unidad || '—';
    const requiere = opt.dataset.requierePresentacion === '1';

    const divPres = document.getElementById('div-pres-edit');
    const inpPres = document.getElementById('inp-pres-edit');
    const txtUnidad = document.getElementById('txt-unidad-edit');

    if (divPres) divPres.style.display = requiere ? '' : 'none';
    if (inpPres) inpPres.required = requiere;
    if (txtUnidad) txtUnidad.value = unidad;
});



/**
 * Al cambiar el tipo de producto:
 * - Muestra u oculta el campo Presentación según requiere_presentacion del tipo
 * - Muestra la unidad de inventario del tipo seleccionado (solo lectura)
 */
document.getElementById('sel-tipo')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const unidad = opt.dataset.unidad || '—';
    const requiere = opt.dataset.requierePresentacion === '1';

    const divPres = document.getElementById('div-presentacion');
    const inpPres = document.getElementById('inp-presentacion');
    const txtUnidad = document.getElementById('txt-unidad-inv');
    const inpStockIni = document.getElementById('inp-stock-inicial');

    if (divPres) divPres.style.display = requiere ? '' : 'none';
    if (inpPres) inpPres.required = requiere;
    if (txtUnidad) txtUnidad.value = unidad;

    // Stock inicial: enteros para und, decimales para kg/g/lb
    if (inpStockIni) {
        const esUnidad = (unidad === 'und');
        inpStockIni.step = esUnidad ? '1' : '0.01';
        inpStockIni.placeholder = esUnidad ? '0' : '0.00';
    }
});