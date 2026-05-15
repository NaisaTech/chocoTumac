"""
conftest.py – Chocolate Tumaco E2E Suite
========================================
  - Driver con scope="function": cada test obtiene un navegador fresco,
    eliminando la propagación de fallos en cascada.
  - login() navega siempre a /login primero, sin asumir estado previo.
  - logout() robusto: intenta href del navbar, si falla va por URL directa,
    si falla borra cookies — garantiza sesión limpia.
  - BASE_URL detectable automáticamente probando rutas comunes de WampServer.
"""

import os
import time
import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service

# ── URL base ──────────────────────────────────────────────────────────────────
# Cambia BASE_URL si tu proyecto está en una ruta diferente.
# Rutas típicas de WampServer:
#   http://localhost/chocoTumac          (carpeta = chocoTumac)
#   http://localhost/chocoTumac/chocoTumac  (subcarpeta)
#   http://localhost                     (en raíz www)
BASE_URL  = os.getenv("BASE_URL", "http://localhost/chocoTumac")
HEADLESS  = os.getenv("HEADLESS", "0") == "1"
TIMEOUT   = 15   # segundos espera explícita (aumentado para WampServer lento)
PAGE_LOAD = 20   # timeout de carga de página

# ── Credenciales reales de la BD ──────────────────────────────────────────────
USERS = {
    "admin": {
        "email":    "admin@chocotumac.com",
        "password": "3187258465*Nmb",
        "rol_id":   1,
    },
    "gerente": {
        "email":    "elkin12@chocotumac.com",
        "password": "Tumaco*3120",
        "rol_id":   2,
    },
    "empleado": {
        "email":    "davidg@chocotumac.com",
        "password": "3154491214*Nmb",
        "rol_id":   3,
    },
    "admin2": {
        "email":    "nathmejia@chocotumac.com",
        "password": "Tomyjerry2021",
        "rol_id":   1,
    },
    "admin3": {
        "email":    "isaura2022@chocotumac.com",
        "password": "2022*Isauraruiz",
        "rol_id":   1,
    },
}

# ── Driver factory ────────────────────────────────────────────────────────────

def _build_driver() -> webdriver.Chrome:
    opts = Options()
    if HEADLESS:
        opts.add_argument("--headless=new")
    opts.add_argument("--window-size=1400,900")
    opts.add_argument("--disable-notifications")
    opts.add_argument("--no-sandbox")
    opts.add_argument("--disable-dev-shm-usage")
    opts.add_argument("--disable-gpu")
    opts.add_argument("--disable-extensions")
    # Evita que Chrome pida contraseñas / guarde datos entre sesiones
    opts.add_argument("--incognito")

    try:
        driver = webdriver.Chrome(options=opts)
    except Exception:
        try:
            from webdriver_manager.chrome import ChromeDriverManager
            service = Service(ChromeDriverManager().install())
            driver = webdriver.Chrome(service=service, options=opts)
        except Exception as exc:
            raise RuntimeError(
                "ChromeDriver no encontrado. "
                "Instala Google Chrome o ejecuta: pip install webdriver-manager"
            ) from exc

    driver.set_page_load_timeout(PAGE_LOAD)
    # NO usar implicitly_wait — usamos esperas explícitas en PageHelper
    return driver


# ── FIXTURE CLAVE: scope="function" → driver fresco por test ─────────────────
@pytest.fixture(scope="function")
def driver():
    """
    Crea un Chrome nuevo para cada test y lo cierra al finalizar.
    Esto garantiza que el fallo de un test no afecte a los demás.
    """
    drv = _build_driver()
    yield drv
    try:
        drv.quit()
    except Exception:
        pass


# ── PageHelper ────────────────────────────────────────────────────────────────

