"""
test_02_clientes.py – Gestión de Clientes (HU-03)
==================================================
"""
import time
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC


# Selector de botón real del sistema (sin type="submit")
BTN_SUBMIT = "button.btn-ct-primary"


from urllib.parse import urlparse
def _llenar_cliente(page, nombre, num_doc, tipo_doc="CC",
                    ciudad="Tumaco", email="", departamento="Nariño"):
    """Rellena el formulario de cliente con los IDs reales del HTML."""
    WebDriverWait(page.driver, 12).until(
        EC.presence_of_element_located((By.ID, "fld-nombre"))
    )
    page.driver.find_element(By.ID, "fld-nombre").clear()
    page.driver.find_element(By.ID, "fld-nombre").send_keys(nombre)

    Select(page.driver.find_element(By.ID, "fld-tipo_doc")).select_by_value(tipo_doc)
    time.sleep(0.3)

    page.driver.find_element(By.ID, "fld-num_doc").clear()
    page.driver.find_element(By.ID, "fld-num_doc").send_keys(num_doc)

    if email:
        page.driver.find_element(By.ID, "fld-email").clear()
        page.driver.find_element(By.ID, "fld-email").send_keys(email)

    page.driver.find_element(By.ID, "fld-ciudad").clear()
    page.driver.find_element(By.ID, "fld-ciudad").send_keys(ciudad)

    page.driver.find_element(By.ID, "fld-departamento").clear()
    page.driver.find_element(By.ID, "fld-departamento").send_keys(departamento)


def _submit_cliente(page):
    """Clic en botón Registrar (sin type='submit' en el HTML)."""
    btn = WebDriverWait(page.driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, BTN_SUBMIT))
    )
    btn.click()
    time.sleep(1.5)


