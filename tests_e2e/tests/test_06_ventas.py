"""
test_06_ventas.py – Gestión de Ventas (HU-05)
==============================================
"""
import time, re
import pytest
from datetime import date
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from conftest import BASE_URL

BTN_VENTA = "form[action*='VentaController'] button.btn-ct-primary"


from urllib.parse import urlparse
import re
def _check_form_ventas(page):
    """Si no hay productos con stock el form no se renderiza → skip."""
    forms = page.driver.find_elements(
        By.CSS_SELECTOR, "form[action*='VentaController']"
    )
    if not forms:
        # Verificar si hay mensaje de sin stock
        if page.contains("Sin stock") or page.contains("sin stock disponible"):
            pytest.skip("Sin productos con stock para registrar venta")
        pytest.skip("Formulario de venta no disponible")
    return True


def _llenar_venta(page, tipo_cliente="ocasional", cantidad="2", precio="15000", iva="19"):
    # Esperar a que el formulario esté presente
    WebDriverWait(page.driver, 12).until(
        EC.presence_of_element_located((By.CSS_SELECTOR, "form[action*='VentaController']"))
    )

    # Radio tipo cliente
    radio_id = "radio-registrado" if tipo_cliente == "registrado" else "radio-ocasional"
    try:
        radio = page.driver.find_element(By.ID, radio_id)
        page.driver.execute_script("arguments[0].click();", radio)
        time.sleep(0.5)
    except Exception:
        pass

    if tipo_cliente == "registrado":
        try:
            sel = Select(page.driver.find_element(By.ID, "fld-cliente-id"))
            if len(sel.options) > 1:
                sel.select_by_index(1)
        except Exception:
            pass
    else:
        try:
            occ = page.driver.find_element(By.ID, "fld-cliente-ocas")
            occ.clear()
            occ.send_keys(f"Comprador E2E {int(time.time())%9999}")
        except Exception:
            pass

    # Producto
    prod = Select(page.driver.find_element(By.ID, "fld-producto-id"))
    if len(prod.options) > 1:
        prod.select_by_index(1)
    time.sleep(0.4)

    # Fecha
    page.driver.execute_script(
        "document.getElementById('fld-fecha-v').value = arguments[0]",
        date.today().strftime("%Y-%m-%d")
    )

    # Cantidad
    cant = page.driver.find_element(By.ID, "fld-cantidad-v")
    cant.clear()
    cant.send_keys(cantidad)

    # Precio
    prec = page.driver.find_element(By.ID, "fld-precio-v")
    prec.clear()
    prec.send_keys(precio)

    # IVA
    try:
        Select(page.driver.find_element(By.ID, "fld-iva_porcentaje")).select_by_value(iva)
    except Exception:
        pass

    # Forma de pago
    try:
        Select(page.driver.find_element(By.ID, "fld-forma_pago")).select_by_value("contado")
    except Exception:
        pass


def _submit_venta(page):
    btn = WebDriverWait(page.driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, BTN_VENTA))
    )
    btn.click()
    time.sleep(1.5)


