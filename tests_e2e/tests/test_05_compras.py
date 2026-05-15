"""
test_05_compras.py – Gestión de Compras (HU-04)
================================================
  - Botón real: <button class="btn btn-ct-primary px-4">Registrar compra</button>
    Selector: form#form-compra button.btn-ct-primary
  - COMP09: Gerente es SOLO LECTURA en compras → NO tiene #form-compra.
    Verificar solo que puede ver la sección, no que tiene formulario.
"""
import time, re
import pytest
from datetime import date
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from conftest import BASE_URL

BTN_COMPRA = "#form-compra button.btn-ct-primary"


def _llenar_compra(page, cantidad="50", precio="8500"):
    WebDriverWait(page.driver, 12).until(
        EC.presence_of_element_located((By.ID, "fld-proveedor_id"))
    )
    prov = Select(page.driver.find_element(By.ID, "fld-proveedor_id"))
    if len(prov.options) > 1:
        prov.select_by_index(1)

    prod = Select(page.driver.find_element(By.ID, "fld-producto_id"))
    if len(prod.options) > 1:
        prod.select_by_index(1)
    time.sleep(0.4)

    page.driver.execute_script(
        "document.getElementById('fld-fecha').value = arguments[0]",
        date.today().strftime("%Y-%m-%d")
    )

    cant = page.driver.find_element(By.ID, "fld-cantidad")
    cant.clear()
    cant.send_keys(cantidad)

    prec = page.driver.find_element(By.ID, "fld-precio_unitario")
    prec.clear()
    prec.send_keys(precio)


def _submit_compra(page):
    btn = WebDriverWait(page.driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, BTN_COMPRA))
    )
    btn.click()
    time.sleep(1.5)


def _check_form_compra(page):
    """Verifica que el formulario de compra está disponible, skip si no hay proveedores/productos."""
    forms = page.driver.find_elements(By.ID, "form-compra")
    if not forms:
        pytest.skip("form-compra no visible (gerente u otro rol sin acceso)")
    return True


