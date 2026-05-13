<?php
/**
 * Modelo: Reporte – ChocoTumac Sprint 3.
 *
 * Centraliza todas las consultas de reportes del sistema.
 * Solo accesible por usuarios con rol Gerente (rol_id = 2).
 *
 * Reportes disponibles:
 *   - Ventas por cliente, filtradas por fecha y/o cliente
 *   - Compras por proveedor, filtradas por fecha y/o proveedor
 *   - Inventario actualizado con estado de stock
 *   - Productos más vendidos (top 10)
 *   - Totales y resumen general
 *
 * @package ChocoTumac
 * @sprint  3
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Alias de columna fecha para ventas — evita duplicar el literal (php:S1192).
 * Usado en filtroFecha(), ventas(), totalesVentas() y productosMasVendidos().
 */
define('SQL_ALIAS_FECHA_VENTA',  'v.fecha');

/**
 * Alias de columna fecha para compras — evita duplicar el literal (php:S1192).
 * Usado en filtroFecha(), compras() y totalesCompras().
 */
define('SQL_ALIAS_FECHA_COMPRA', 'c.fecha');

class Reporte
{
    /** @var PDO */
    private $conn;

    public function __construct()
    {
        $this->conn = (new Database())->connect();
    }

    // ── Helpers de filtro ─────────────────────────────────────────────

    /**
     * Construye condiciones WHERE y parámetros para filtros de fecha.
     * Centraliza los literales "alias >= ?" y "alias <= ?" (php:S1192).
     *
     * @param string      $alias  Alias de la columna fecha (ej. "v.fecha")
     * @param string|null $desde  Fecha inicio (Y-m-d)
     * @param string|null $hasta  Fecha fin    (Y-m-d)
     * @return array{ where: string[], params: array }
     */
    private function filtroFecha(string $alias, ?string $desde, ?string $hasta): array
    {
        $where  = [];
        $params = [];

        if (!empty($desde)) {
            $where[]  = "$alias >= ?";
            $params[] = $desde;
        }
        if (!empty($hasta)) {
            $where[]  = "$alias <= ?";
            $params[] = $hasta;
        }

        return ['where' => $where, 'params' => $params];
    }