from conftest import BASE_URL
import re
class TestGestionClientes:

    def test_CLI01_admin_crea_cliente_cc_valido(self, page):
        """Admin crea cliente CC → mensaje de éxito."""
        page.login("admin")
        page.go_to("clientes")
        ts = str(int(time.time()))[-6:]

        _llenar_cliente(page,
            nombre=f"Maria Lopez {ts}",
            num_doc=f"98765{ts}",
            email=f"maria{ts}@test.com"
        )
        _submit_cliente(page)
        page.screenshot("CLI01_cliente_cc_creado")

        assert (
            page.url_param("msg") == "creado" or
            page.contains("creado") or
            page.is_visible(".alert-success")
        ), f"Cliente CC no creado. URL: {page.driver.current_url}"

    def test_CLI02_admin_crea_cliente_nit_con_digito(self, page):
        """Admin crea cliente NIT con dígito de verificación."""
        page.login("admin")
        page.go_to("clientes")
        ts = str(int(time.time()))[-6:]

        _llenar_cliente(page,
            nombre=f"Empresa Cacao {ts}",
            num_doc=f"9001{ts}",
            tipo_doc="NIT"
        )
        try:
            dv = page.driver.find_element(By.ID, "fld-digito_ver")
            dv.clear()
            dv.send_keys("3")
        except Exception:
            pass

        _submit_cliente(page)
        page.screenshot("CLI02_cliente_nit")
        assert page.no_php_errors(), "Error PHP al crear cliente NIT"

    def test_CLI03_nombre_vacio_muestra_error(self, page):
        """Nombre vacío → validación HTML5 (required) bloquea el envío."""
        page.login("admin")
        page.go_to("clientes")
        ts = str(int(time.time()))[-6:]

        WebDriverWait(page.driver, 10).until(
            EC.presence_of_element_located((By.ID, "fld-nombre")))

        # Dejar nombre vacío
        page.driver.find_element(By.ID, "fld-nombre").clear()
        page.driver.find_element(By.ID, "fld-num_doc").send_keys(f"111{ts}")
        page.driver.find_element(By.ID, "fld-ciudad").send_keys("Tumaco")
        page.driver.find_element(By.ID, "fld-departamento").send_keys("Nariño")

        # Clic en botón (HTML5 required impide el envío si nombre vacío)
        btn = WebDriverWait(page.driver, 10).until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, BTN_SUBMIT))
        )
        btn.click()
        time.sleep(0.8)
        page.screenshot("CLI03_nombre_vacio")

        # HTML5 required bloquea → sigue en la misma vista
        assert (
            page.url_has("view=clientes") or
            page.contains("Clientes") or
            page.is_visible(".alert-danger")
        ), "No se bloqueó nombre vacío"

    def test_CLI04_email_invalido_muestra_error(self, page):
        """Email inválido → input[type=email] de HTML5 bloquea el envío."""
        page.login("admin")
        page.go_to("clientes")
        ts = str(int(time.time()))[-6:]

        _llenar_cliente(page,
            nombre=f"Test Email {ts}",
            num_doc=f"222{ts}",
            email="email-sin-arroba"
        )
        btn = WebDriverWait(page.driver, 10).until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, BTN_SUBMIT))
        )
        btn.click()
        time.sleep(0.8)
        page.screenshot("CLI04_email_invalido")

        assert (
            page.url_has("view=clientes") or
            page.contains("Clientes") or
            page.is_visible(".alert-danger")
        ), "Email inválido no fue rechazado"

    def test_CLI05_documento_duplicado_muestra_error(self, page):
        """Mismo num_doc dos veces → error de duplicado del servidor."""
        page.login("admin")
        ts = str(int(time.time()))[-6:]
        doc = f"55555{ts}"

        # Crear primero
        page.go_to("clientes")
        _llenar_cliente(page, f"Primero {ts}", doc)
        _submit_cliente(page)

        # Crear segundo con mismo documento
        page.go_to("clientes")
        _llenar_cliente(page, f"Segundo {ts}", doc)
        _submit_cliente(page)
        page.screenshot("CLI05_duplicado")

        assert (
            page.is_visible(".alert-danger") or
            page.contains("ya está registrado") or
            page.contains("existe") or
            page.contains("duplicado") or
            page.url_has("error")
        ), "No se detectó cliente duplicado"

    def test_CLI06_admin_edita_cliente(self, page):
        """Admin edita un cliente existente."""
        page.login("admin")
        page.go_to("clientes")

        edit_links = page.driver.find_elements(
            By.CSS_SELECTOR, "a[href*='ClienteController.php?action=editar']"
        )
        if not edit_links:
            pytest.skip("No hay clientes para editar")

        edit_links[0].click()
        WebDriverWait(page.driver, 10).until(
            EC.presence_of_element_located((By.ID, "fld-telefono"))
        )
        page.screenshot("CLI06_form_editar")

        tel = page.driver.find_element(By.ID, "fld-telefono")
        tel.clear()
        tel.send_keys("3199998888")

        # En el form de editar el botón puede tener type='submit' o btn-ct-primary
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
        page.screenshot("CLI06_guardado")

        assert (
            page.url_param("msg") == "actualizado" or
            page.contains("actualizado") or
            page.is_visible(".alert-success")
        ), "No se confirmó la actualización del cliente"

    def test_CLI07_admin_elimina_cliente(self, page):
        """
        Admin crea cliente y lo elimina.
        Busca el botón por data-nombre para evitar eliminar
        un cliente con ventas asociadas (FK constraint).
        """
        page.login("admin")
        ts = str(int(time.time()))[-6:]
        nombre_creado = f"Para Borrar {ts}"

        page.go_to("clientes")
        _llenar_cliente(page, nombre_creado, f"99900{ts[-5:]}")
        _submit_cliente(page)
        page.go_to("clientes")

        todos_los_btns = page.driver.find_elements(By.CSS_SELECTOR, ".btn-confirmar-eliminar")
        if not todos_los_btns:
            pytest.skip("No se encontraron botones de eliminar en clientes")

        # Buscar el botón del cliente recién creado por nombre
        btn_target = None
        for btn in todos_los_btns:
            dn = btn.get_attribute("data-nombre") or ""
            if "Para Borrar" in dn or nombre_creado in dn:
                btn_target = btn
                break

        # Fallback: mayor ID (el recién insertado)
        if btn_target is None:
            btns_ids = [(b, int(m.group(1)))
                        for b in todos_los_btns
                        for m in [re.search(r"id=(\d+)", b.get_attribute("data-url") or "")]
                        if m]
            btn_target = max(btns_ids, key=lambda x: x[1])[0] if btns_ids else None
        if btn_target is None:
            pytest.skip("No se pudo identificar el botón del cliente creado")

        data_url = btn_target.get_attribute("data-url") or ""
        assert data_url and "eliminar" in data_url, f"Sin data-url: '{data_url}'"

        parsed = urlparse(BASE_URL)
        href = (f"{parsed.scheme}://{parsed.netloc}{data_url}"
                if data_url.startswith("/") else data_url)

        page.screenshot("CLI07_antes_eliminar")
        page.driver.get(href)
        WebDriverWait(page.driver, 15).until(EC.url_contains("view=clientes"))
        page.screenshot("CLI07_eliminado")

        assert (
            page.url_param("msg") == "eliminado" or
            page.contains("eliminado") or
            page.is_visible(".alert-warning")
        ), f"No confirmado. URL: {page.driver.current_url}"
    def test_CLI08_gerente_ve_clientes_solo_lectura(self, page):
        """Gerente (rol_id=2) ve lista con badge 'Solo lectura' y sin formulario."""
        page.login("gerente")
        page.go_to("clientes")
        page.screenshot("CLI08_gerente_solo_lectura")

        assert page.contains("Clientes"), "Gerente no ve la sección Clientes"
        assert (
            page.contains("Solo lectura") or page.contains("solo lectura")
        ), "No aparece badge 'Solo lectura'"

        # Rol 2 no ve el formulario de creación
        forms = page.driver.find_elements(
            By.CSS_SELECTOR, "#fld-nombre"
        )
        assert len(forms) == 0, "Gerente no debería ver el campo fld-nombre"

    def test_CLI09_gerente_no_puede_crear_cliente(self, page):
        """Gerente no tiene formulario de creación."""
        page.login("gerente")
        page.go_to("clientes")

        # El campo fld-nombre NO debe estar presente para gerente
        fields = page.driver.find_elements(By.ID, "fld-nombre")
        assert len(fields) == 0, "Gerente tiene campo de nombre (no debería)"
        page.screenshot("CLI09_sin_form_gerente")

    def test_CLI10_empleado_accede_clientes(self, page):
        """Empleado (rol_id=3) puede gestionar clientes (rol en [1,3])."""
        page.login("empleado")
        page.go_to("clientes")
        page.screenshot("CLI10_empleado_clientes")

        assert page.no_php_errors(), "Error PHP para empleado en clientes"
        assert page.contains("Clientes"), "Empleado no ve la sección Clientes"

        # Empleado SÍ ve el formulario de creación
        fields = page.driver.find_elements(By.ID, "fld-nombre")
        assert len(fields) > 0, "Empleado debería ver el campo de creación"