# ChocoTumac

## Versión 1.2.0 – Sprint 3 (Release final)

---

## Descripción

ChocoTumac es un sistema web de gestión desarrollado bajo arquitectura MVC, diseñado para la empresa Chocolate Tumaco. Permite administrar el ciclo operativo completo: usuarios, clientes, proveedores, compras de cacao, ventas de productos, control de inventario en tiempo real y reportes para la toma de decisiones gerenciales.

El sistema implementa operaciones CRUD, control de acceso por roles, mecanismos de seguridad, generación de facturas electrónicas con campos DIAN, sistema de tipos de producto dinámico y un módulo de reportes exclusivo para el Gerente.

---

## Credenciales iniciales

| Campo      | Valor                    |
|------------|--------------------------|
| Correo     | admin@chocotumac.com     |
| Contraseña | Admin123*                |
| Rol        | Administrador            |

> **Importante:** Cambiar la contraseña tras el primer inicio de sesión desde el módulo "Mi Perfil".

---

## Funcionalidades por Sprint

### Sprint 1 – Base del sistema `v1.0.0`
- Autenticación de usuarios con contraseñas encriptadas (bcrypt)
- Gestión de usuarios y roles (Administrador, Empleado, Gerente)
- CRUD de clientes con soporte de documentos CC, NIT, CE
- CRUD de proveedores
- Validaciones en frontend y backend
- Protección CSRF en todos los formularios
- Control de acceso por roles
- Manejo seguro de sesiones con bloqueo de acceso directo a vistas

### Sprint 2 – Operaciones y facturación `v1.1.0`
- Registro de compras a proveedores con código único `CMP-YYYY-NNNN`
- Registro de ventas con código único de factura `FAC-YYYY-NNNN`
- Control automático de inventario: stock actualizado en cada compra y venta
- Historial de movimientos con trazabilidad completa
- Factura electrónica imprimible con campos mínimos DIAN (Res. 42/2020)
  - Desglose de IVA (0%, 5%, 19%), total en letras, CUFE, forma de pago
- Sistema de tipos de producto dinámico — admin crea tipos desde la interfaz
  - Unidad de medida heredada automáticamente del tipo (kg, g, und, lb)
- Validación de cantidades enteras para productos vendidos por unidad
- Ajuste manual de stock con registro en historial
- Edición de precio de productos en cualquier momento
- Soporte de cliente ocasional y cliente registrado en ventas

### Sprint 3 – Reportes `v1.2.0` *(Release final)*
- Módulo de reportes exclusivo para el rol **Gerente**
- Panel de KPIs con 8 métricas generales en tiempo real
- Reporte de ventas por cliente con totales, subtotales e IVA
- Reporte de compras por proveedor con totales
- Reporte de inventario actualizado con badges de estado (Stock OK / Stock bajo / Sin stock)
- Top 10 productos más vendidos con gráfico de barras visual
- Filtros por rango de fechas en todos los reportes
- Filtro por cliente específico (ventas) y por proveedor específico (compras)
- Búsqueda rápida por palabra clave en todos los módulos
- Botón de impresión limpia — oculta navbar, filtros y tabs al imprimir

---

## Roles del sistema

| Rol             | `rol_id` | Acceso                                                              |
|-----------------|----------|---------------------------------------------------------------------|
| Administrador   | 1        | Todo el sistema excepto Reportes                                    |
| Gerente         | 2        | Dashboard, Reportes y consulta de módulos                           |
| Empleado        | 3        | Registro de compras, ventas y consulta de inventario                |

---

## Tecnologías utilizadas

- PHP 7.4+
- MySQL 8.4
- Apache (WampServer 64)
- Bootstrap 5.3
- JavaScript (vanilla)
- PDO para acceso seguro a base de datos

---

## Arquitectura del sistema

El proyecto sigue el patrón MVC (Modelo – Vista – Controlador):

