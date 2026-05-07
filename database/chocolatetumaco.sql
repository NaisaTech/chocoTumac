-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: localhost    Database: chocolatetumaco
-- ------------------------------------------------------
-- Server version	8.4.7

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_doc` enum('NIT','CC','CE','Pasaporte') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CC',
  `num_doc` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `digito_ver` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departamento` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cliente_doc` (`tipo_doc`,`num_doc`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'Comercializadora El Cacao','NIT','800123456',NULL,'3001234567',NULL,NULL,'Bogota','Cundinamarca','2026-04-30 21:27:30'),(2,'Chocolates del Pacifico','NIT','900654321',NULL,'3117654321',NULL,NULL,'Tumaco','Narino','2026-04-30 21:27:36'),(3,'Distribuidora San Pablo','CC','12345678',NULL,'3205559900',NULL,NULL,'encano','Narino','2026-04-30 21:27:47'),(4,'Fundación colors','NIT','900123456','7','3182422375',NULL,NULL,NULL,NULL,'2026-04-30 22:09:31');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compras`
--

DROP TABLE IF EXISTS `compras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compras` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Código único: CMP-YYYY-NNNN',
  `proveedor_id` int unsigned NOT NULL,
  `producto_id` int unsigned NOT NULL,
  `fecha` date NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `unidad` enum('kg','g') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kg' COMMENT 'kg para cacao_grano | g para chocolate_mesa',
  `precio_unitario` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `usuario_id` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_compra_codigo` (`codigo`),
  KEY `proveedor_id` (`proveedor_id`),
  KEY `producto_id` (`producto_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `compras_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `compras_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `compras_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compras`
--

