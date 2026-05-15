<?php
/**
 * GestionInventarioTest – ChocoTumac
 *
 * Suite de pruebas unitarias del modelo Producto enfocada en inventario.
 * Valida las reglas de negocio de HU-06: control automático de inventario
 * de cacao y productos derivados.
 *
 * Cubre:
 *   - Validación de campos al crear y actualizar productos
 *   - Ajuste inicial de stock (carga manual)
 *   - Incremento de stock al registrar compras
 *   - Decremento de stock al registrar ventas con validación de stock mínimo
 *   - Historial de movimientos de inventario
 *   - Gestión de tipos de producto
 *
 * Patrón:  AAA (Arrange – Act – Assert)
 * Tipo:    Caja blanca con mocks para aislar las operaciones de BD
 * Runner:  PHPUnit 9
 *
 * @package ChocoTumac\Tests
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestHelper.php';

class GestionInventarioTest extends TestCase
{
    use TestHelper;

    /** @var Producto Instancia del modelo bajo prueba */
    private Producto $producto;

    protected function setUp(): void
    {
        $this->producto = new Producto();
        // FakePDO inyectado via bootstrap.php (stub de Database)
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 1 – Validación de campos del producto
    // ══════════════════════════════════════════════════════════════════

    /**
     * IN-01: El nombre del producto es obligatorio.
     *
     * @test
     */
    public function IN01_nombreVacio_retornaError(): void
    {
        // Arrange
        $data = ['nombre' => '', 'tipo_id' => '1', 'presentacion' => '',
                 'stock_minimo' => '0', 'precio_venta' => '5000'];

        // Act
        $resultado = $this->invocarPrivado($this->producto, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    /**
     * IN-02: El nombre debe tener al menos 2 caracteres.
     *
     * @test
     */
    public function IN02_nombreDeUnCaracter_retornaError(): void
    {
        // Arrange
        $data = ['nombre' => 'X', 'tipo_id' => '1', 'presentacion' => '',
                 'stock_minimo' => '0', 'precio_venta' => '5000'];

        // Act
        $resultado = $this->invocarPrivado($this->producto, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('2 caracteres', $resultado);
    }

    /**
     * IN-03: El tipo_id es obligatorio y debe ser numérico.
     *
     * @test
     */
    public function IN03_sinTipoId_retornaError(): void
    {
        // Arrange
        $data = ['nombre' => 'Cacao Fino', 'tipo_id' => '', 'presentacion' => '',
                 'stock_minimo' => '0', 'precio_venta' => '5000'];

        // Act
        $resultado = $this->invocarPrivado($this->producto, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('tipo', strtolower($resultado));
    }

    /**
     * IN-04: El stock mínimo no puede ser negativo.
     *
     * @test
     */
    public function IN04_stockMinimoNegativo_retornaError(): void
    {
        // Arrange
        $data = ['nombre' => 'Cacao Fino', 'tipo_id' => '1', 'presentacion' => '',
                 'stock_minimo' => '-1', 'precio_venta' => '5000'];

        // Act
        $resultado = $this->invocarPrivado($this->producto, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('stock', strtolower($resultado));
    }

    /**
     * IN-05: El precio de venta no puede ser negativo.
     *
     * @test
     */
    public function IN05_precioVentaNegativo_retornaError(): void
    {
        // Arrange
        $data = ['nombre' => 'Cacao Fino', 'tipo_id' => '1', 'presentacion' => '',
                 'stock_minimo' => '5', 'precio_venta' => '-100'];

        // Act
        $resultado = $this->invocarPrivado($this->producto, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('precio', strtolower($resultado));
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 2 – Creación y actualización de productos
    // ══════════════════════════════════════════════════════════════════

    /**
     * IN-06: Crear producto con tipo válido y stock cero debe retornar true.
     *
     * @test
     */
    public function IN06_crear_tipoValido_stockCero_retornaTrue(): void
    {
        // Arrange — mock que retorna un tipo válido sin requerir presentación
        $mockProducto = $this->getMockBuilder(Producto::class)
            ->onlyMethods(['obtenerTipoPorId'])
            ->getMock();
        $mockProducto->method('obtenerTipoPorId')->willReturn([
            'id' => 1, 'nombre' => 'Cacao Grano', 'unidad' => 'kg',
            'unidad_venta' => 'kg', 'requiere_presentacion' => 0,
        ]);

        $data = ['nombre' => 'Cacao Grano Fino', 'tipo_id' => '1', 'presentacion' => '',
                 'stock_minimo' => '5', 'precio_venta' => '8500', 'stock_inicial' => '0'];

        // Act
        $resultado = $mockProducto->crear($data);

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * IN-07: Si el tipo requiere presentación y no se especifica, debe retornar error.
     *
     * @test
     */
    public function IN07_crear_presentacionRequerida_vacia_retornaError(): void
    {
        // Arrange — mock que retorna tipo que requiere presentación
        $mockProducto = $this->getMockBuilder(Producto::class)
            ->onlyMethods(['obtenerTipoPorId'])
            ->getMock();
        $mockProducto->method('obtenerTipoPorId')->willReturn([
            'id' => 3, 'nombre' => 'Chocolate Mesa', 'unidad' => 'und',
            'unidad_venta' => 'und', 'requiere_presentacion' => 1,
        ]);

        $data = ['nombre' => 'Barra Oscura', 'tipo_id' => '3', 'presentacion' => '',
                 'stock_minimo' => '0', 'precio_venta' => '5000', 'stock_inicial' => '0'];

        // Act
        $resultado = $mockProducto->crear($data);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('presentaci', strtolower($resultado));
    }

    /**
     * IN-08: Para productos en unidades (und), el stock inicial no puede ser fraccionario.
     *
     * @test
     */
    public function IN08_crear_stockInicialFraccionario_enProductoUnd_retornaError(): void
    {
        // Arrange
        $mockProducto = $this->getMockBuilder(Producto::class)
            ->onlyMethods(['obtenerTipoPorId'])
            ->getMock();
        $mockProducto->method('obtenerTipoPorId')->willReturn([
            'id' => 2, 'nombre' => 'Tabletas', 'unidad' => 'und',
            'unidad_venta' => 'und', 'requiere_presentacion' => 0,
        ]);

        $data = ['nombre' => 'Tableta Cacao', 'tipo_id' => '2', 'presentacion' => '',
                 'stock_minimo' => '0', 'precio_venta' => '3000', 'stock_inicial' => '5.5'];

        // Act
        $resultado = $mockProducto->crear($data);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('entero', strtolower($resultado));
    }

    /**
     * IN-09: Actualizar producto sin nombre debe retornar error.
     *
     * @test
     */
    public function IN09_actualizar_sinNombre_retornaError(): void
    {
        // Arrange
        $data = ['nombre' => '', 'tipo_id' => '1', 'presentacion' => '',
                 'stock_minimo' => '5', 'precio_venta' => '9000', 'activo' => 1];

        // Act
        $resultado = $this->producto->actualizar(1, $data);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 3 – Ajuste manual de stock
    // ══════════════════════════════════════════════════════════════════

    /**
     * IN-10: El ajuste de stock no acepta cantidades no numéricas.
     *
     * @test
     */
    public function IN10_ajusteInicial_cantidadNoNumerica_retornaError(): void
    {
        // Act
        $resultado = $this->producto->ajusteInicial(1, 'mucho', 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('cantidad', strtolower($resultado));
    }

    /**
     * IN-11: El ajuste de stock no acepta cantidades negativas.
     *
     * @test
     */
    public function IN11_ajusteInicial_cantidadNegativa_retornaError(): void
    {
        // Act
        $resultado = $this->producto->ajusteInicial(1, -10, 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('cantidad', strtolower($resultado));
    }

    /**
     * IN-12: El ajuste de stock con producto inexistente debe retornar error.
     * Con FakePDO, obtenerPorId retorna false → producto no encontrado.
     *
     * @test
     */
    public function IN12_ajusteInicial_productoInexistente_retornaError(): void
    {
        // Act
        $resultado = $this->producto->ajusteInicial(9999, 50, 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('no encontrado', strtolower($resultado));
    }

    /**
     * IN-13: El ajuste de stock con producto existente debe retornar true.
     *
     * @test
     */
    public function IN13_ajusteInicial_productoExistente_retornaTrue(): void
    {
        // Arrange — mock que retorna producto con stock actual
        $mockProducto = $this->getMockBuilder(Producto::class)
            ->onlyMethods(['obtenerPorId'])
            ->getMock();
        $mockProducto->method('obtenerPorId')->willReturn([
            'id' => 1, 'nombre' => 'Cacao', 'unidad' => 'kg', 'stock_actual' => 30.0,
        ]);

        // Act
        $resultado = $mockProducto->ajusteInicial(1, 50, 1);

        // Assert
        $this->assertTrue($resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 4 – Incremento y decremento de stock
    // ══════════════════════════════════════════════════════════════════

    /**
     * IN-14: Incrementar stock con producto inexistente debe retornar false.
     *
     * @test
     */
    public function IN14_incrementarStock_productoInexistente_retornaFalse(): void
    {
        // Act — FakePDO retorna false → producto no encontrado
        $resultado = $this->producto->incrementarStock(9999, 10, 'kg', 1, 1);

        // Assert
        $this->assertFalse($resultado);
    }

    /**
     * IN-15: Incrementar stock con producto existente debe retornar true.
     *
     * @test
     */
    public function IN15_incrementarStock_productoExistente_retornaTrue(): void
    {
        // Arrange
        $mockProducto = $this->getMockBuilder(Producto::class)
            ->onlyMethods(['obtenerPorId'])
            ->getMock();
        $mockProducto->method('obtenerPorId')->willReturn([
            'id' => 1, 'nombre' => 'Cacao', 'unidad' => 'kg', 'stock_actual' => 20.0,
        ]);

        // Act
        $resultado = $mockProducto->incrementarStock(1, 30, 'kg', 1, 1);

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * IN-16: Decrementar stock con producto inexistente debe retornar mensaje de error.
     *
     * @test
     */
    public function IN16_decrementarStock_productoInexistente_retornaError(): void
    {
        // Act
        $resultado = $this->producto->decrementarStock(9999, 5, 1, 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('no encontrado', strtolower($resultado));
    }

    /**
     * IN-17: Decrementar stock cuando el stock actual es insuficiente
     * debe retornar un mensaje de error con los valores exactos disponibles.
     *
     * @test
     */
    public function IN17_decrementarStock_stockInsuficiente_retornaError(): void
    {
        // Arrange — stock: 3 kg, se pide: 10 kg
        $mockProducto = $this->getMockBuilder(Producto::class)
            ->onlyMethods(['obtenerPorId'])
            ->getMock();
        $mockProducto->method('obtenerPorId')->willReturn([
            'id' => 1, 'nombre' => 'Cacao', 'unidad' => 'kg', 'stock_actual' => 3.0,
        ]);

        // Act
        $resultado = $mockProducto->decrementarStock(1, 10, 1, 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('insuficiente', strtolower($resultado));
    }

    /**
     * IN-18: Decrementar stock cuando hay stock suficiente debe retornar true.
     *
     * @test
     */
    public function IN18_decrementarStock_stockSuficiente_retornaTrue(): void
    {
        // Arrange — stock: 100 kg, se pide: 10 kg
        $mockProducto = $this->getMockBuilder(Producto::class)
            ->onlyMethods(['obtenerPorId'])
            ->getMock();
        $mockProducto->method('obtenerPorId')->willReturn([
            'id' => 1, 'nombre' => 'Cacao', 'unidad' => 'kg', 'stock_actual' => 100.0,
        ]);

        // Act
        $resultado = $mockProducto->decrementarStock(1, 10, 1, 1);

        // Assert
        $this->assertTrue($resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 5 – Historial de movimientos y consultas
    // ══════════════════════════════════════════════════════════════════

    /**
     * IN-19: Obtener movimientos sin filtro debe retornar array con FakePDO.
     *
     * @test
     */
    public function IN19_obtenerMovimientos_sinFiltro_retornaArray(): void
    {
        // Act
        $resultado = $this->producto->obtenerMovimientos();

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * IN-20: Obtener movimientos filtrados por producto debe retornar array.
     *
     * @test
     */
    public function IN20_obtenerMovimientos_conProductoId_retornaArray(): void
    {
        // Act
        $resultado = $this->producto->obtenerMovimientos(1);

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * IN-21: Obtener todos los productos debe retornar array con FakePDO.
     *
     * @test
     */
    public function IN21_obtener_retornaArray(): void
    {
        // Act
        $resultado = $this->producto->obtener();

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * IN-22: Obtener productos activos debe retornar array con FakePDO.
     *
     * @test
     */
    public function IN22_obtenerActivos_retornaArray(): void
    {
        // Act
        $resultado = $this->producto->obtenerActivos();

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * IN-23: Buscar producto por ID inexistente retorna false con FakePDO.
     *
     * @test
     */
    public function IN23_obtenerPorId_idInexistente_retornaFalse(): void
    {
        // Act
        $resultado = $this->producto->obtenerPorId(9999);

        // Assert
        $this->assertFalse($resultado);
    }

    /**
     * IN-24: Los métodos de control de inventario deben existir y ser accesibles.
     *
     * @test
     */
    public function IN24_metodosDeInventarioExisten(): void
    {
        // Assert
        $this->assertTrue(method_exists($this->producto, 'incrementarStock'));
        $this->assertTrue(method_exists($this->producto, 'decrementarStock'));
        $this->assertTrue(method_exists($this->producto, 'ajusteInicial'));
        $this->assertTrue(method_exists($this->producto, 'obtenerMovimientos'));
    }
}