```
chocoTumac/
├── config/
│   └── database.php               # Conexión a base de datos
├── controllers/
│   ├── Redirectable.php           # Trait centralizado de redirecciones HTTP
│   ├── ProductoController.php
│   ├── CompraController.php
│   ├── VentaController.php
│   ├── ClienteController.php      # ⚠ v1.2.0: manejo de FK en eliminar()
│   ├── ProveedorController.php    # ⚠ v1.2.0: manejo de FK en eliminar()
│   └── UsuarioController.php
├── models/
│   ├── Compra.php
│   ├── Venta.php
│   ├── Producto.php               # Incluye gestión de tipos_producto
│   ├── Reporte.php                # Sprint 3: consultas de reportes
│   ├── Cliente.php
│   ├── Proveedor.php
│   └── Usuario.php
├── views/
│   ├── layout/
│   │   └── navbar.php             # Menú con control de acceso por rol
│   ├── compras.php
│   ├── ventas.php
│   ├── inventario.php
│   ├── factura.php                # Factura imprimible con campos DIAN
│   ├── editar_producto.php
│   ├── reportes.php               # Sprint 3: módulo completo de reportes
│   ├── clientes.php
│   ├── proveedores.php
│   ├── dashboard.php
│   └── perfil.php
├── public/
│   ├── css/styles.css
│   └── js/
│       ├── app.js                 # ⚠ v1.2.0: modal sin dependencia de CDN
│       ├── compras.js
│       ├── inventario.js
│       └── ventas.js
├── tests/                         # ⚠ v1.2.0: suite PHPUnit (156 casos)
│   ├── bootstrap.php              # Stubs: FakePDO, FakePDOStatement, Database
│   ├── TestHelper.php             # Trait: invocarPrivado(), inyectarPropiedad(), fábricas
│   ├── GestionClientesTest.php    # 18 casos – HU-03
│   ├── GestionProveedoresTest.php # 17 casos – HU-02
│   ├── GestionUsuariosTest.php    # 31 casos – HU-01
│   ├── GestionInventarioTest.php  # 24 casos – HU-06
│   ├── GestionComprasTest.php     # 22 casos – HU-04
│   ├── GestionVentasTest.php      # 25 casos – HU-05
│   ├── GeneracionReportesTest.php # 19 casos – HU-07
│   └── reports/
│       ├── junit.xml              # Resultados JUnit para SonarCloud
│       └── coverage.xml           # Cobertura para SonarCloud
├── database/
│   ├── chocolatetumaco.sql        # Script completo Sprint 1 + 2 + 3
│   └── migracion_sprint2_fix.sql  # Migración para BD existentes del Sprint 1
├── sonar-project.properties       # ⚠ v1.2.0: análisis estático SonarCloud
├── phpunit.xml                    # ⚠ v1.2.0: configuración PHPUnit
├── composer.json                  # ⚠ v1.2.0: dependencias de desarrollo PHP
└── index.php                      # Enrutador principal
```

---

## Instalación y uso

1. Clonar el repositorio:
   ```bash
   git clone [URL_DEL_REPOSITORIO]
   ```

2. Mover el proyecto a la carpeta `www` de WampServer:
   ```
   C:\wamp64\www\chocoTumac\
   ```

3. En DBeaver, crear la base de datos `chocolatetumaco`.

4. Importar el script completo:
   ```
   database/chocolatetumaco.sql
   ```

5. Configurar la conexión en `config/database.php`:
   ```php
   private $host = "localhost";
   private $db   = "chocolatetumaco";
   private $user = "root";
   private $pass = "";
   ```

6. Iniciar Apache y MySQL desde WampServer.

7. Acceder desde el navegador:
   ```
   http://localhost/chocoTumac/
   ```

8. Iniciar sesión con las credenciales iniciales:
   ```
   Correo:     admin@chocotumac.com
   Contraseña: Admin123*
   ```

> **Si ya tienes la BD del Sprint 1 instalada**, ejecuta únicamente:
> ```
> database/migracion_sprint2_fix.sql
> ```

---

## Base de datos – tablas principales

| Tabla                    | Descripción                                              |
|--------------------------|----------------------------------------------------------|
| `usuarios`               | Cuentas del sistema con rol asignado                     |
| `roles`                  | Administrador (1), Gerente (2), Empleado (3)             |
| `clientes`               | Clientes registrados con documento, ciudad y contacto    |
| `proveedores`            | Proveedores de cacao con NIT/CC                          |
| `tipos_producto`         | Tipos dinámicos con unidad de inventario y unidad venta  |
| `productos`              | Catálogo con stock actual, stock mínimo y precio         |
| `compras`                | Compras de cacao con código CMP único                    |
| `ventas`                 | Ventas con código FAC, IVA, subtotal y forma de pago     |
| `movimientos_inventario` | Historial completo de entradas, salidas y ajustes        |

---

## Seguridad implementada