    /**
     * Construye filtros completos para ventas: fecha + cliente + búsqueda.
     * Elimina la duplicación del bloque de 8 líneas en ventas() y totalesVentas().
     *
     * @param string|null $desde
     * @param string|null $hasta
     * @param int|null    $cliente_id
     * @param string|null $busqueda
     * @return array{ cond: string, params: array }
     */
    private function filtrosVentas(
        ?string $desde,
        ?string $hasta,
        ?int    $cliente_id,
        ?string $busqueda
    ): array {
        $fecha  = $this->filtroFecha(SQL_ALIAS_FECHA_VENTA, $desde, $hasta);
        $where  = array_merge(['1=1'], $fecha['where']);
        $params = $fecha['params'];

        if (!empty($cliente_id)) {
            $where[]  = 'v.cliente_id = ?';
            $params[] = (int)$cliente_id;
        }
        if (!empty($busqueda)) {
            $like     = '%' . $busqueda . '%';
            $where[]  = '(v.codigo LIKE ? OR p.nombre LIKE ? OR COALESCE(c.nombre, v.cliente_ocasional) LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        return ['cond' => implode(' AND ', $where), 'params' => $params];
    }

    /**
     * Construye filtros completos para compras: fecha + proveedor + búsqueda.
     * Elimina la duplicación del bloque de 8 líneas en compras() y totalesCompras().
     *
     * @param string|null $desde
     * @param string|null $hasta
     * @param int|null    $proveedor_id
     * @param string|null $busqueda
     * @return array{ cond: string, params: array }
     */
    private function filtrosCompras(
        ?string $desde,
        ?string $hasta,
        ?int    $proveedor_id,
        ?string $busqueda
    ): array {
        $fecha  = $this->filtroFecha(SQL_ALIAS_FECHA_COMPRA, $desde, $hasta);
        $where  = array_merge(['1=1'], $fecha['where']);
        $params = $fecha['params'];

        if (!empty($proveedor_id)) {
            $where[]  = 'c.proveedor_id = ?';
            $params[] = (int)$proveedor_id;
        }
        if (!empty($busqueda)) {
            $like     = '%' . $busqueda . '%';
            $where[]  = '(c.codigo LIKE ? OR p.nombre LIKE ? OR pr.nombre LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        return ['cond' => implode(' AND ', $where), 'params' => $params];
    }

    // ── Reporte de Ventas ─────────────────────────────────────────────

    /**
     * Retorna el listado de ventas con filtros opcionales.
     *
     * @param string|null $desde       Fecha inicio
     * @param string|null $hasta       Fecha fin
     * @param int|null    $cliente_id  Filtrar por cliente registrado
     * @param string|null $busqueda    Palabra clave (código, cliente, producto)
     * @return array
     */
    public function ventas(
        ?string $desde      = null,
        ?string $hasta      = null,
        ?int    $cliente_id = null,
        ?string $busqueda   = null
    ): array {
        ['cond' => $cond, 'params' => $params] =
            $this->filtrosVentas($desde, $hasta, $cliente_id, $busqueda);

        $stmt = $this->conn->prepare("
            SELECT
                v.id,
                v.codigo,
                v.fecha,
                COALESCE(c.nombre, v.cliente_ocasional, 'Cliente general') AS cliente,
                COALESCE(c.tipo_doc, v.doc_ocasional_tipo, '')              AS doc_tipo,
                COALESCE(c.num_doc,  v.doc_ocasional_num,  '')              AS doc_num,
                p.nombre        AS producto,
                t.unidad_venta  AS unidad,
                v.cantidad,
                v.precio_unitario,
                v.subtotal,
                v.iva_porcentaje,
                v.iva_valor,
                v.total,
                v.forma_pago,
                u.nombre        AS registrado_por
            FROM ventas v
            LEFT JOIN clientes       c ON v.cliente_id  = c.id
            JOIN      productos      p ON v.producto_id = p.id
            JOIN      tipos_producto t ON p.tipo_id     = t.id
            JOIN      usuarios       u ON v.usuario_id  = u.id
            WHERE $cond
            ORDER BY v.fecha DESC, v.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Totales del reporte de ventas (suma, promedio, conteo).
     */
    public function totalesVentas(
        ?string $desde      = null,
        ?string $hasta      = null,
        ?int    $cliente_id = null,
        ?string $busqueda   = null
    ): array {
        ['cond' => $cond, 'params' => $params] =
            $this->filtrosVentas($desde, $hasta, $cliente_id, $busqueda);

        $stmt = $this->conn->prepare("
            SELECT
                COUNT(v.id)       AS transacciones_totales,
                SUM(v.subtotal)   AS subtotal_suma,
                SUM(v.iva_valor)  AS suma_iva,
                SUM(v.total)      AS suma_total,
                AVG(v.total)      AS promedio_venta,
                MAX(v.total)      AS venta_maxima,
                MIN(v.total)      AS venta_minima
            FROM ventas v
            LEFT JOIN clientes       c ON v.cliente_id  = c.id
            JOIN      productos      p ON v.producto_id = p.id
            WHERE $cond
        ");
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── Reporte de Compras ────────────────────────────────────────────

    /**
     * Retorna el listado de compras con filtros opcionales.
     */
    public function compras(
        ?string $desde        = null,
        ?string $hasta        = null,
        ?int    $proveedor_id = null,
        ?string $busqueda     = null
    ): array {
        ['cond' => $cond, 'params' => $params] =
            $this->filtrosCompras($desde, $hasta, $proveedor_id, $busqueda);

        $stmt = $this->conn->prepare("
            SELECT
                c.id,
                c.codigo,
                c.fecha,
                pr.nombre       AS proveedor,
                CONCAT(pr.tipo_doc, ' ', pr.num_doc) AS proveedor_doc,
                p.nombre        AS producto,
                c.cantidad,
                c.unidad,
                c.precio_unitario,
                c.total,
                c.observaciones,
                u.nombre        AS registrado_por
            FROM compras c
            JOIN proveedores    pr ON c.proveedor_id = pr.id
            JOIN productos       p ON c.producto_id  = p.id
            JOIN usuarios        u ON c.usuario_id   = u.id
            WHERE $cond
            ORDER BY c.fecha DESC, c.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Totales del reporte de compras.
     */
    public function totalesCompras(
        ?string $desde        = null,
        ?string $hasta        = null,
        ?int    $proveedor_id = null,
        ?string $busqueda     = null
    ): array {
        ['cond' => $cond, 'params' => $params] =
            $this->filtrosCompras($desde, $hasta, $proveedor_id, $busqueda);

        $stmt = $this->conn->prepare("
            SELECT
                COUNT(c.id)   AS transacciones_totales,
                SUM(c.total)  AS suma_total,
                AVG(c.total)  AS promedio_compra,
                MAX(c.total)  AS compra_maxima,
                MIN(c.total)  AS compra_minima
            FROM compras c
            JOIN proveedores pr ON c.proveedor_id = pr.id
            JOIN productos    p ON c.producto_id  = p.id
            WHERE $cond
        ");
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── Reporte de Inventario ─────────────────────────────────────────

    /**
     * Retorna el inventario completo actualizado con estado de stock.
     */
    public function inventario(?string $busqueda = null): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($busqueda)) {
            $like     = '%' . $busqueda . '%';
            $where[]  = '(p.nombre LIKE ? OR t.nombre LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        $cond = implode(' AND ', $where);
        $stmt = $this->conn->prepare("
            SELECT
                p.id,
                p.nombre,
                t.nombre        AS tipo,
                p.presentacion,
                p.unidad,
                t.unidad_venta,
                p.stock_actual,
                p.stock_minimo,
                p.precio_venta,
                p.activo,
                CASE
                    WHEN p.stock_actual = 0               THEN 'sin_stock'
                    WHEN p.stock_actual <= p.stock_minimo THEN 'stock_bajo'
                    ELSE                                       'ok'
                END AS estado_stock,
                (SELECT COUNT(*) FROM movimientos_inventario m
                 WHERE m.producto_id = p.id) AS total_movimientos
            FROM productos      p
            JOIN tipos_producto t ON p.tipo_id = t.id
            WHERE $cond
            ORDER BY t.nombre ASC, p.nombre ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Productos más vendidos ────────────────────────────────────────

    /**
     * Retorna los 10 productos más vendidos por cantidad total.
     *
     * @param string|null $desde  Fecha inicio del período
     * @param string|null $hasta  Fecha fin del período
     * @return array
     */
    public function productosMasVendidos(?string $desde = null, ?string $hasta = null): array
    {
        $fecha  = $this->filtroFecha(SQL_ALIAS_FECHA_VENTA, $desde, $hasta);
        $where  = array_merge(['1=1'], $fecha['where']);
        $params = $fecha['params'];
        $cond   = implode(' AND ', $where);

        $stmt = $this->conn->prepare("
            SELECT
                p.nombre               AS producto,
                t.nombre               AS tipo,
                t.unidad_venta         AS unidad,
                COUNT(v.id)            AS num_ventas,
                SUM(v.cantidad)        AS cantidad_total,
                SUM(v.total)           AS ingresos_total,
                AVG(v.precio_unitario) AS precio_promedio
            FROM ventas v
            JOIN productos      p ON v.producto_id = p.id
            JOIN tipos_producto t ON p.tipo_id     = t.id
            WHERE $cond
            GROUP BY p.id, p.nombre, t.nombre, t.unidad_venta
            ORDER BY cantidad_total DESC
            LIMIT 10
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Listas para selectores ────────────────────────────────────────

    /** Retorna clientes ordenados por nombre para el selector de filtros. */
    public function listaClientes(): array
    {
        return $this->conn->query("
            SELECT id, nombre FROM clientes ORDER BY nombre ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Retorna proveedores ordenados por nombre para el selector de filtros. */
    public function listaProveedores(): array
    {
        return $this->conn->query("
            SELECT id, nombre FROM proveedores ORDER BY nombre ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Resumen general ───────────────────────────────────────────────

    /**
     * Retorna métricas generales del sistema para el dashboard del gerente.
     */
    public function resumenGeneral(): array
    {
        return [
            'ventas_totales'   => $this->conn->query("SELECT COUNT(*) FROM ventas")->fetchColumn(),
            'total_compras'    => $this->conn->query("SELECT COUNT(*) FROM compras")->fetchColumn(),
            'ingresos_total'   => $this->conn->query("SELECT COALESCE(SUM(total),0) FROM ventas")->fetchColumn(),
            'egresos_total'    => $this->conn->query("SELECT COALESCE(SUM(total),0) FROM compras")->fetchColumn(),
            'clientes_activos' => $this->conn->query("SELECT COUNT(*) FROM clientes")->fetchColumn(),
            'productos_activos'=> $this->conn->query("SELECT COUNT(*) FROM productos WHERE activo=1")->fetchColumn(),
            'stock_bajo'       => $this->conn->query("SELECT COUNT(*) FROM productos WHERE activo=1 AND stock_actual <= stock_minimo")->fetchColumn(),
            'sin_stock'        => $this->conn->query("SELECT COUNT(*) FROM productos WHERE activo=1 AND stock_actual = 0")->fetchColumn(),
        ];
    }
}