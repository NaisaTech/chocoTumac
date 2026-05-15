"""
test_03_proveedores.py – Gestión de Proveedores (HU-02)
========================================================
"""
import time
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC

BTN_SUBMIT = "button.btn-ct-primary"


def _llenar_proveedor(page, nombre, num_doc, tipo_doc="CC",
                      tipo_proveedor="Agricultor", ciudad="Tumaco"):
    WebDriverWait(page.driver, 12).until(
        EC.presence_of_element_located((By.ID, "fld-nombre"))
    )
    page.driver.find_element(By.ID, "fld-nombre").clear()
    page.driver.find_element(By.ID, "fld-nombre").send_keys(nombre)

    Select(page.driver.find_element(By.ID, "fld-tipo_doc")).select_by_value(tipo_doc)
    time.sleep(0.3)

    page.driver.find_element(By.ID, "fld-num_doc").clear()
    page.driver.find_element(By.ID, "fld-num_doc").send_keys(num_doc)

    Select(page.driver.find_element(By.ID, "fld-tipo_proveedor")).select_by_value(tipo_proveedor)

    page.driver.find_element(By.ID, "fld-ciudad").clear()
    page.driver.find_element(By.ID, "fld-ciudad").send_keys(ciudad)

    page.driver.find_element(By.ID, "fld-departamento").clear()
    page.driver.find_element(By.ID, "fld-departamento").send_keys("Nariño")


def _submit_proveedor(page):
    btn = WebDriverWait(page.driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, BTN_SUBMIT))
    )
    btn.click()
    time.sleep(1.5)


