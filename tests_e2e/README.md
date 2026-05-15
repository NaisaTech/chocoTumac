# Pruebas E2E – Chocolate Tumaco 🍫
## Sistema de Gestión de Inventario de Cacao

Suite de pruebas automatizadas end-to-end con **Selenium WebDriver** para el
sistema ChocoTumac (PHP 7.4+ / MySQL 8.4 / WampServer).

---

## Estructura del proyecto

```
tests_e2e/
├── conftest.py                  # Fixtures globales, PageHelper, credenciales
├── pytest.ini                   # Configuración de pytest
├── requirements.txt             # Dependencias Python
├── README.md                    # Esta documentación
├── tests/
│   ├── test_01_autenticacion.py # Login y control de acceso (AUTH-01..10)
│   ├── test_02_clientes.py      # Gestión de clientes   (CLI-01..10)
│   ├── test_03_proveedores.py   # Gestión de proveedores (PROV-01..09)
│   ├── test_04_inventario.py    # Inventario y productos (INV-01..12)
│   ├── test_05_compras.py       # Órdenes de compra      (COMP-01..10)
│   ├── test_06_ventas.py        # Ventas y facturas       (VENT-01..12)
│   └── test_07_reportes.py      # Generación de reportes  (REP-01..08)
└── reports/                     # Generados automáticamente
    ├── reporte.html
    ├── pytest.log
    └── screenshots/
```

---

## Requisitos previos

### 1. Entorno servidor
| Componente | Versión |
|-----------|---------|
| WampServer | 3.3+ |
| Apache | 2.4 |
| PHP | 7.4+ |
| MySQL | 8.4 |

### 2. Base de datos
```bash
# Restaurar BD desde el archivo SQL del proyecto
mysql -u root chocolatetumaco < database/chocolatetumaco.sql
```

### 3. Usuarios de prueba requeridos
La BD restaurada debe tener estos usuarios (o crearlos desde el panel de admin):

| Rol | Email | Contraseña |
|-----|-------|-----------|
| Administrador | admin@chocotumaco.com | Admin123 |
| Gerente | gerente@chocotumaco.com | Gerente123 |
| Empleado | empleado@chocotumaco.com | Empleado123 |

> Si los emails son diferentes, editar `USERS` en `conftest.py`.

### 4. Requisitos Python
```bash
# Python 3.9+
pip install -r requirements.txt
```

### 5. Google Chrome
- Instalar Google Chrome (versión estable reciente)
- `webdriver-manager` descarga ChromeDriver automáticamente

---

## Ejecución

### Ejecutar todas las pruebas (ventana visible)
```bash
cd tests_e2e
pytest tests/ -v --html=reports/reporte.html --self-contained-html
```

### Ejecutar en modo headless (sin ventana)
```bash
HEADLESS=1 pytest tests/ -v --html=reports/reporte.html --self-contained-html
```

### Ejecutar una suite específica
```bash
# Solo autenticación
pytest tests/test_01_autenticacion.py -v

# Solo compras y ventas
pytest tests/test_05_compras.py tests/test_06_ventas.py -v
```

### Ejecutar un test específico
```bash
pytest tests/test_06_ventas.py::TestGestionVentas::test_VENT08_codigo_fac_formato_correcto -v
```

### Ejecutar por marcador de rol
```bash
# Solo pruebas de administrador
pytest -m admin -v

# Solo pruebas de inventario
pytest -m inventario -v
```

### Ejecución paralela (más rápida)
```bash
pip install pytest-xdist
pytest tests/ -n 2 -v  # 2 workers paralelos
```

---

## Cobertura de pruebas

### Mapeo PHPUnit → E2E

| Suite PHPUnit | Casos PHPUnit | Tests E2E | Archivo E2E |
|--------------|-------------|---------|-----------|
| GestionUsuariosTest | US-01..US-14 | AUTH-01..10 | test_01_autenticacion.py |
| GestionClientesTest | CL-01..CL-16 | CLI-01..10 | test_02_clientes.py |
| GestionProveedoresTest | PR-01..PR-16 | PROV-01..09 | test_03_proveedores.py |
| GestionInventarioTest | IN-01..IN-14 | INV-01..12 | test_04_inventario.py |
| GestionComprasTest | CP-01..CP-12 | COMP-01..10 | test_05_compras.py |
| GestionVentasTest | VT-01..VT-25 | VENT-01..12 | test_06_ventas.py |
| GeneracionReportesTest | RE-01..RE-08 | REP-01..08 | test_07_reportes.py |
| **Total** | **~95 casos** | **71 tests E2E** | **7 archivos** |