- Encriptación de contraseñas con `password_hash()` (bcrypt, 12 rounds)
- Protección CSRF en todos los formularios
- Validación de sesiones activas en cada vista protegida
- Control de acceso por roles en vistas y controladores
- Consultas preparadas con PDO — sin riesgo de SQL injection
- Bloqueo de acceso directo a vistas mediante constante `CHOCOTUMAC_APP`
- Headers `Cache-Control`, `Pragma` y `Expires` en todas las rutas protegidas
- Módulo de reportes protegido: solo `rol_id = 2` puede acceder

---

## Pruebas

### Pruebas unitarias (PHPUnit)

Las pruebas unitarias validan la lógica de negocio de los modelos de forma aislada, sin conexión real a la base de datos. Se usa **FakePDO** (stub definido en `tests/bootstrap.php`) que simula todas las operaciones PDO. El trait **TestHelper** provee acceso por Reflection a métodos privados (pruebas de caja blanca) y fábricas de datos válidos por modelo para la fase Arrange de cada caso.

#### Instalación de dependencias

```bash
composer install
```

#### Ejecución

```bash
# Suite completa (156 casos)
vendor/bin/phpunit

# Suite individual
vendor/bin/phpunit --testsuite Clientes
vendor/bin/phpunit --testsuite Proveedores
vendor/bin/phpunit --testsuite Usuarios
vendor/bin/phpunit --testsuite Inventario
vendor/bin/phpunit --testsuite Compras
vendor/bin/phpunit --testsuite Ventas
vendor/bin/phpunit --testsuite Reportes

# Generar reporte JUnit para SonarCloud
vendor/bin/phpunit --log-junit tests/reports/junit.xml
```

#### Cobertura por historia de usuario

| Suite                   | Archivo                       | Casos | HU cubierta      |
|-------------------------|-------------------------------|------:|------------------|
| GestionUsuariosTest     | GestionUsuariosTest.php       |    31 | HU-01 Usuarios   |
| GestionProveedoresTest  | GestionProveedoresTest.php    |    17 | HU-02 Proveedores|
| GestionClientesTest     | GestionClientesTest.php       |    18 | HU-03 Clientes   |
| GestionComprasTest      | GestionComprasTest.php        |    22 | HU-04 Compras    |
| GestionVentasTest       | GestionVentasTest.php         |    25 | HU-05 Ventas     |
| GestionInventarioTest   | GestionInventarioTest.php     |    24 | HU-06 Inventario |
| GeneracionReportesTest  | GeneracionReportesTest.php    |    19 | HU-07 Reportes   |
| **Total**               |                               | **156** |                |

### Pruebas E2E automatizadas (Selenium WebDriver)

La suite E2E en `tests_e2e/` automatiza flujos completos en el navegador: llenado de formularios, mensajes de éxito/error, control de acceso por rol y ciclos CRUD completos. Requiere Python 3.9+, Google Chrome y WampServer en ejecución.

```bash
cd tests_e2e
pytest tests/ -v --html=reports/reporte.html --self-contained-html
```

Consulta `tests_e2e/README.md` para instrucciones detalladas de instalación, configuración de credenciales y ejecución por módulo.

| Suite PHPUnit          | Tests E2E   | Archivo E2E                |
|------------------------|:-----------:|----------------------------|
| GestionUsuariosTest    | AUTH-01..10 | test_01_autenticacion.py   |
| GestionClientesTest    | CLI-01..10  | test_02_clientes.py        |
| GestionProveedoresTest | PROV-01..09 | test_03_proveedores.py     |
| GestionInventarioTest  | INV-01..12  | test_04_inventario.py      |
| GestionComprasTest     | COMP-01..10 | test_05_compras.py         |
| GestionVentasTest      | VENT-01..12 | test_06_ventas.py          |
| GeneracionReportesTest | REP-01..08  | test_07_reportes.py        |
| **Total**              | **71**      | 7 archivos                 |

### Resumen de cobertura total

| Sprint   | Módulos cubiertos                               | Casos PHPUnit | Tests E2E | % Éxito |
|----------|-------------------------------------------------|:-------------:|:---------:|:-------:|
| Sprint 1 | HU-1 Usuarios, HU-2 Proveedores, HU-3 Clientes |      66       |    29     |  100%   |
| Sprint 2 | HU-4 Compras, HU-5 Ventas, HU-6 Inventario     |      71       |    34     |  100%   |
| Sprint 3 | HU-7 Reportes                                   |      19       |     8     |  100%   |
| **Total**| **7 historias de usuario**                      |    **156**    |   **71**  | **100%**|

---

## Análisis de calidad – SonarCloud

