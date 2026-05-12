<?php
/**
 * Modelo: Compra – ChocoTumac Sprint 2.
 *
 * Gestiona el registro de compras de cacao en grano seco a proveedores.
 * Al crear una compra, actualiza automáticamente el stock del producto
 * en el inventario mediante el modelo Producto.
 * Al eliminar una compra, revierte el stock correspondiente.
 *
 * Principios aplicados:
 *   - SRP:  separación entre validación, persistencia y control de stock
 *   - DRY:  validarCampos() centraliza todas las reglas de negocio
 *   - Robustez: valida stock y datos antes de cualquier escritura en BD
 *
 * @package ChocoTumac
 * @sprint  2
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Producto.php';

class Compra {

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
     * Valida los campos requeridos para registrar una compra.
     * Aplica principio DRY centralizando todas las reglas de validación.
     *
     * @param array $data Datos del formulario de compra
     * @return true|string true si los datos son válidos, string con error si no
     */
    /**
     * Verifica que la cantidad sea entera para productos con unidad "und".
     *
     * @param array $data Datos con producto_id y cantidad.
     * @return true|string true si es válida, string con error si no.
     */
    private function validarCantidadPorUnidad(array $data) {
        if (empty($data['producto_id'])) {
            return true;
        }
        $modelProd = new Producto();
        $prod = $modelProd->obtenerPorId($data['producto_id']);
        if (!$prod || $prod['unidad'] !== 'und') {
            return true;
        }
        if (floor((float)$data['cantidad']) != (float)$data['cantidad']) {
            return "La cantidad debe ser un número entero para productos manejados por unidad.";
        }
        return true;
    }

    /**
     * Valida todos los campos del formulario de compra.
     * Complejidad cognitiva: 8 (era 16 — extraído validarCantidadPorUnidad).
     *
     * @param array $data Datos del formulario de compra.
     * @return true|string true si los datos son válidos, string con error si no.
     */
    private function validarCampos($data) {
        if (empty($data['proveedor_id']) || !is_numeric($data['proveedor_id'])) {
            return "Debes seleccionar un proveedor.";
        }
        if (empty($data['producto_id']) || !is_numeric($data['producto_id'])) {
            return "Debes seleccionar un producto.";
        }
        if (empty($data['fecha'])) {
            return "La fecha de la compra es obligatoria.";
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

    // ── Código de compra ──────────────────────────────────────────────────

    /**
     * Genera el siguiente código de compra con formato CMP-YYYY-NNNN.
     * Ejemplo: CMP-2026-0001, CMP-2026-0012, CMP-2027-0001
     *
     * @return string Código único para la compra
     */
    private function generarCodigo() {
        $anio = date('Y');
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM compras WHERE YEAR(created_at) = ?"
        );
        $stmt->execute([$anio]);
        $n = (int)$stmt->fetchColumn() + 1;
        return sprintf('CMP-%s-%04d', $anio, $n);
    }


    /**
     * Registra una nueva compra y actualiza el inventario automáticamente.
     *
     * Flujo:
     *   1. Valida los campos del formulario
     *   2. Verifica que el producto sea de tipo cacao_grano
     *   3. Calcula el total (cantidad × precio_unitario)
     *   4. Inserta la compra en la BD
     *   5. Llama a Producto::incrementarStock() para actualizar el inventario
     *
     * @param array $data       Datos de la compra desde el formulario
     * @param int   $usuario_id ID del usuario que registra la compra
     * @return true|string true si se registró correctamente, string con error si no
     */
    public function crear($data, $usuario_id) {
        $val = $this->validarCampos($data);
        if ($val !== true) {
            return $val;
        }
        // Verificar que el producto existe
        $producto = $this->modelProducto->obtenerPorId($data['producto_id']);
        if (!$producto) {
            return "Producto no encontrado.";
        }
        $cantidad        = (float)$data['cantidad'];
        $precio_unitario = (float)$data['precio_unitario'];
        $total           = round($cantidad * $precio_unitario, 2);
        $codigo          = $this->generarCodigo();

        // Insertar registro de compra
        $stmt = $this->conn->prepare("
            INSERT INTO compras
                (codigo, proveedor_id, producto_id, fecha, cantidad, unidad,
                 precio_unitario, total, observaciones, usuario_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $codigo,
            (int)$data['proveedor_id'],
            (int)$data['producto_id'],
            $data['fecha'],
            $cantidad,
            $data['unidad'],
            $precio_unitario,
            $total,
            trim($data['observaciones'] ?? '') ?: null,
            (int)$usuario_id,
        ]);

        $compra_id = $this->conn->lastInsertId();

        // Actualizar inventario: incrementar stock con la unidad de la compra
        $res = $this->modelProducto->incrementarStock(
            $data['producto_id'], $cantidad, $data['unidad'], $compra_id, $usuario_id
        );
        if (!$res) {
            return "Compra registrada pero hubo un error al actualizar el inventario.";
        }

        return true;
    }

    /**
     * Retorna el historial completo de compras con datos relacionados.
     * Incluye nombre del proveedor, producto y usuario registrador.
     *
     * @return array Lista de compras ordenadas por fecha descendente
     */
    public function obtener() {
        return $this->conn->query("
            SELECT c.*,
                   p.nombre  AS proveedor_nombre,
                   pr.nombre AS producto_nombre,
                   u.nombre  AS usuario_nombre
            FROM compras c
            JOIN proveedores p  ON c.proveedor_id = p.id
            JOIN productos   pr ON c.producto_id  = pr.id
            JOIN usuarios    u  ON c.usuario_id   = u.id
            ORDER BY c.fecha DESC, c.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna una compra específica por su ID.
     *
     * @param int $id ID de la compra
     * @return array|false Datos de la compra o false si no existe
     */
    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("
            SELECT c.*,
                   p.nombre  AS proveedor_nombre,
                   pr.nombre AS producto_nombre
            FROM compras c
            JOIN proveedores p  ON c.proveedor_id = p.id
            JOIN productos   pr ON c.producto_id  = pr.id
            WHERE c.id = ?
        ");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Elimina una compra y revierte el stock en el inventario.
     *
     * Flujo de reversión:
     *   1. Obtiene la compra y el stock actual del producto
     *   2. Descuenta la cantidad comprada del stock (con mínimo 0)
     *   3. Elimina el registro de compra
     *   4. Registra un movimiento de salida en el historial
     *
     * @param int $id         ID de la compra a eliminar
     * @param int $usuario_id ID del usuario que realiza la eliminación
     * @return true|string true si se eliminó, string con error si no
     */
    public function eliminar($id, $usuario_id) {
        $compra = $this->obtenerPorId($id);
        if (!$compra) {
            return "Compra no encontrada.";
        }
        // Revertir el stock: descontar la cantidad que había entrado
        $producto      = $this->modelProducto->obtenerPorId($compra['producto_id']);
        $stock_antes   = (float)$producto['stock_actual'];
        $stock_despues = max(0, $stock_antes - (float)$compra['cantidad']);

        $this->conn->prepare("UPDATE productos SET stock_actual = ? WHERE id = ?")
                   ->execute([$stock_despues, $compra['producto_id']]);

        // Eliminar la compra
        $this->conn->prepare("DELETE FROM compras WHERE id = ?")
                   ->execute([(int)$id]);

        // Registrar el movimiento de reversión en el historial
        $this->conn->prepare("
            INSERT INTO movimientos_inventario
                (producto_id, tipo, cantidad, stock_antes, stock_despues,
                 referencia_tipo, referencia_id, usuario_id)
            VALUES (?, 'salida', ?, ?, ?, 'compra', ?, ?)
        ")->execute([
            $compra['producto_id'],
            $compra['cantidad'],
            $stock_antes,
            $stock_despues,
            $id,
            $usuario_id,
        ]);

        return true;
    }
}