### Qué valida cada test E2E

```
✓ Formularios se llenan y envían visualmente en el navegador
✓ Mensajes de error/éxito visibles en pantalla (Bootstrap alerts)
✓ Redirecciones de seguridad funcionan correctamente
✓ Control de acceso por rol (Admin/Gerente/Empleado)
✓ Códigos CMP (compras) y FAC (ventas) con formato correcto
✓ Stock actualiza tras compras y ventas
✓ Movimientos de inventario registrados
✓ Facturas renderizan con datos correctos
✓ No hay errores PHP fatales para ningún rol
✓ Flujos completos de CRUD (Crear/Leer/Actualizar/Eliminar)
```

---

## Configuración avanzada

### Variables de entorno

| Variable | Default | Descripción |
|---------|---------|-------------|
| `BASE_URL` | `http://localhost/chocoTumac` | URL del sistema |
| `HEADLESS` | `0` | `1` = sin ventana visible |

```bash
BASE_URL=http://mi-servidor/chocoTumac HEADLESS=1 pytest tests/ -v
```

### Credenciales personalizadas
Editar el diccionario `USERS` en `conftest.py`:
```python
USERS = {
    "admin": {
        "email": "tu_admin@dominio.com",
        "password": "TuPasswordAdmin1",
        "rol": "Administrador",
        "rol_id": 1,
    },
    # ...
}
```

---

## Interpretación del reporte

### Reporte HTML (`reports/reporte.html`)
- Abre en cualquier navegador
- Muestra ✅ PASSED / ❌ FAILED / ⚠️ SKIPPED por cada test
- Incluye traceback completo en caso de fallo
- Duración de cada test

### Screenshots (`reports/screenshots/`)
- Captura automática en puntos clave de cada test
- Nombre: `TESTID_descripcion_TIMESTAMP.png`
- Útil para diagnóstico visual de fallos

### Causas comunes de SKIP
- `pytest.skip("Formulario no disponible")` → el elemento no se encontró en la vista
  → Verificar que la vista del sistema tiene el formulario esperado
- `pytest.skip("No hay X para Y")` → no hay datos en la BD para el escenario

---

## Arquitectura de las pruebas

### Patrón AAA (Arrange – Act – Assert)
Cada test sigue el mismo patrón que las pruebas PHPUnit originales:
```python
def test_COMP01_admin_registra_compra_valida(self, page):
    # ARRANGE: Admin autenticado, datos válidos
    page.login("admin")
    page.go_to("compras")
    
    # ACT: Llenar y enviar formulario
    self._fill_compra_form(page)
    page._click("button[type='submit']")
    
    # ASSERT: Resultado visible en pantalla
    assert page.page_contains("CMP"), "Compra no fue registrada"
```

### PageHelper (conftest.py)
Abstrae las interacciones con Selenium para que los tests sean legibles:
```python
page.login("admin")           # Login completo
page.go_to("clientes")        # Navegar a vista
page._fill("#campo", "valor") # Escribir en input
page._click("button")         # Clic en elemento
page._select_by_value("select", "NIT")  # Seleccionar opción
page.screenshot("nombre")     # Captura de pantalla
page.page_contains("texto")   # Verificar texto en página
```

---

## Solución de problemas

### ChromeDriver no encontrado
```bash
# Instalar manualmente
pip install webdriver-manager
# O descargar ChromeDriver de https://chromedriver.chromium.org/
```

### Error de conexión al sistema
```
RuntimeError: No se puede conectar a http://localhost/chocoTumac
```
→ Verificar que WampServer está corriendo y la BD está restaurada.

### Tests SKIPPED en masa
→ La BD puede estar vacía (sin proveedores, productos, clientes).
→ Ejecutar primero los tests de creación (CLI-01, PROV-01, INV-03).

### Error de credenciales
→ Actualizar `USERS` en `conftest.py` con los emails/passwords reales de la BD.

---

## Contribuir

Los tests siguen la nomenclatura de las pruebas PHPUnit existentes:
- `test_MODXX_descripcion_camelCase`
- Donde `MOD` = AUTH/CLI/PROV/INV/COMP/VENT/REP
- Y `XX` = número secuencial

Para agregar nuevos tests, agregar al archivo correspondiente siguiendo
el patrón AAA y usando los helpers de `PageHelper`.