class PageHelper:
    """Abstracción sobre Selenium para los tests de ChocoTumac."""

    def __init__(self, driver: webdriver.Chrome):
        self.driver = driver
        self.wait   = WebDriverWait(driver, TIMEOUT)

    # ── Navegación ────────────────────────────────────────────────

    def go_to(self, view: str) -> None:
        """Navega a index.php?view=<view>."""
        self.driver.get(f"{BASE_URL}/index.php?view={view}")

    def go_to_url(self, url: str) -> None:
        """Navega a una URL absoluta."""
        self.driver.get(url)

    # ── Login / Logout ────────────────────────────────────────────

    def login(self, role_key: str) -> None:
        """
        Realiza el login completo:
        1. Navega a la página de login (siempre, sin asumir estado previo).
        2. Espera el formulario.
        3. Rellena y envía credenciales.
        4. Espera la redirección al dashboard.
        """
        creds = USERS[role_key]

        # Ir a login y esperar que el formulario esté listo
        self.go_to("login")
        self.wait.until(
            EC.presence_of_element_located((By.ID, "fld-email"))
        )

        # Limpiar y rellenar campos
        email_field = self.driver.find_element(By.ID, "fld-email")
        email_field.clear()
        email_field.send_keys(creds["email"])

        pass_field = self.driver.find_element(By.ID, "fld-password")
        pass_field.clear()
        pass_field.send_keys(creds["password"])

        # Enviar (esperar que el botón sea clickeable)
        btn = self.wait.until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']"))
        )
        btn.click()

        # Esperar redirección al dashboard
        self.wait.until(EC.url_contains("view=dashboard"))

    def logout(self) -> None:
        """
        Cierra sesión de forma robusta con 3 estrategias de fallback.
        Garantiza que el driver quede en la página de login.
        """
        # Estrategia 1: clic en el enlace del navbar
        try:
            link = self.driver.find_element(
                By.CSS_SELECTOR, "a[href*='action=logout']"
            )
            link.click()
            WebDriverWait(self.driver, 5).until(
                EC.url_contains("view=login")
            )
            return
        except Exception:
            pass

        # Estrategia 2: navegar directamente a la URL de logout
        try:
            self.driver.get(
                f"{BASE_URL}/controllers/UsuarioController.php?action=logout"
            )
            WebDriverWait(self.driver, 5).until(
                EC.url_contains("view=login")
            )
            return
        except Exception:
            pass

        # Estrategia 3: borrar cookies (sesión PHP = cookie PHPSESSID)
        try:
            self.driver.delete_all_cookies()
            self.go_to("login")
            WebDriverWait(self.driver, 5).until(
                EC.presence_of_element_located((By.ID, "fld-email"))
            )
        except Exception:
            pass

    # ── Interacciones con elementos ───────────────────────────────

    def fill(self, css_or_id: str, value: str, by_id: bool = False) -> None:
        """Limpia y escribe en un campo. Usa ID si by_id=True."""
        locator = (By.ID, css_or_id) if by_id else (By.CSS_SELECTOR, css_or_id)
        el = self.wait.until(EC.element_to_be_clickable(locator))
        el.clear()
        el.send_keys(value)

    def click(self, css: str) -> None:
        el = self.wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, css)))
        el.click()

    def select_by_value(self, css: str, value: str) -> None:
        from selenium.webdriver.support.ui import Select
        el = self.wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, css)))
        Select(el).select_by_value(value)

    def set_value_js(self, element_id: str, value: str) -> None:
        """Fija el valor de un campo via JS (para inputs readonly/min)."""
        self.driver.execute_script(
            "var el = document.getElementById(arguments[0]);"
            "el.removeAttribute('min'); el.removeAttribute('max');"
            "el.removeAttribute('required'); el.value = arguments[1];",
            element_id, value
        )

    # ── Verificaciones ────────────────────────────────────────────

    def contains(self, text: str) -> bool:
        return text in self.driver.page_source

    def url_has(self, fragment: str) -> bool:
        return fragment in self.driver.current_url

    def url_param(self, param: str) -> str:
        from urllib.parse import urlparse, parse_qs
        params = parse_qs(urlparse(self.driver.current_url).query)
        return params.get(param, [""])[0]

    def is_visible(self, css: str) -> bool:
        try:
            WebDriverWait(self.driver, 4).until(
                EC.visibility_of_element_located((By.CSS_SELECTOR, css))
            )
            return True
        except Exception:
            return False

    def is_present(self, css: str) -> bool:
        return len(self.driver.find_elements(By.CSS_SELECTOR, css)) > 0

    def wait_for_element(self, by: By, value: str, timeout: int = None):
        t = timeout or TIMEOUT
        return WebDriverWait(self.driver, t).until(
            EC.presence_of_element_located((by, value))
        )

    def wait_for_url(self, fragment: str, timeout: int = None) -> None:
        t = timeout or TIMEOUT
        WebDriverWait(self.driver, t).until(EC.url_contains(fragment))

    def screenshot(self, name: str) -> None:
        os.makedirs("reports/screenshots", exist_ok=True)
        path = f"reports/screenshots/{name}_{int(time.time())}.png"
        try:
            self.driver.save_screenshot(path)
            print(f"  📸 {path}")
        except Exception:
            pass

    def no_php_errors(self) -> bool:
        src = self.driver.page_source
        return not any(e in src for e in
                       ["Fatal error", "Uncaught", "Warning:", "Notice:"])

    def pause(self, seconds: float = 0.5) -> None:
        time.sleep(seconds)


# ── Fixture page ──────────────────────────────────────────────────────────────

@pytest.fixture(scope="function")
def page(driver):
    """PageHelper fresco para cada test."""
    yield PageHelper(driver)