El proyecto está integrado con **SonarCloud** (organización `naisatech`). El análisis cubre exclusivamente el directorio `models/` (lógica de negocio pura), usando los resultados de PHPUnit en `tests/reports/junit.xml` para métricas de cobertura.

```bash
# 1. Generar reporte de cobertura
vendor/bin/phpunit --log-junit tests/reports/junit.xml

# 2. Ejecutar análisis (requiere sonar-scanner instalado)
sonar-scanner
```

La configuración en `sonar-project.properties` suprime dos reglas que generan falsos positivos en los modelos:

| Regla | Motivo de supresión |
|-------|---------------------|
| `php:S1172` – Parámetros no usados | Los modelos implementan firmas de interfaz que exigen el parámetro aunque la implementación concreta no lo use |
| `php:S2259` – Null pointer dereference | PDO con `ERRMODE_EXCEPTION` nunca retorna `null`; lanza excepción ante cualquier error, haciendo que la alerta del análisis estático sea incorrecta |

La duplicación de código en `tests/**` está excluida (`sonar.cpd.exclusions=tests/**`) porque los patrones repetitivos en pruebas son intencionales y forman parte del patrón AAA.

---

## Cambios técnicos v1.2.0

### 1. Modal de eliminación sin dependencia de CDN (`public/js/app.js`)

Los botones de eliminar en todos los módulos dependían de `new bootstrap.Modal(modal).show()`, que requiere que Bootstrap JS cargue desde el CDN externo `cdn.jsdelivr.net`. En entornos WampServer sin internet fluido, el script no llegaba a tiempo y el modal nunca se abría, haciendo que los botones de eliminar parecieran no funcionar.

`app.js v2` reemplaza esa llamada por dos funciones nativas (`mostrarModal` / `ocultarModal`) que manipulan las clases CSS directamente, reproduciendo el comportamiento de Bootstrap 5 sin ninguna dependencia externa. El cierre por botón Cancelar, clic en backdrop y tecla Escape también se gestiona de forma nativa.

### 2. Manejo de restricciones de clave foránea en controladores (`ClienteController`, `ProveedorController`)

Al intentar eliminar un cliente con ventas asociadas, o un proveedor con compras asociadas, MySQL lanzaba un error de integridad referencial (`ON DELETE RESTRICT`). Con `PDO::ERRMODE_EXCEPTION` activo, PHP generaba una excepción no capturada que resultaba en una página en blanco sin mensaje alguno para el usuario.

Ambos controladores ahora capturan la `PDOException` en `eliminar()` y redirigen al usuario con un mensaje descriptivo en lugar de fallar silenciosamente.

### 3. Suite de pruebas unitarias PHPUnit (156 casos, archivos nuevos)

Se incorporó infraestructura completa de pruebas unitarias para todas las historias de usuario:

- **`composer.json`** — declara `phpunit/phpunit ^9.6` como dependencia de desarrollo.
- **`phpunit.xml`** — configura 8 suites ejecutables individualmente; cobertura limitada a `models/`; salida JUnit en `tests/reports/junit.xml`.
- **`tests/bootstrap.php`** — stubs `FakePDO` y `FakePDOStatement` que simulan la capa de base de datos sin conexión real; stub `Database` que retorna `FakePDO`.
- **`tests/TestHelper.php`** — trait con acceso por Reflection a métodos privados (`invocarPrivado`), inyección de dependencias (`inyectarPropiedad`) y fábricas de datos válidos por modelo para reducir repetición en los tests.
- **7 archivos de prueba** cubriendo todas las historias de usuario (HU-01 a HU-07).

### 4. Configuración SonarCloud (`sonar-project.properties`, archivo nuevo)

Se añadió el archivo de configuración para análisis estático continuo integrado con SonarCloud. Las reglas `php:S1172` y `php:S2259` se suprimieron como falsos positivos (ver tabla en sección "Análisis de calidad"). La duplicación de código en `tests/` se excluye del análisis.

---

## Estado del proyecto

| Fase     | HU cubiertas            | Versión | Estado      |
|----------|-------------------------|---------|-------------|
| Sprint 1 | HU-1, HU-2, HU-3        | 1.0.0   | Finalizado  |
| Sprint 2 | HU-4, HU-5, HU-6        | 1.1.0   | Finalizado  |
| Sprint 3 | HU-7                    | 1.2.0   | Finalizado  |

---

## Autores

- Naisa Tech
- Nathalia Mejia Buitrago
- Isaura Alexandra Banguera Ruiz

---

## Licencia

Proyecto académico – Naisa Tech © 2026