LOCK TABLES `compras` WRITE;
/*!40000 ALTER TABLE `compras` DISABLE KEYS */;
INSERT INTO `compras` VALUES (1,'CMP-2026-0001',3,1,'2026-05-04',10.00,'kg',10000.00,100000.00,NULL,1,'2026-05-03 23:31:11'),(2,'CMP-2026-0002',2,1,'2026-05-04',5.00,'kg',12000.00,60000.00,NULL,1,'2026-05-03 23:34:09'),(3,'CMP-2026-0003',4,1,'2026-05-04',3.00,'kg',7500.00,22500.00,NULL,1,'2026-05-04 10:08:08'),(4,'CMP-2026-0004',3,1,'2026-05-04',2.00,'kg',3000.00,6000.00,NULL,1,'2026-05-04 10:20:01'),(5,'CMP-2026-0005',1,1,'2026-05-04',5.00,'kg',4000.00,20000.00,NULL,1,'2026-05-04 10:34:02'),(6,'CMP-2026-0006',2,1,'2026-05-04',0.91,'kg',6000.00,5460.00,NULL,1,'2026-05-04 10:36:09');
/*!40000 ALTER TABLE `compras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_inventario`
--

DROP TABLE IF EXISTS `movimientos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_inventario` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` int unsigned NOT NULL,
  `tipo` enum('entrada','salida','ajuste_inicial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `stock_antes` decimal(10,2) NOT NULL,
  `stock_despues` decimal(10,2) NOT NULL,
  `referencia_tipo` enum('compra','venta','inicial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia_id` int unsigned DEFAULT NULL,
  `usuario_id` int unsigned NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `movimientos_inventario_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_inventario`
--

LOCK TABLES `movimientos_inventario` WRITE;
/*!40000 ALTER TABLE `movimientos_inventario` DISABLE KEYS */;
INSERT INTO `movimientos_inventario` VALUES (1,1,'ajuste_inicial',0.06,0.00,0.06,'inicial',NULL,1,'2026-05-03 22:05:12'),(2,1,'ajuste_inicial',9.94,0.06,10.00,'inicial',NULL,1,'2026-05-03 22:46:22'),(3,4,'ajuste_inicial',10.00,0.00,10.00,'inicial',NULL,1,'2026-05-03 22:46:43'),(4,2,'ajuste_inicial',10.00,0.00,10.00,'inicial',NULL,1,'2026-05-03 22:46:51'),(5,5,'ajuste_inicial',10.00,0.00,10.00,'inicial',NULL,1,'2026-05-03 22:47:00'),(6,3,'ajuste_inicial',10.00,0.00,10.00,'inicial',NULL,1,'2026-05-03 22:47:07'),(7,4,'salida',1.00,10.00,9.00,'venta',1,1,'2026-05-03 23:13:09'),(8,4,'salida',2.00,9.00,7.00,'venta',2,3,'2026-05-03 23:15:43'),(9,1,'entrada',10.00,10.00,20.00,'compra',1,1,'2026-05-03 23:31:11'),(10,1,'entrada',5.00,20.00,25.00,'compra',2,1,'2026-05-03 23:34:09'),(11,1,'entrada',3.00,25.00,28.00,'compra',3,1,'2026-05-04 10:08:08'),(12,1,'entrada',2.00,28.00,30.00,'compra',4,1,'2026-05-04 10:20:01'),(13,1,'entrada',5.00,30.00,35.00,'compra',5,1,'2026-05-04 10:34:02'),(14,1,'entrada',0.91,35.00,35.91,'compra',6,1,'2026-05-04 10:36:09'),(15,1,'entrada',10.00,35.91,45.91,'compra',7,1,'2026-05-06 06:51:26'),(16,1,'salida',10.00,45.91,35.91,'compra',7,1,'2026-05-06 07:01:28'),(17,4,'salida',2.00,7.00,5.00,'venta',3,1,'2026-05-06 07:03:44'),(18,5,'salida',1.00,10.00,9.00,'venta',4,1,'2026-05-06 07:05:06'),(19,7,'ajuste_inicial',20.00,0.00,20.00,'inicial',NULL,1,'2026-05-06 07:11:52'),(20,4,'salida',1.00,5.00,4.00,'venta',5,1,'2026-05-06 16:00:21'),(21,4,'ajuste_inicial',1.00,4.00,3.00,'inicial',NULL,1,'2026-05-06 16:02:29'),(22,6,'ajuste_inicial',20.00,0.00,20.00,'inicial',NULL,1,'2026-05-07 11:58:27');
/*!40000 ALTER TABLE `movimientos_inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_id` int unsigned NOT NULL COMMENT 'FK → tipos_producto.id',
  `presentacion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ej: 250g, 500g. Solo si el tipo requiere_presentacion=1',
  `unidad` enum('kg','g','lb','und') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kg' COMMENT 'Se copia automáticamente de tipos_producto.unidad al crear',
  `stock_actual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_minimo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `precio_venta` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Precio sugerido de venta',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_producto_tipo` (`tipo_id`),
  CONSTRAINT `fk_producto_tipo` FOREIGN KEY (`tipo_id`) REFERENCES `tipos_producto` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,'Cacao en Grano Seco',1,NULL,'kg',35.91,50.00,4000.00,1,'2026-05-03 17:04:57'),(2,'Chocolate de Mesa 250g',2,'250g','und',10.00,20.00,4500.00,1,'2026-05-03 17:04:57'),(3,'Chocolate de Mesa 500g',2,'500g','und',10.00,20.00,8500.00,1,'2026-05-03 17:04:57'),(4,'Chocolate de Mesa 1000g',2,'1000g','und',3.00,10.00,16000.00,1,'2026-05-03 17:04:57'),(5,'Chocolate de Mesa 25g',2,'25g','und',9.00,10.00,2000.00,1,'2026-05-03 22:45:04'),(6,'chocolate de mesa 2000g',2,'2000g','und',20.00,20.00,30000.00,1,'2026-05-06 07:10:15'),(7,'chocolate de mesa 1500g',2,'1500g','und',20.00,50.00,25000.00,1,'2026-05-06 07:11:52');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedores` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_doc` enum('NIT','CC','CE','Pasaporte') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CC',
  `num_doc` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `digito_ver` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_proveedor` enum('Agricultor','Intermediario','Cooperativa','Empresa') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Agricultor',
  `persona_contacto` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departamento` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_proveedor_doc` (`tipo_doc`,`num_doc`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (1,'Finca La Esperanza','CC','87654321',NULL,'Agricultor',NULL,'3141112233',NULL,NULL,'Tumaco','Narino','2026-04-30 21:28:02'),(2,'Cacao del Sur SAS','NIT','900111222',NULL,'Empresa',NULL,'3004445566',NULL,NULL,'Barbacoas','Narino','2026-04-30 21:28:12'),(3,'Agroexport Narino','NIT','800999888',NULL,'Cooperativa',NULL,'3167778899',NULL,NULL,'Ipiales','Narino','2026-04-30 21:28:19'),(4,'sofia alarcon','CC','1087781140',NULL,'Agricultor','sofia A.','3154491214',NULL,NULL,NULL,NULL,'2026-04-30 22:10:38');
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrador'),(2,'Gerente'),(3,'Empleado');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_producto`
--

DROP TABLE IF EXISTS `tipos_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_producto` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: Cacao en grano seco',
  `slug` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Identificador interno sin espacios: cacao_grano',
  `unidad` enum('kg','g','lb','und') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kg' COMMENT 'Unidad fija de inventario para este tipo',
  `unidad_venta` enum('kg','g','lb','und') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'und' COMMENT 'Unidad que aparece en facturas y formulario de ventas',
  `requiere_presentacion` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = pide presentacion al crear producto',
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tipo_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_producto`
--

LOCK TABLES `tipos_producto` WRITE;
/*!40000 ALTER TABLE `tipos_producto` DISABLE KEYS */;
INSERT INTO `tipos_producto` VALUES (1,'Cacao en grano seco','cacao_grano','kg','kg',0,'Cacao en grano seco fermentado y secado',1,'2026-05-03 17:00:00'),(2,'Chocolate de mesa','chocolate_mesa','und','und',1,'Chocolate de mesa en distintas presentaciones empacadas',1,'2026-05-03 17:00:00');
/*!40000 ALTER TABLE `tipos_producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol_id` tinyint unsigned NOT NULL DEFAULT '3',
  `ultimo_acceso` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `rol_id` (`rol_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','admin@chocotumac.com','$2y$12$zjXB5m3lAgnGCzw6rQuxme/fE6VJguWESJTxlTPbJ0hPZukhkkeG2','3209876546',1,'2026-05-07 11:57:46','2026-04-30 21:24:36'),(2,'Nathalia Mejia','nathmejia@chocotumac.com','$2y$12$J2igzaiyojeFfyLtBALnW.THGCRErMn8pBAqA7pdOHIRlm.Mfuynq','3158539049',1,'2026-04-30 22:12:09','2026-04-30 21:44:39'),(3,'David Gomes','davidg@chocotumac.com','$2y$12$7hLToR6elqeG6OclmRC3W.VjuU9kMLnGriMy/zp64VMxNYyIy7Coi','3154491214',3,'2026-05-06 18:15:59','2026-04-30 21:50:12'),(5,'isaura Ruiz','isaura2022@chocotumac.com','$2y$12$YcQIdxkxI9GhHKBEIb/HCOddPesr5CZInUYYBxa5ZkA63.rLFLQRm','3116789043',1,'2026-04-30 22:22:19','2026-04-30 21:53:06'),(9,'Elkin Buitrago','elkin12@chocotumac.com','$2y$12$z08Wdeo4waKXmjRCnq8Oa.CfsXJqu4rXYMpLnxg3WBrzPUthKzCgO',NULL,2,'2026-05-03 23:20:45','2026-05-03 23:20:13');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ventas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Código único de factura: FAC-YYYY-NNNN',
  `cliente_id` int unsigned DEFAULT NULL,
  `cliente_ocasional` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nombre del cliente cuando no está registrado en el sistema',
  `doc_ocasional_tipo` enum('CC','CE','NIT','Pasaporte') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc_ocasional_num` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producto_id` int unsigned NOT NULL,
  `fecha` date NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Base gravable antes de impuestos',
  `iva_porcentaje` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Porcentaje IVA aplicado (0, 5, 19)',
  `iva_valor` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Valor del IVA calculado',
  `total` decimal(10,2) NOT NULL COMMENT 'Total final incluyendo impuestos',
  `forma_pago` enum('contado','credito') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'contado',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `usuario_id` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_venta_codigo` (`codigo`),
  KEY `producto_id` (`producto_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `fk_venta_cliente` (`cliente_id`),
  CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `ventas_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
INSERT INTO `ventas` VALUES (1,'FAC-2026-0001',NULL,'Cliente general',NULL,NULL,4,'2026-05-04',1.00,16000.00,16000.00,0.00,0.00,16000.00,'contado',NULL,1,'2026-05-03 23:13:09'),(2,'FAC-2026-0002',4,NULL,NULL,NULL,4,'2026-05-04',2.00,16000.00,32000.00,0.00,0.00,32000.00,'contado',NULL,3,'2026-05-03 23:15:43'),(3,'FAC-2026-0003',2,NULL,NULL,NULL,4,'2026-05-06',2.00,16000.00,32000.00,0.00,0.00,32000.00,'contado',NULL,1,'2026-05-06 07:03:44'),(4,'FAC-2026-0004',NULL,'luis perez','CC',NULL,5,'2026-05-06',1.00,2000.00,2000.00,0.00,0.00,2000.00,'contado',NULL,1,'2026-05-06 07:05:06'),(5,'FAC-2026-0005',2,NULL,NULL,NULL,4,'2026-05-06',1.00,16000.00,16000.00,0.00,0.00,16000.00,'contado',NULL,1,'2026-05-06 16:00:21');
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'chocolatetumaco'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-07 16:16:55
