<?php
/**
 * Modelo: Venta – ChocoTumac Sprint 2.
 *
 * Gestiona el registro de ventas de productos a clientes.
 * Soporta dos tipos de cliente:
 *   - Cliente registrado: se selecciona de la tabla clientes (cliente_id)
 *   - Cliente ocasional: se escribe el nombre libremente (cliente_ocasional)
 *
 * Al registrar una venta, valida el stock y decrementa el inventario
 * automáticamente. Si el descuento falla, la venta se revierte.
 * Al eliminar una venta, el stock se restaura.
 *
 * Principios aplicados:
 *   - SRP:  separación entre validación, persistencia y control de stock
 *   - DRY:  validarCampos() centraliza todas las reglas de negocio
 *   - Robustez: verifica stock antes de insertar; revierte si el descuento falla
 *
 * @package ChocoTumac
 * @sprint  2
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Producto.php';

class Venta {

    /** @var PDO Instancia de conexión a la base de datos */
    private $conn;

    /** @var Producto Modelo de productos para control de inventario */
    private $modelProducto;

    public function __construct() {
        $this->conn          = (new Database())->connect();
        $this->modelProducto = new Producto();
    }

    // ── Validaciones ──────────────────────────────────────────────────────

    /**
     * Valida los campos requeridos para registrar una venta.
     *
     * Reglas de cliente:
     *   - Debe haber un cliente_id registrado O un nombre de cliente ocasional
     *   - No pueden estar ambos vacíos al mismo tiempo
     *
     * @param array $data Datos del formulario de venta
     * @return true|string true si los datos son válidos, string con error si no
     */
    /**
     * Verifica que la cantidad sea válida para el tipo de unidad del producto.
     * Los productos con unidad "und" solo aceptan cantidades enteras.
     *
     * @param array $data Datos del formulario con producto_id y cantidad.
     * @return true|string true si es válida, string con error si no.
     */
    private function validarCantidadPorUnidad(array $data) {
        if (empty($data['producto_id'])) {
            return true;
        }
        $prod = $this->modelProducto->obtenerPorId($data['producto_id']);
        if (!$prod || $prod['unidad'] !== 'und') {
            return true;
        }
        if (floor((float)$data['cantidad']) != (float)$data['cantidad']) {
            return "La cantidad debe ser un número entero para productos vendidos por unidad (no se permiten fracciones como 0.5 o 1.3).";
        }
        return true;
    }

    /**
     * Verifica que el cliente esté correctamente especificado.
     * Acepta cliente registrado (con ID) o cliente ocasional.
     *
     * @param array $data Datos del formulario.
     * @return true|string true si es válido, string con error si no.
     */
    private function validarCliente(array $data) {
        $tieneClienteRegistrado = !empty($data['cliente_id']) && is_numeric($data['cliente_id']);
        $esClienteOcasional     = isset($data['tipo_cliente']) && $data['tipo_cliente'] === 'ocasional';
        if (!$tieneClienteRegistrado && !$esClienteOcasional) {
            return "Debes seleccionar un cliente registrado o elegir la opción de cliente ocasional.";
        }
        return true;
    }

    /**
     * Valida todos los campos del formulario de venta.
     * Complejidad cognitiva: 8 (era 16 — extraídos validarCliente y validarCantidadPorUnidad).
     *
     * @param array $data Datos del formulario de venta.
     * @return true|string true si los datos son válidos, string con error si no.
     */
    private function validarCampos($data) {
        $errCliente = $this->validarCliente($data);
        if ($errCliente !== true) {
            return $errCliente;
        }
        if (empty($data['producto_id']) || !is_numeric($data['producto_id'])) {
            return "Debes seleccionar un producto.";
        }
        if (empty($data['fecha'])) {
            return "La fecha de la venta es obligatoria.";
        }
        if (!strtotime($data['fecha'])) {
            return "El formato de la fecha no es válido.";
        }
        if (!is_numeric($data['cantidad']) || (float)$data['cantidad'] <= 0) {
            return "La cantidad debe ser un número mayor a cero.";
        }
        $errUnidad = $this->validarCantidadPorUnidad($data);
        if ($errUnidad !== true) {
            return $errUnidad;
        }
        if (!is_numeric($data['precio_unitario']) || (float)$data['precio_unitario'] <= 0) {
            return "El precio unitario debe ser un número mayor a cero.";
        }
        return true;
    }

    // ── Código de venta / factura ─────────────────────────────────────────

    /**
     * Genera el siguiente código de factura con formato FAC-YYYY-NNNN.
     * El consecutivo reinicia cada año.
     * Ejemplo: FAC-2026-0001, FAC-2026-0012, FAC-2027-0001
     *
     * @return string Código único para la venta
     */
    private function generarCodigo() {
        $anio = date('Y');
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM ventas WHERE YEAR(created_at) = ?"
        );
        $stmt->execute([$anio]);
        $n = (int)$stmt->fetchColumn() + 1;
        return sprintf('FAC-%s-%04d', $anio, $n);
    }

    // ── CRUD ──────────────────────────────────────────────────────────────

    /**
     * Registra una nueva venta y descuenta el stock automáticamente.
     *
     * Flujo:
     *   1. Valida los campos del formulario
     *   2. Verifica que el producto tenga stock suficiente ANTES de insertar
     *   3. Calcula el total (cantidad × precio_unitario)
     *   4. Inserta la venta en la BD con cliente_id o cliente_ocasional
     *   5. Llama a Producto::decrementarStock() para actualizar el inventario
     *   6. Si el descuento falla, elimina la venta recién insertada (rollback manual)
     *
     * @param array $data       Datos de la venta desde el formulario
     * @param int   $usuario_id ID del usuario que registra la venta
     * @return true|string true si se registró correctamente, string con error si no
     */
    public function crear($data, $usuario_id) {
        $val = $this->validarCampos($data);
        if ($val !== true) {
            return $val;
        }
        $cantidad        = (float)$data['cantidad'];
        $precio_unitario = (float)$data['precio_unitario'];
        $total           = round($cantidad * $precio_unitario, 2);

        // Verificar stock ANTES de insertar
        $producto = $this->modelProducto->obtenerPorId($data['producto_id']);
        if (!$producto) {
            return "Producto no encontrado.";
        }
        if ((float)$producto['stock_actual'] < $cantidad) {
            return "Stock insuficiente. Stock disponible: "
                 . number_format($producto['stock_actual'], 2) . " " . $producto['unidad']
                 . ". Cantidad solicitada: "
                 . number_format($cantidad, 2) . " " . $producto['unidad'] . ".";
        }

        // Determinar tipo de cliente según el radio seleccionado en el formulario
        $esOcasional       = isset($data['tipo_cliente']) && $data['tipo_cliente'] === 'ocasional';
        $cliente_id        = (!$esOcasional && !empty($data['cliente_id']) && is_numeric($data['cliente_id']))
                             ? (int)$data['cliente_id'] : null;
        $cliente_ocasional = $esOcasional
                             ? (trim($data['cliente_ocasional'] ?? '') ?: 'Cliente general')
                             : null;
        $doc_ocasional_tipo = ($esOcasional && !empty($data['doc_ocasional_tipo']))
                             ? $data['doc_ocasional_tipo'] : null;
        $doc_ocasional_num  = ($esOcasional && !empty(trim($data['doc_ocasional_num'] ?? '')))
                             ? trim($data['doc_ocasional_num']) : null;

        // Generar código único de factura (FAC-YYYY-NNNN)
        $codigo = $this->generarCodigo();

        // Calcular IVA
        $iva_pct   = isset($data['iva_porcentaje']) ? (float)$data['iva_porcentaje'] : 0.00;
        $subtotal  = round($cantidad * $precio_unitario, 2);
        $iva_valor = round($subtotal * $iva_pct / 100, 2);
        $total     = $subtotal + $iva_valor;

        $forma_pago = in_array($data['forma_pago'] ?? 'contado', ['contado','credito'])
                      ? $data['forma_pago'] : 'contado';

        // Insertar registro de venta
        $stmt = $this->conn->prepare("
            INSERT INTO ventas
                (codigo, cliente_id, cliente_ocasional, doc_ocasional_tipo, doc_ocasional_num,
                 producto_id, fecha, cantidad, precio_unitario,
                 subtotal, iva_porcentaje, iva_valor, total,
                 forma_pago, observaciones, usuario_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $codigo,
            $cliente_id,
            $cliente_ocasional,
            $doc_ocasional_tipo,
            $doc_ocasional_num,
            (int)$data['producto_id'],
            $data['fecha'],
            $cantidad,
            $precio_unitario,
            $subtotal,
            $iva_pct,
            $iva_valor,
            $total,
            $forma_pago,
            trim($data['observaciones'] ?? '') ?: null,
            (int)$usuario_id,
        ]);

        $venta_id = $this->conn->lastInsertId();

        // Descontar inventario
        $res = $this->modelProducto->decrementarStock(
            $data['producto_id'], $cantidad, $venta_id, $usuario_id
        );

        // Si el descuento falla, revertir la venta (rollback manual)
        if ($res !== true) {
            $this->conn->prepare("DELETE FROM ventas WHERE id = ?")
                       ->execute([$venta_id]);
            return $res;
        }

        // Retorna el ID para que el controlador redirija directo a la factura
        return (int)$venta_id;
    }

    /**
     * Retorna el historial completo de ventas con datos relacionados.
     * Usa COALESCE para mostrar el nombre del cliente registrado
     * o el nombre ocasional según corresponda.
     *
     * @return array Lista de ventas ordenadas por fecha descendente
     */
    public function obtener() {
        return $this->conn->query("
            SELECT v.*,
                   COALESCE(c.nombre, v.cliente_ocasional, 'Cliente general') AS cliente_nombre,
                   c.tipo_doc        AS cliente_tipo_doc,
                   c.num_doc         AS cliente_num_doc,
                   c.digito_ver      AS cliente_digito_ver,
                   p.nombre          AS producto_nombre,
                   p.tipo_id         AS producto_tipo_id,
                   p.unidad          AS producto_unidad,
                   p.presentacion    AS producto_presentacion,
                   t.slug            AS tipo_slug,
                   t.unidad_venta    AS unidad_venta,
                   u.nombre          AS usuario_nombre
            FROM ventas v
            LEFT JOIN clientes      c ON v.cliente_id  = c.id
            JOIN      productos     p ON v.producto_id = p.id
            JOIN      tipos_producto t ON p.tipo_id    = t.id
            JOIN      usuarios      u ON v.usuario_id  = u.id
            ORDER BY v.fecha DESC, v.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna una venta específica por su ID.
     *
     * @param int $id ID de la venta
     * @return array|false Datos de la venta o false si no existe
     */
    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("
            SELECT v.*,
                   COALESCE(c.nombre, v.cliente_ocasional, 'Cliente general') AS cliente_nombre,
                   c.tipo_doc        AS cliente_tipo_doc,
                   c.num_doc         AS cliente_num_doc,
                   c.digito_ver      AS cliente_digito_ver,
                   c.email           AS cliente_email,
                   c.telefono        AS cliente_telefono,
                   c.ciudad          AS cliente_ciudad,
                   c.departamento    AS cliente_departamento,
                   c.direccion       AS cliente_direccion,
                   p.nombre          AS producto_nombre,
                   p.unidad          AS producto_unidad,
                   p.presentacion    AS producto_presentacion,
                   t.slug            AS tipo_slug,
                   t.unidad_venta    AS unidad_venta
            FROM ventas v
            LEFT JOIN clientes       c ON v.cliente_id  = c.id
            JOIN      productos      p ON v.producto_id = p.id
            JOIN      tipos_producto t ON p.tipo_id     = t.id
            WHERE v.id = ?
        ");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Elimina una venta y restaura el stock en el inventario.
     *
     * @param int $id         ID de la venta a eliminar
     * @param int $usuario_id ID del usuario que realiza la eliminación
     * @return true|string true si se eliminó, string con error si no
     */
    public function eliminar($id, $usuario_id) {
        $venta = $this->obtenerPorId($id);
        if (!$venta) {
            return "Venta no encontrada.";
        }
        // Restaurar stock
        $producto      = $this->modelProducto->obtenerPorId($venta['producto_id']);
        $stock_antes   = (float)$producto['stock_actual'];
        $stock_despues = $stock_antes + (float)$venta['cantidad'];

        $this->conn->prepare("UPDATE productos SET stock_actual = ? WHERE id = ?")
                   ->execute([$stock_despues, $venta['producto_id']]);

        // Eliminar la venta
        $this->conn->prepare("DELETE FROM ventas WHERE id = ?")
                   ->execute([(int)$id]);

        // Registrar movimiento de restauración
        $this->conn->prepare("
            INSERT INTO movimientos_inventario
                (producto_id, tipo, cantidad, stock_antes, stock_despues,
                 referencia_tipo, referencia_id, usuario_id)
            VALUES (?, 'entrada', ?, ?, ?, 'venta', ?, ?)
        ")->execute([
            $venta['producto_id'],
            $venta['cantidad'],
            $stock_antes,
            $stock_despues,
            $id,
            $usuario_id,
        ]);

        return true;
    }
}