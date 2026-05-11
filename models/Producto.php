<?php
/**
 * Modelo: Producto – ChocoTumac Sprint 2.
 *
 * El tipo de producto es dinámico: referencia a la tabla `tipos_producto`.
 * La unidad de inventario y la unidad de venta se heredan del tipo.
 *
 * @package ChocoTumac
 * @sprint  2
 */

require_once __DIR__ . '/../config/database.php';

class Producto {

    private $conn;

    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    // ── Tipos de producto ─────────────────────────────────────────────────

    public function obtenerTipos() {
        return $this->conn->query(
            "SELECT * FROM tipos_producto WHERE activo = 1 ORDER BY nombre ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodosTipos() {
        return $this->conn->query(
            "SELECT * FROM tipos_producto ORDER BY nombre ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTipoPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM tipos_producto WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo tipo de producto (solo admin).
     * Genera el slug automáticamente desde el nombre.
     */
    public function crearTipo($data) {
        if (empty(trim($data['nombre']))) return "El nombre del tipo es obligatorio.";
        if (!in_array($data['unidad'], ['kg','g','lb','und'])) return "Unidad de inventario no válida.";
        if (!in_array($data['unidad_venta'], ['kg','g','lb','und'])) return "Unidad de venta no válida.";

        $slug = strtolower(trim($data['nombre']));
        $slug = preg_replace('/\s+/', '_', $slug);
        $slug = preg_replace('/[^a-z0-9_]/', '', $slug);
        $slug = substr($slug, 0, 60);

        $check = $this->conn->prepare("SELECT id FROM tipos_producto WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->fetch()) return "Ya existe un tipo con ese nombre. Usa uno diferente.";

        $this->conn->prepare("
            INSERT INTO tipos_producto
                (nombre, slug, unidad, unidad_venta, requiere_presentacion, descripcion)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            trim($data['nombre']), $slug,
            $data['unidad'], $data['unidad_venta'],
            isset($data['requiere_presentacion']) ? 1 : 0,
            trim($data['descripcion'] ?? '') ?: null,
        ]);
        return true;
    }

    public function actualizarTipo($id, $data) {
        if (empty(trim($data['nombre']))) return "El nombre del tipo es obligatorio.";
        if (!in_array($data['unidad'], ['kg','g','lb','und'])) return "Unidad de inventario no válida.";
        if (!in_array($data['unidad_venta'], ['kg','g','lb','und'])) return "Unidad de venta no válida.";

        $this->conn->prepare("
            UPDATE tipos_producto
            SET nombre=?, unidad=?, unidad_venta=?, requiere_presentacion=?, descripcion=?, activo=?
            WHERE id=?
        ")->execute([
            trim($data['nombre']), $data['unidad'], $data['unidad_venta'],
            isset($data['requiere_presentacion']) ? 1 : 0,
            trim($data['descripcion'] ?? '') ?: null,
            isset($data['activo']) ? 1 : 0,
            (int)$id,
        ]);
        return true;
    }

    // ── Validación de productos ───────────────────────────────────────────

    private function validarCampos($data) {
        if (empty(trim($data['nombre'])))    return "El nombre del producto es obligatorio.";
        if (strlen(trim($data['nombre'])) < 2) return "El nombre debe tener al menos 2 caracteres.";
        if (empty($data['tipo_id']) || !is_numeric($data['tipo_id'])) return "Selecciona un tipo de producto.";
        if (!is_numeric($data['stock_minimo']) || (float)$data['stock_minimo'] < 0) return "El stock mínimo debe ser ≥ 0.";
        if (!is_numeric($data['precio_venta']) || (float)$data['precio_venta'] < 0) return "El precio de venta debe ser ≥ 0.";

        $tipo = $this->obtenerTipoPorId($data['tipo_id']);
        if (!$tipo) return "El tipo de producto no existe.";
        if ($tipo['requiere_presentacion'] && empty(trim($data['presentacion'] ?? ''))) {
            return "La presentación es obligatoria para el tipo '{$tipo['nombre']}'.";
        }
        return true;
    }

    // ── CRUD de productos ─────────────────────────────────────────────────

    public function obtener() {
        return $this->conn->query("
            SELECT p.*, t.nombre AS tipo_nombre, t.slug AS tipo_slug,
                   t.unidad_venta, t.requiere_presentacion
            FROM productos p JOIN tipos_producto t ON p.tipo_id = t.id
            ORDER BY t.nombre ASC, p.nombre ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerActivos() {
        return $this->conn->query("
            SELECT p.*, t.nombre AS tipo_nombre, t.slug AS tipo_slug,
                   t.unidad_venta, t.requiere_presentacion
            FROM productos p JOIN tipos_producto t ON p.tipo_id = t.id
            WHERE p.activo = 1
            ORDER BY t.nombre ASC, p.nombre ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Filtra por slug del tipo. Ej: obtenerPorTipo('cacao_grano') */
    public function obtenerPorTipo($slug) {
        $stmt = $this->conn->prepare("
            SELECT p.*, t.nombre AS tipo_nombre, t.slug AS tipo_slug,
                   t.unidad_venta, t.requiere_presentacion
            FROM productos p JOIN tipos_producto t ON p.tipo_id = t.id
            WHERE t.slug = ? AND p.activo = 1
            ORDER BY p.nombre ASC
        ");
        $stmt->execute([$slug]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("
            SELECT p.*, t.nombre AS tipo_nombre, t.slug AS tipo_slug,
                   t.unidad_venta, t.requiere_presentacion
            FROM productos p JOIN tipos_producto t ON p.tipo_id = t.id
            WHERE p.id = ?
        ");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($data) {
        $data = array_map('trim', $data);
        $val  = $this->validarCampos($data);
        if ($val !== true) return $val;

        $tipo   = $this->obtenerTipoPorId($data['tipo_id']);
        $unidad = $tipo['unidad'];

        // Validar stock inicial según unidad
        $stock_inicial = (float)($data['stock_inicial'] ?? 0);
        if ($stock_inicial < 0) return "El stock inicial no puede ser negativo.";
        if ($unidad === 'und' && floor($stock_inicial) != $stock_inicial) {
            return "El stock inicial debe ser un número entero para productos por unidad.";
        }

        $this->conn->prepare("
            INSERT INTO productos (nombre, tipo_id, presentacion, unidad, stock_actual, stock_minimo, precio_venta)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $data['nombre'],
            (int)$data['tipo_id'],
            ($tipo['requiere_presentacion'] && !empty($data['presentacion'])) ? $data['presentacion'] : null,
            $unidad,
            $stock_inicial,
            (float)$data['stock_minimo'],
            (float)$data['precio_venta'],
        ]);

        $nuevo_id = (int)$this->conn->lastInsertId();

        // Registrar movimiento inicial si hay stock
        if ($stock_inicial > 0) {
            $usuario_id = $_SESSION['user']['id'] ?? 1;
            $this->registrarMovimiento(
                $nuevo_id, 'ajuste_inicial', $stock_inicial,
                0, $stock_inicial, 'inicial', null, $usuario_id
            );
        }

        return true;
    }

    public function actualizar($id, $data) {
        $data = array_map('trim', $data);
        $val  = $this->validarCampos($data);
        if ($val !== true) return $val;

        $tipo = $this->obtenerTipoPorId($data['tipo_id']);
        $this->conn->prepare("
            UPDATE productos
            SET nombre=?, tipo_id=?, presentacion=?, unidad=?,
                stock_minimo=?, precio_venta=?, activo=?
            WHERE id=?
        ")->execute([
            $data['nombre'], (int)$data['tipo_id'],
            ($tipo['requiere_presentacion'] && !empty($data['presentacion'])) ? $data['presentacion'] : null,
            $tipo['unidad'],
            (float)$data['stock_minimo'], (float)$data['precio_venta'],
            $data['activo'] ? 1 : 0,
            (int)$id,
        ]);
        return true;
    }

    // ── Control de inventario ─────────────────────────────────────────────

    public function ajusteInicial($id, $cantidad, $usuario_id) {
        if (!is_numeric($cantidad) || (float)$cantidad < 0) return "La cantidad debe ser ≥ 0.";
        $producto = $this->obtenerPorId($id);
        if (!$producto) return "Producto no encontrado.";

        $stock_antes   = (float)$producto['stock_actual'];
        $stock_despues = (float)$cantidad;

        $this->conn->prepare("UPDATE productos SET stock_actual = ? WHERE id = ?")
                   ->execute([$stock_despues, (int)$id]);
        $this->registrarMovimiento($id, 'ajuste_inicial', abs($stock_despues - $stock_antes),
            $stock_antes, $stock_despues, 'inicial', null, $usuario_id);
        return true;
    }

    public function incrementarStock($id, $cantidad, $unidad, $compra_id, $usuario_id) {
        $producto = $this->obtenerPorId($id);
        if (!$producto) return false;

        $stock_antes   = (float)$producto['stock_actual'];
        $stock_despues = $stock_antes + (float)$cantidad;

        $this->conn->prepare("UPDATE productos SET stock_actual = ? WHERE id = ?")
                   ->execute([$stock_despues, (int)$id]);
        $this->registrarMovimiento($id, 'entrada', (float)$cantidad,
            $stock_antes, $stock_despues, 'compra', $compra_id, $usuario_id);
        return true;
    }

    public function decrementarStock($id, $cantidad, $venta_id, $usuario_id) {
        $producto = $this->obtenerPorId($id);
        if (!$producto) return "Producto no encontrado.";

        $stock_antes = (float)$producto['stock_actual'];
        if ($stock_antes < (float)$cantidad) {
            return "Stock insuficiente. Disponible: {$stock_antes} {$producto['unidad']}. "
                 . "Solicitado: {$cantidad} {$producto['unidad']}.";
        }

        $stock_despues = $stock_antes - (float)$cantidad;
        $this->conn->prepare("UPDATE productos SET stock_actual = ? WHERE id = ?")
                   ->execute([$stock_despues, (int)$id]);
        $this->registrarMovimiento($id, 'salida', (float)$cantidad,
            $stock_antes, $stock_despues, 'venta', $venta_id, $usuario_id);
        return true;
    }

    private function registrarMovimiento($producto_id, $tipo, $cantidad,
                                          $stock_antes, $stock_despues,
                                          $ref_tipo, $ref_id, $usuario_id) {
        $this->conn->prepare("
            INSERT INTO movimientos_inventario
                (producto_id, tipo, cantidad, stock_antes, stock_despues,
                 referencia_tipo, referencia_id, usuario_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([(int)$producto_id, $tipo, $cantidad, $stock_antes,
                     $stock_despues, $ref_tipo, $ref_id, (int)$usuario_id]);
    }

    public function obtenerMovimientos($producto_id = null) {
        if ($producto_id) {
            $stmt = $this->conn->prepare("
                SELECT m.*, p.nombre AS producto, p.unidad, u.nombre AS usuario
                FROM movimientos_inventario m
                JOIN productos p ON m.producto_id = p.id
                JOIN usuarios  u ON m.usuario_id  = u.id
                WHERE m.producto_id = ? ORDER BY m.fecha DESC
            ");
            $stmt->execute([(int)$producto_id]);
        } else {
            $stmt = $this->conn->query("
                SELECT m.*, p.nombre AS producto, p.unidad, u.nombre AS usuario
                FROM movimientos_inventario m
                JOIN productos p ON m.producto_id = p.id
                JOIN usuarios  u ON m.usuario_id  = u.id
                ORDER BY m.fecha DESC LIMIT 100
            ");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
