# ChocoTumac

## Versión 1.1.0 – Sprint 2

---

## Descripción

ChocoTumac es un sistema web de gestión desarrollado bajo arquitectura MVC, diseñado para la empresa Chocolate Tumaco. Permite administrar el ciclo operativo completo: usuarios, clientes, proveedores, compras de cacao, ventas de productos y control de inventario en tiempo real.

El sistema implementa operaciones CRUD, control de acceso por roles, mecanismos de seguridad, generación de facturas electrónicas con campos DIAN y un sistema de tipos de producto dinámico y extensible.

---

## Funcionalidades por Sprint

### Sprint 1 – Base del sistema
- Autenticación de usuarios con contraseñas encriptadas (bcrypt)
- Gestión de usuarios y roles (Administrador, Empleado, Gerente)
- CRUD de clientes con soporte de documentos CC, NIT, CE
- CRUD de proveedores
- Validaciones en frontend y backend
- Protección CSRF en todos los formularios
- Control de acceso por roles
- Manejo seguro de sesiones con bloqueo de acceso directo a vistas

### Sprint 2 – Operaciones y facturación
- Registro de compras a proveedores con código único `CMP-YYYY-NNNN`
- Registro de ventas con código único de factura `FAC-YYYY-NNNN`
- Control automático de inventario: el stock se actualiza en cada compra y venta
- Historial de movimientos de inventario con trazabilidad completa
- Factura electrónica imprimible con campos mínimos DIAN (Res. 42/2020)
  - Desglose de IVA (0%, 5%, 19%), total en letras, CUFE, forma de pago
- Sistema de tipos de producto dinámico — el admin crea tipos desde la interfaz
  - Unidad de medida heredada automáticamente del tipo (kg, g, und, lb)
- Validación de cantidades enteras para productos vendidos por unidad (und)
- Ajuste manual de stock con registro en historial
- Edición de precio de productos en cualquier momento
- Soporte de cliente ocasional y cliente registrado en ventas

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
├── config/          # Conexión a base de datos
├── controllers/     # Lógica HTTP, CSRF y control de acceso
├── models/          # Lógica de negocio y acceso a BD
│   ├── Compra.php
│   ├── Venta.php
│   ├── Producto.php  ← incluye gestión de tipos_producto
│   ├── Cliente.php
│   ├── Proveedor.php
│   └── Usuario.php
├── views/           # Interfaz de usuario (HTML + Bootstrap)
│   ├── layout/      # Navbar compartido
│   ├── compras.php
│   ├── ventas.php
│   ├── inventario.php
│   ├── factura.php  ← factura imprimible con campos DIAN
│   └── editar_producto.php
├── public/
│   ├── css/styles.css
│   └── js/  
|       ├── app.js
|       ├── compras.js
|       ├── inventario.js
|       └── ventas.js   
|   
├── database/
│   ├── chocolatetumaco.sql        ← script completo Sprint 1 + Sprint 2
│   └── migracion_sprint2_fix.sql  ← migración para BD existentes
└── index.php        # Enrutador principal
```

---

## Entorno de ejecución

| Componente         | Detalle                           |
|--------------------|-----------------------------------|
| Servidor           | WampServer 64 (Apache 2.4)        |
| Lenguaje           | PHP 7.4+                          |
| Base de datos      | MySQL 8.4                         |
| Navegador          | Chrome / Firefox (última versión) |
| Sistema operativo  | Windows 10 / 11                   |
| URL base           | http://localhost/chocoTumac/      |

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

5. Configurar la conexión en:
   ```
   config/database.php
   ```

6. Iniciar Apache y MySQL desde WampServer.

7. Acceder desde el navegador:
   ```
   http://localhost/chocoTumac/
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
| `roles`                  | Administrador, Empleado, Gerente                         |
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
- Control de acceso por roles (admin, empleado, gerente)
- Consultas preparadas con PDO — sin SQL injection
- Bloqueo de acceso directo a vistas mediante constante `CHOCOTUMAC_APP`
- Headers `Cache-Control`, `Pragma` y `Expires` en todas las rutas protegidas

---

## Pruebas

### Sprint 1
Pruebas funcionales manuales con patrón AAA.

| Módulo                  | Casos | Pasaron | % Éxito |
|-------------------------|-------|---------|---------|
| HU-01: Usuarios y Roles | 8     | 8       | 100%    |
| HU-02: Proveedores      | 6     | 6       | 100%    |
| HU-03: Clientes         | 6     | 6       | 100%    |
| **Total**               | **20**| **20**  | **100%**|

### Sprint 2
Pruebas funcionales manuales con patrón AAA. 6 defectos identificados y corregidos (D-06 a D-11).

| Módulo                                    | Casos | Pasaron | % Éxito |
|-------------------------------------------|-------|---------|---------|
| HU-4: Registro de Compras de Cacao        | 8     | 8       | 100%    |
| HU-5: Registro de Ventas de Cacao         | 9     | 9       | 100%    |
| HU-6: Actualización Automática Inventario | 8     | 8       | 100%    |
| **Total**                                 | **25**| **25**  | **100%**|

---

## Estado del proyecto

| Fase     | HU cubiertas      | Estado      |
|----------|-------------------|-------------|
| Sprint 1 | HU-1, HU-2, HU-3  | Finalizado  |
| Sprint 2 | HU-4, HU-5, HU-6  | Finalizado  |
| Sprint 3 | HU-7 (Reportes)   | Planificado |

---

## Autores

- Naisa Tech
- Nathalia Mejia Buitrago
- Isaura Alexandra Banguera Ruiz

---

## Licencia

Proyecto académico – Naisa Tech © 2026