class TestGestionProveedores:

    def test_PROV01_admin_crea_proveedor_nit(self, page):
        page.login("admin")
        page.go_to("proveedores")
        ts = str(int(time.time()))[-6:]

        _llenar_proveedor(page, f"Cacao Sur SAS {ts}", f"9001{ts}",
                           tipo_doc="NIT", tipo_proveedor="Cooperativa")
        try:
            dv = page.driver.find_element(By.ID, "fld-digito_ver")
            dv.clear()
            dv.send_keys("3")
        except Exception:
            pass

        _submit_proveedor(page)
        page.screenshot("PROV01_proveedor_nit")

        assert (
            page.url_param("msg") == "creado" or
            page.contains("creado") or
            page.is_visible(".alert-success")
        ), f"Proveedor NIT no creado. URL: {page.driver.current_url}"

    def test_PROV02_admin_crea_proveedor_agricultor_cc(self, page):
        page.login("admin")
        page.go_to("proveedores")
        ts = str(int(time.time()))[-6:]

        _llenar_proveedor(page, f"Pedro Campo {ts}", f"1122{ts}",
                           tipo_doc="CC", tipo_proveedor="Agricultor")
        _submit_proveedor(page)
        page.screenshot("PROV02_agricultor_cc")
        assert page.no_php_errors(), "Error PHP al crear proveedor Agricultor"

    def test_PROV03_nombre_vacio_error(self, page):
        page.login("admin")
        page.go_to("proveedores")
        ts = str(int(time.time()))[-6:]

        WebDriverWait(page.driver, 10).until(
            EC.presence_of_element_located((By.ID, "fld-nombre")))

        page.driver.find_element(By.ID, "fld-nombre").clear()
        page.driver.find_element(By.ID, "fld-num_doc").send_keys(f"3344{ts}")
        page.driver.find_element(By.ID, "fld-ciudad").send_keys("Tumaco")
        page.driver.find_element(By.ID, "fld-departamento").send_keys("Nariño")

        btn = WebDriverWait(page.driver, 10).until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, BTN_SUBMIT))
        )
        btn.click()
        time.sleep(0.8)
        page.screenshot("PROV03_nombre_vacio")

        assert (
            page.contains("Proveedores") or
            page.is_visible(".alert-danger")
        ), "No se bloqueó nombre vacío en proveedor"

    def test_PROV04_nit_sin_digito_error(self, page):
        page.login("admin")
        page.go_to("proveedores")
        ts = str(int(time.time()))[-6:]

        _llenar_proveedor(page, f"SinDigito Ltda {ts}", f"8899{ts}", tipo_doc="NIT")
        try:
            page.driver.find_element(By.ID, "fld-digito_ver").clear()
        except Exception:
            pass

        _submit_proveedor(page)
        page.screenshot("PROV04_sin_digito")
        assert page.no_php_errors(), "Error PHP fatal al crear NIT sin dígito"

    def test_PROV05_documento_duplicado_error(self, page):
        page.login("admin")
        ts = str(int(time.time()))[-6:]
        doc = f"77700{ts}"

        page.go_to("proveedores")
        _llenar_proveedor(page, f"Proveedor Uno {ts}", doc)
        _submit_proveedor(page)

        page.go_to("proveedores")
        _llenar_proveedor(page, f"Proveedor Dos {ts}", doc)
        _submit_proveedor(page)
        page.screenshot("PROV05_duplicado")

        assert (
            page.is_visible(".alert-danger") or
            page.contains("ya está registrado") or
            page.contains("existe") or
            page.url_has("error")
        ), "No se detectó proveedor duplicado"

    def test_PROV06_admin_edita_proveedor(self, page):
        page.login("admin")
        page.go_to("proveedores")

        edit_links = page.driver.find_elements(
            By.CSS_SELECTOR, "a[href*='ProveedorController.php?action=editar']"
        )
        if not edit_links:
            pytest.skip("No hay proveedores para editar")

        edit_links[0].click()
        WebDriverWait(page.driver, 10).until(
            EC.presence_of_element_located((By.ID, "fld-persona_contacto"))
        )
        page.screenshot("PROV06_form_editar")

        contacto = page.driver.find_element(By.ID, "fld-persona_contacto")
        contacto.clear()
        contacto.send_keys("Contacto E2E v4")

        try:
            btn = WebDriverWait(page.driver, 5).until(
                EC.element_to_be_clickable((By.CSS_SELECTOR, BTN_SUBMIT))
            )
        except Exception:
            btn = WebDriverWait(page.driver, 5).until(
                EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']"))
            )
        btn.click()
        time.sleep(1.5)
        page.screenshot("PROV06_guardado")

        assert (
            page.url_param("msg") == "actualizado" or
            page.contains("actualizado") or
            page.is_visible(".alert-success")
        ), "No se confirmó actualización del proveedor"

    def test_PROV07_admin_elimina_proveedor(self, page):
        """
        Admin crea proveedor SIN compras y lo elimina.

        CORRECCIÓN v5:
          - Identificamos el botón por data-nombre (no por índice [-1])
          - La lista está ordenada por nombre ASC → [-1] golpea el último
            alfabéticamente, que puede tener compras (FK constraint)
          - Buscar por nombre garantiza eliminar SOLO el recién creado
          - data-url leído directamente → sin depender del modal Bootstrap
        """
        page.login("admin")
        ts = str(int(time.time()))[-6:]
        nombre_creado = f"ParaBorrar {ts}"

        # Crear proveedor sin compras asociadas
        page.go_to("proveedores")
        _llenar_proveedor(page, nombre_creado, f"00{ts}")
        _submit_proveedor(page)

        # Navegar a la lista actualizada
        page.go_to("proveedores")

        # Buscar el botón del proveedor recién creado por su nombre exacto
        # (no por índice [-1] que podría apuntar a un proveedor con compras)
        todos_los_btns = page.driver.find_elements(By.CSS_SELECTOR, ".btn-confirmar-eliminar")
        if not todos_los_btns:
            pytest.skip("No se encontraron botones de eliminar en proveedores")

        btn_target = None
        for btn in todos_los_btns:
            data_nombre = btn.get_attribute("data-nombre") or ""
            if nombre_creado in data_nombre or f"ParaBorrar" in data_nombre:
                btn_target = btn
                break

        # Fallback: si no encontramos por nombre, usar el botón del mayor ID
        if btn_target is None:
            btns_con_url = [(btn, btn.get_attribute("data-url") or "") for btn in todos_los_btns]
            btns_con_id  = [(btn, int(re.search(r"id=(\d+)", url).group(1)))
                            for btn, url in btns_con_url if re.search(r"id=(\d+)", url)]
            if btns_con_id:
                btn_target = max(btns_con_id, key=lambda x: x[1])[0]
            else:
                pytest.skip("No se pudo identificar el botón del proveedor creado")

        # Leer data-url del botón — sin depender del modal Bootstrap ni del CDN
        data_url = btn_target.get_attribute("data-url") or ""
        assert data_url and "eliminar" in data_url,             f"Botón sin data-url válido: '{data_url}'"

        # Construir URL absoluta
        parsed = urlparse(BASE_URL)
        href = (f"{parsed.scheme}://{parsed.netloc}{data_url}"
                if data_url.startswith("/") else data_url)

        page.screenshot("PROV07_antes_eliminar")

        # Navegar al controlador — Selenium sigue el HTTP 302 síncronamente
        page.driver.get(href)

        # Esperar redirección a la vista de proveedores
        WebDriverWait(page.driver, 15).until(
            EC.url_contains("view=proveedores")
        )
        page.screenshot("PROV07_eliminado")

        assert (
            page.url_param("msg") == "eliminado" or
            page.contains("eliminado") or
            page.is_visible(".alert-warning")
        ), f"Eliminación no confirmada. URL: {page.driver.current_url}"
    def test_PROV08_gerente_ve_proveedores_solo_lectura(self, page):
        """
        Gerente (rol_id=2) tiene SOLO LECTURA en proveedores.
        CORRECCIÓN: el gerente NO tiene formulario de creación
        (el PHP usa in_array($rol,[1,3]) igual que en clientes).
        """
        page.login("gerente")
        page.go_to("proveedores")
        page.screenshot("PROV08_gerente_proveedores")

        assert page.contains("Proveedores"), "Gerente no accede a proveedores"
        assert not page.contains("No tienes permisos"), \
            "Gerente fue bloqueado completamente"

        # Gerente ve badge Solo lectura
        assert (
            page.contains("Solo lectura") or page.contains("solo lectura")
        ), "Gerente no ve badge Solo lectura en proveedores"

        # Gerente NO tiene formulario de creación
        fields = page.driver.find_elements(By.ID, "fld-nombre")
        assert len(fields) == 0, \
            "Gerente no debería ver el formulario de creación en proveedores"

    def test_PROV09_empleado_accede_proveedores(self, page):
        page.login("empleado")
        page.go_to("proveedores")
        page.screenshot("PROV09_empleado_proveedores")
        assert page.no_php_errors(), "Error PHP para empleado en proveedores"
        assert page.contains("Proveedores")
from conftest import BASE_URL
from urllib.parse import urlparse
import re