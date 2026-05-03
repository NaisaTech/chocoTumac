# ChocoTumac

## Versión 1.0.0 – Sprint 1

---

## Descripción
ChocoTumac es un sistema web de gestión desarrollado bajo arquitectura MVC, que permite administrar usuarios, clientes y proveedores de manera segura y eficiente.

El sistema implementa operaciones CRUD (Crear, Leer, Actualizar, Eliminar), control de acceso por roles y mecanismos de seguridad que garantizan la integridad de la información y la correcta gestión de los datos.

---

## Funcionalidades principales

- Autenticación de usuarios con contraseñas encriptadas (bcrypt)
- Gestión de usuarios y roles (Administrador, Empleado, Gerente)
- CRUD de clientes
- CRUD de proveedores
- Validaciones en frontend y backend
- Protección contra CSRF
- Control de acceso por roles
- Manejo seguro de sesiones

---

## Tecnologías utilizadas

- PHP 7.4+
- MySQL 5.7+
- Apache (WampServer)
- Bootstrap 5
- JavaScript
- PDO para acceso seguro a base de datos

---

## Arquitectura del sistema

El proyecto sigue el patrón MVC (Modelo - Vista - Controlador):

- Models: lógica de negocio y acceso a base de datos  
- Controllers: manejo de peticiones HTTP, validaciones y seguridad  
- Views: interfaz de usuario (HTML + Bootstrap)  
- Config: configuración general y conexión a la base de datos  

---

## Entorno de ejecución

| Componente         | Detalle                        |
|------------------|-------------------------------|
| Servidor          | WampServer (Apache 2.4)       |
| Lenguaje          | PHP 7.4+                      |
| Base de datos     | MySQL 5.7+                    |
| Navegador         | Chrome / Firefox              |
| Sistema operativo | Windows 10 / 11               |
| URL base          | http://localhost/choco_tumac/ |

---

## Instalación y uso

1. Clonar el repositorio:
   bash
   git clone [URL_DEL_REPOSITORIO]

2. Mover el proyecto a la carpeta `www` de WampServer.

3. Crear la base de datos en MySQL.

4. Configurar la conexión en:

   
   config/database.php
   

5. Importar el script SQL del proyecto.

6. Iniciar Apache y MySQL desde WampServer.

7. Acceder desde el navegador:

   ```
   http://localhost/choco_tumac/
   ```

---

## Seguridad implementada

* Encriptación de contraseñas con password_hash() (bcrypt)
* Protección CSRF en todos los formularios
* Validación de sesiones activas
* Control de acceso por roles
* Uso de consultas preparadas con PDO
* Bloqueo de acceso directo a vistas
* Control de caché en rutas protegidas



## Pruebas

Durante el Sprint 1 se realizaron pruebas funcionales manuales utilizando el patrón AAA (Arrange – Act – Assert).

Resultados:

* Total de casos de prueba: 20
* Casos exitosos: 20
* Porcentaje de éxito: 100%

Las pruebas cubren:

* Autenticación de usuarios
* Gestión de usuarios y roles
* Gestión de clientes
* Gestión de proveedores
* Seguridad (CSRF, sesiones, control de acceso)

---

## Estado del proyecto

| Fase     | Estado        |
| -------- | ------------- |
| Sprint 1 | Finalizado    |
| Sprint 2 | En desarrollo |

---

## Autores

* Naisa Tech 
* Nathalia Mejia Buitrago
* Isaura Alexandra Banguera Ruiz

---

## Licencia




