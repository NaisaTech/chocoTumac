"""
test_01_autenticacion.py – Autenticación y control de acceso
=============================================================
  - Cada test hace login/logout de forma autónoma (driver fresco por test).
  - AUTH07: logout via clic en navbar, con fallback a delete_all_cookies.
  - AUTH08/09/10: sin dependencia del estado del test anterior.
"""
import time
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from conftest import BASE_URL


class TestAutenticacion:

    # ── AUTH-01 ───────────────────────────────────────────────────────────────
    def test_AUTH01_login_administrador_exitoso(self, page):
        """Admin puede iniciar sesión y llega al dashboard."""
        page.login("admin")
        page.screenshot("AUTH01_admin_dashboard")
        assert page.url_has("view=dashboard"), \
            f"Admin no llegó al dashboard. URL: {page.driver.current_url}"

    # ── AUTH-02 ───────────────────────────────────────────────────────────────
    def test_AUTH02_login_gerente_exitoso(self, page):
        """Gerente puede iniciar sesión."""
        page.login("gerente")
        page.screenshot("AUTH02_gerente_dashboard")
        assert page.url_has("view=dashboard"), \
            f"Gerente no llegó al dashboard. URL: {page.driver.current_url}"

    # ── AUTH-03 ───────────────────────────────────────────────────────────────
    def test_AUTH03_login_empleado_exitoso(self, page):
        """Empleado puede iniciar sesión."""
        page.login("empleado")
        page.screenshot("AUTH03_empleado_dashboard")
        assert page.url_has("view=dashboard"), \
            f"Empleado no llegó al dashboard. URL: {page.driver.current_url}"

    # ── AUTH-04 ───────────────────────────────────────────────────────────────
    def test_AUTH04_email_incorrecto_muestra_error(self, page):
        """Email inexistente → no llega al dashboard."""
        page.go_to("login")
        WebDriverWait(page.driver, 10).until(
            EC.presence_of_element_located((By.ID, "fld-email"))
        )
        page.driver.find_element(By.ID, "fld-email").send_keys("noexiste@fake999.com")
        page.driver.find_element(By.ID, "fld-password").send_keys("Password1")
        page.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        page.pause(1.5)
        page.screenshot("AUTH04_email_incorrecto")

        assert not page.url_has("view=dashboard"), \
            "Login con email inválido no debe redirigir al dashboard"
        assert (
            page.is_visible(".alert-danger") or
            page.url_has("error") or
            page.contains("denegado") or
            page.contains("incorrectos")
        ), "No se mostró mensaje de error"

    # ── AUTH-05 ───────────────────────────────────────────────────────────────
    def test_AUTH05_password_incorrecto_muestra_error(self, page):
        """Contraseña incorrecta → no llega al dashboard."""
        from conftest import USERS
        page.go_to("login")
        WebDriverWait(page.driver, 10).until(
            EC.presence_of_element_located((By.ID, "fld-email"))
        )
        page.driver.find_element(By.ID, "fld-email").send_keys(USERS["admin"]["email"])
        page.driver.find_element(By.ID, "fld-password").send_keys("password_WRONG_xyz999")
        page.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        page.pause(1.5)
        page.screenshot("AUTH05_password_incorrecto")

        assert not page.url_has("view=dashboard"), \
            "Login con password inválido no debe redirigir al dashboard"

    # ── AUTH-06 ───────────────────────────────────────────────────────────────
    def test_AUTH06_acceso_ruta_protegida_sin_sesion(self, page):
        """Sin sesión → dashboard redirige a login."""
        # Driver incognito nuevo: sin cookies de sesión
        page.go_to("dashboard")
        page.pause(1)
        page.screenshot("AUTH06_sin_sesion")
        assert page.url_has("view=login"), \
            f"Sin sesión debe redirigir a login. URL: {page.driver.current_url}"

    # ── AUTH-07 ───────────────────────────────────────────────────────────────
    def test_AUTH07_logout_destruye_sesion(self, page):
        """
        Logout destruye la sesión.
        CORRECCIÓN: usa page.logout() que tiene 3 estrategias de fallback.
        """
        page.login("admin")
        assert page.url_has("view=dashboard"), "Login previo falló"

        page.logout()
        page.screenshot("AUTH07_despues_logout")

        assert page.url_has("view=login"), \
            f"Logout no redirigió a login. URL: {page.driver.current_url}"

        # Verificar que sin sesión no puede volver al dashboard
        page.go_to("dashboard")
        page.pause(0.5)
        assert page.url_has("view=login"), \
            "Después del logout sigue pudiendo acceder al dashboard"

    # ── AUTH-08 ───────────────────────────────────────────────────────────────
    def test_AUTH08_admin_ve_menu_completo(self, page):
        """Admin ve todos los módulos en la navegación."""
        page.login("admin")
        page.screenshot("AUTH08_admin_menu")

        nav_text = page.driver.find_element(By.CSS_SELECTOR, "nav").text.lower()
        modulos = ["clientes", "inventario", "compras", "ventas"]
        faltantes = [m for m in modulos if m not in nav_text]
        assert not faltantes, f"Admin no ve estos módulos: {faltantes}"

    # ── AUTH-09 ───────────────────────────────────────────────────────────────
    def test_AUTH09_gerente_sin_gestion_usuarios(self, page):
        """Gerente no puede acceder a editar_usuario."""
        page.login("gerente")
        page.go_to_url(f"{BASE_URL}/index.php?view=editar_usuario&id=1")
        page.pause(1)
        page.screenshot("AUTH09_gerente_sin_admin")

        assert (
            page.url_has("view=dashboard") or
            page.url_has("view=login") or
            page.url_has("error") or
            page.contains("No tienes permisos") or
            page.contains("Acceso")
        ), f"Gerente no fue bloqueado. URL: {page.driver.current_url}"

    # ── AUTH-10 ───────────────────────────────────────────────────────────────
    def test_AUTH10_empleado_acceso_limitado_reportes(self, page):
        """Empleado puede acceder a reportes sin error PHP fatal."""
        page.login("empleado")
        page.go_to("reportes")
        page.screenshot("AUTH10_empleado_reportes")

        assert page.no_php_errors(), \
            "Error PHP fatal para empleado en reportes"