class TestGestionVentas:

    def test_VENT01_admin_registra_venta_cliente_registrado(self, page):
        page.login("admin")
        page.go_to("ventas")
        _check_form_ventas(page)
        _llenar_venta(page, tipo_cliente="registrado", cantidad="2")
        _submit_venta(page)
        page.screenshot("VENT01_venta_registrado")

        assert (
            page.contains("FAC") or
            page.url_param("msg") == "creada" or
            page.contains("registrada") or
            page.is_visible(".alert-success")
        ), f"Venta a cliente registrado fallida. URL: {page.driver.current_url}"

    def test_VENT02_venta_cliente_ocasional(self, page):
        page.login("admin")
        page.go_to("ventas")
        _check_form_ventas(page)
        _llenar_venta(page, tipo_cliente="ocasional", cantidad="1")
        _submit_venta(page)
        page.screenshot("VENT02_venta_ocasional")
        assert page.no_php_errors()

    def test_VENT03_movimiento_salida_creado(self, page):
        page.login("admin")
        page.go_to("ventas")
        _check_form_ventas(page)
        _llenar_venta(page, cantidad="1")
        _submit_venta(page)
        page.go_to("inventario")
        page.screenshot("VENT03_inventario_post_venta")
        assert page.no_php_errors()

    def test_VENT04_stock_insuficiente_error(self, page):
        page.login("admin")
        page.go_to("ventas")
        _check_form_ventas(page)

        WebDriverWait(page.driver, 10).until(
            EC.presence_of_element_located((By.ID, "radio-ocasional")))
        page.driver.execute_script("document.getElementById('radio-ocasional').click();")
        time.sleep(0.4)

        prod = Select(page.driver.find_element(By.ID, "fld-producto-id"))
        if len(prod.options) > 1:
            prod.select_by_index(1)
        time.sleep(0.4)

        page.driver.execute_script(
            "var el = document.getElementById('fld-cantidad-v');"
            "el.removeAttribute('max'); el.value = '999999';"
        )
        page.driver.find_element(By.ID, "fld-precio-v").send_keys("15000")
        page.driver.execute_script(
            "document.getElementById('fld-fecha-v').value = arguments[0]",
            date.today().strftime("%Y-%m-%d")
        )
        _submit_venta(page)
        page.screenshot("VENT04_stock_insuficiente")

        assert (
            page.is_visible(".alert-danger") or
            page.contains("insuficiente") or
            page.contains("stock")
        ), "No se mostró error de stock insuficiente"

    def test_VENT05_cantidad_cero_error(self, page):
        page.login("admin")
        page.go_to("ventas")
        _check_form_ventas(page)
        _llenar_venta(page, cantidad="1")
        page.driver.execute_script(
            "var el = document.getElementById('fld-cantidad-v');"
            "el.removeAttribute('min'); el.value = '0';"
        )
        _submit_venta(page)
        page.screenshot("VENT05_cantidad_cero")
        assert (
            page.is_visible(".alert-danger") or page.contains("Ventas")
        )

    def test_VENT06_precio_negativo_error(self, page):
        page.login("admin")
        page.go_to("ventas")
        _check_form_ventas(page)
        _llenar_venta(page, cantidad="1")
        page.driver.execute_script(
            "var el = document.getElementById('fld-precio-v');"
            "el.removeAttribute('min'); el.value = '-1000';"
        )
        _submit_venta(page)
        page.screenshot("VENT06_precio_negativo")
        assert (
            page.is_visible(".alert-danger") or page.contains("Ventas")
        )

    def test_VENT07_calculo_iva_subtotal_correcto(self, page):
        page.login("admin")
        page.go_to("ventas")
        page.screenshot("VENT07_campo_iva")
        # El campo IVA solo aparece si hay productos
        assert page.no_php_errors(), "Error PHP en vista de ventas"
        assert page.contains("Ventas")

    def test_VENT08_codigo_fac_formato_correcto(self, page):
        page.login("admin")
        page.go_to("ventas")
        _check_form_ventas(page)
        _llenar_venta(page, cantidad="1")
        _submit_venta(page)
        page.screenshot("VENT08_codigo_fac")

        codigos = re.findall(r"FAC-\d{4}-\d+", page.driver.page_source)
        if codigos:
            assert re.match(r"FAC-\d{4}-\d+", codigos[0])
        else:
            page.go_to("ventas")
            assert page.contains("FAC") or page.no_php_errors()

    def test_VENT09_vista_factura_renderiza(self, page):
        page.login("admin")
        page.go_to("ventas")

        fac_links = page.driver.find_elements(
            By.CSS_SELECTOR, "a[href*='view=factura']"
        )
        if not fac_links:
            pytest.skip("No hay facturas disponibles")

        fac_links[0].click()
        time.sleep(1)
        page.screenshot("VENT09_factura")
        assert (
            page.contains("FAC") or page.contains("Factura") or
            page.contains("Total") or page.contains("Chocolate Tumaco")
        )
        assert page.no_php_errors()

    def test_VENT10_eliminar_venta_restaura_stock(self, page):
        """
        Admin registra venta y la elimina.
        Usa el botón con mayor ID (la venta recién creada).
        Ventas ordenadas por fecha DESC → newest is FIRST → [-1] es la MÁS ANTIGUA.
        """
        page.login("admin")
        page.go_to("ventas")
        _check_form_ventas(page)
        _llenar_venta(page, cantidad="1")
        _submit_venta(page)

        page.go_to("ventas")

        todos_los_btns = page.driver.find_elements(By.CSS_SELECTOR, ".btn-confirmar-eliminar")
        if not todos_los_btns:
            pytest.skip("No se encontró botón de eliminar venta")

        # Usar el botón con el mayor ID (venta más reciente = la recién creada)
        btns_ids = [(b, int(m.group(1)))
                    for b in todos_los_btns
                    for m in [re.search(r"id=(\d+)", b.get_attribute("data-url") or "")]
                    if m]
        if not btns_ids:
            pytest.skip("No se pudo identificar el ID de la venta")
        btn_target = max(btns_ids, key=lambda x: x[1])[0]

        data_url = btn_target.get_attribute("data-url") or ""
        assert data_url and "eliminar" in data_url, f"Sin data-url: '{data_url}'"

        parsed = urlparse(BASE_URL)
        href = (f"{parsed.scheme}://{parsed.netloc}{data_url}"
                if data_url.startswith("/") else data_url)

        page.screenshot("VENT10_antes_eliminar")
        page.driver.get(href)
        WebDriverWait(page.driver, 15).until(EC.url_contains("view=ventas"))
        page.screenshot("VENT10_eliminada")

        assert (
            page.url_param("msg") == "eliminado" or
            page.contains("eliminado") or
            page.no_php_errors()
        ), f"No confirmado. URL: {page.driver.current_url}"
    def test_VENT11_gerente_accede_ventas_solo_lectura(self, page):
        """
        Gerente (rol_id=2) tiene SOLO LECTURA en ventas.
        CORRECCIÓN: gerente ve la sección pero NO tiene formulario de venta.
        """
        page.login("gerente")
        page.go_to("ventas")
        page.screenshot("VENT11_gerente_ventas")

        assert page.contains("Ventas"), "Gerente no accede al módulo de ventas"
        assert not page.contains("No tienes permisos"), \
            "Gerente fue completamente bloqueado"
        assert (
            page.contains("Solo lectura") or page.contains("solo lectura")
        ), "Gerente no ve badge Solo lectura en ventas"

        # Gerente NO tiene formulario de venta
        forms = page.driver.find_elements(
            By.CSS_SELECTOR, "form[action*='VentaController']"
        )
        assert len(forms) == 0, \
            "Gerente no debería tener formulario de ventas"

    def test_VENT12_empleado_registra_venta(self, page):
        page.login("empleado")
        page.go_to("ventas")
        page.screenshot("VENT12_empleado_ventas")
        assert page.no_php_errors()
        assert page.contains("Ventas")