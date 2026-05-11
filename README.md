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
│   └── database.php            # Conexión a base de datos
├── controllers/
│   ├── ProductoController.php
│   ├── CompraController.php
│   ├── VentaController.php
│   ├── ClienteController.php
│   ├── ProveedorController.php
│   └── UsuarioController.php
├── models/
│   ├── Compra.php
│   ├── Venta.php
│   ├── Producto.php            # Incluye gestión de tipos_producto
│   ├── Reporte.php             # Sprint 3: consultas de reportes
│   ├── Cliente.php
│   ├── Proveedor.php
│   └── Usuario.php
├── views/
│   ├── layout/
│   │   └── navbar.php          # Menú con control de acceso por rol
│   ├── compras.php
│   ├── ventas.php
│   ├── inventario.php
│   ├── factura.php             # Factura imprimible con campos DIAN
│   ├── editar_producto.php
│   ├── reportes.php            # Sprint 3: módulo completo de reportes
│   ├── clientes.php
│   ├── proveedores.php
│   ├── dashboard.php
│   └── perfil.php
├── public/
│   ├── css/styles.css
|   └──  js/  
|        ├── app.js
|        ├── compras.js
|        ├── inventario.js
|        └── ventas.js
├── database/
│   └── chocolatetumaco.sql         # Script completo Sprint 1 + 2 + 3
└── index.php                       # Enrutador principal
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

| Sprint   | Módulos cubiertos                     | Casos | Pasaron | Defectos | % Éxito |
|----------|---------------------------------------|-------|---------|----------|---------|
| Sprint 1 | HU-1 Usuarios, HU-2 Proveedores, HU-3 Clientes | 20 | 20 | D-01 a D-05 | 100% |
| Sprint 2 | HU-4 Compras, HU-5 Ventas, HU-6 Inventario     | 25 | 25 | D-06 a D-11 | 100% |
| Sprint 3 | HU-7 Reportes                                   | 17 | 17 | Ninguno     | 100% |
| **Total**| **7 historias de usuario**            | **62**| **62**| **11**   | **100%**|

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