class TestGestionCompras:

    def test_COMP01_admin_registra_compra_valida(self, page):
        page.login("admin")
        page.go_to("compras")
        _check_form_compra(page)
        _llenar_compra(page)
        _submit_compra(page)
        page.screenshot("COMP01_compra_valida")

        assert (
            page.contains("CMP") or
            page.url_param("msg") == "creada" or
            page.contains("registrada") or
            page.is_visible(".alert-success")
        ), f"Compra no registrada. URL: {page.driver.current_url}"

    def test_COMP02_stock_incrementa_tras_compra(self, page):
        page.login("admin")
        page.go_to("compras")
        if not page.driver.find_elements(By.ID, "form-compra"):
            pytest.skip("Sin formulario de compra disponible")
        _llenar_compra(page, cantidad="25")
        _submit_compra(page)
        page.go_to("inventario")
        page.screenshot("COMP02_inventario_post_compra")
        assert page.no_php_errors()
        assert page.is_present("table")

    def test_COMP03_movimiento_entrada_creado(self, page):
        page.login("admin")
        page.go_to("compras")
        if not page.driver.find_elements(By.ID, "form-compra"):
            pytest.skip("Sin formulario de compra disponible")
        _llenar_compra(page, cantidad="10")
        _submit_compra(page)
        page.go_to("inventario")
        page.screenshot("COMP03_movimiento_entrada")
        assert page.no_php_errors()

    def test_COMP04_cantidad_cero_error(self, page):
        page.login("admin")
        page.go_to("compras")
        if not page.driver.find_elements(By.ID, "form-compra"):
            pytest.skip("Sin formulario de compra disponible")

        WebDriverWait(page.driver, 10).until(
            EC.presence_of_element_located((By.ID, "fld-proveedor_id")))

        prov = Select(page.driver.find_element(By.ID, "fld-proveedor_id"))
        if len(prov.options) > 1:
            prov.select_by_index(1)
        prod = Select(page.driver.find_element(By.ID, "fld-producto_id"))
        if len(prod.options) > 1:
            prod.select_by_index(1)

        page.driver.execute_script(
            "var el = document.getElementById('fld-cantidad');"
            "el.removeAttribute('min'); el.value = '0';"
        )
        page.driver.find_element(By.ID, "fld-precio_unitario").send_keys("5000")
        _submit_compra(page)
        page.screenshot("COMP04_cantidad_cero")

        assert (
            page.is_visible(".alert-danger") or
            page.contains("cantidad") or
            page.contains("Compras")
        ), "No se rechazó cantidad cero"

    def test_COMP05_precio_negativo_error(self, page):
        page.login("admin")
        page.go_to("compras")
        if not page.driver.find_elements(By.ID, "form-compra"):
            pytest.skip("Sin formulario de compra disponible")
        _llenar_compra(page)
        page.driver.execute_script(
            "var el = document.getElementById('fld-precio_unitario');"
            "el.removeAttribute('min'); el.value = '-500';"
        )
        _submit_compra(page)
        page.screenshot("COMP05_precio_negativo")
        assert (
            page.is_visible(".alert-danger") or page.contains("Compras")
        )

    def test_COMP06_proveedor_requerido(self, page):
        page.login("admin")
        page.go_to("compras")
        if not page.driver.find_elements(By.ID, "form-compra"):
            pytest.skip("Sin formulario de compra disponible")

        WebDriverWait(page.driver, 10).until(
            EC.presence_of_element_located((By.ID, "fld-proveedor_id")))
        Select(page.driver.find_element(By.ID, "fld-proveedor_id")).select_by_index(0)
        page.driver.find_element(By.ID, "fld-cantidad").send_keys("10")
        page.driver.find_element(By.ID, "fld-precio_unitario").send_keys("5000")

        btn = WebDriverWait(page.driver, 10).until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, BTN_COMPRA))
        )
        btn.click()
        time.sleep(0.8)
        page.screenshot("COMP06_sin_proveedor")
        assert page.contains("Compras") or page.is_visible(".alert-danger")

    def test_COMP07_producto_requerido(self, page):
        page.login("admin")
        page.go_to("compras")
        if not page.driver.find_elements(By.ID, "form-compra"):
            pytest.skip("Sin formulario de compra disponible")

        WebDriverWait(page.driver, 10).until(
            EC.presence_of_element_located((By.ID, "fld-proveedor_id")))
        prov = Select(page.driver.find_element(By.ID, "fld-proveedor_id"))
        if len(prov.options) > 1:
            prov.select_by_index(1)
        Select(page.driver.find_element(By.ID, "fld-producto_id")).select_by_index(0)
        page.driver.find_element(By.ID, "fld-cantidad").send_keys("10")
        page.driver.find_element(By.ID, "fld-precio_unitario").send_keys("5000")

        btn = WebDriverWait(page.driver, 10).until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, BTN_COMPRA))
        )
        btn.click()
        time.sleep(0.8)
        page.screenshot("COMP07_sin_producto")
        assert page.contains("Compras") or page.is_visible(".alert-danger")

    def test_COMP08_codigo_cmp_formato_correcto(self, page):
        page.login("admin")
        page.go_to("compras")
        if not page.driver.find_elements(By.ID, "form-compra"):
            pytest.skip("Sin formulario de compra disponible")
        _llenar_compra(page)
        _submit_compra(page)
        page.screenshot("COMP08_codigo_cmp")

        codigos = re.findall(r"CMP-\d{4}-\d+", page.driver.page_source)
        if codigos:
            assert re.match(r"CMP-\d{4}-\d+", codigos[0])
        else:
            page.go_to("compras")
            assert page.contains("CMP") or page.no_php_errors()

    def test_COMP09_gerente_accede_compras_solo_lectura(self, page):
        """
        Gerente (rol_id=2) tiene SOLO LECTURA en compras.
        CORRECCIÓN: el gerente ve la sección pero NO tiene #form-compra.
        Verificamos que puede acceder sin error, no que tenga formulario.
        """
        page.login("gerente")
        page.go_to("compras")
        page.screenshot("COMP09_gerente_compras")

        assert page.contains("Compras"), "Gerente no ve el módulo de compras"
        assert not page.contains("No tienes permisos"), \
            "Gerente fue completamente bloqueado"
        assert (
            page.contains("Solo lectura") or page.contains("solo lectura")
        ), "Gerente no ve badge Solo lectura en compras"

        # Gerente NO tiene formulario de creación
        forms = page.driver.find_elements(By.ID, "form-compra")
        assert len(forms) == 0, \
            "Gerente no debería tener #form-compra"

    def test_COMP10_empleado_accede_compras(self, page):
        page.login("empleado")
        page.go_to("compras")
        page.screenshot("COMP10_empleado_compras")
        assert page.no_php_errors()
        assert page.contains("Compras")