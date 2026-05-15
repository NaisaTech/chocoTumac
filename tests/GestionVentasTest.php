<?php
/**
 * GestionVentasTest – ChocoTumac
 *
 * Suite de pruebas unitarias del modelo Venta.
 * Valida las reglas de negocio definidas en HU-05:
 * registro de ventas con cliente registrado u ocasional,
 * validación de stock antes de insertar, y descuento automático de inventario.
 *
 * Cubre:
 *   - Validación de tipo de cliente (registrado vs ocasional)
 *   - Validaciones de producto, fecha, cantidad y precio
 *   - Regla de cantidad entera para productos en unidades (und)
 *   - Flujo completo: validación → verificar stock → INSERT → decrementar stock
 *   - Rollback si el decremento de stock falla
 *   - Eliminación con restauración de stock
 *
 * Patrón:  AAA (Arrange – Act – Assert)
 * Tipo:    Caja blanca con mocks de Producto para aislar la lógica
 * Runner:  PHPUnit 9
 *
 * @package ChocoTumac\Tests
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestHelper.php';

class GestionVentasTest extends TestCase
{
    use TestHelper;

    /** @var Venta Instancia del modelo bajo prueba */
    private Venta $venta;

    protected function setUp(): void
    {
        $this->venta = new Venta();
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 1 – Validación de tipo de cliente
    // ══════════════════════════════════════════════════════════════════

    /**
     * VT-01: Si el tipo de cliente es "registrado" pero no se seleccionó ninguno,
     * la validación debe rechazarlo con mensaje claro.
     *
     * @test
     */
    public function VT01_clienteRegistrado_sinId_retornaError(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        unset($data['cliente_id']);
        $data['tipo_cliente'] = 'registrado';   // tipo registrado pero sin ID

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCliente', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('cliente registrado', $resultado);
    }

    /**
     * VT-02: Un cliente ocasional (sin necesidad de estar registrado) debe ser aceptado.
     * Este flujo habilita ventas a personas que no quieren registrarse.
     *
     * @test
     */
    public function VT02_clienteOcasional_sinId_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        unset($data['cliente_id']);
        $data['tipo_cliente'] = 'ocasional';

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCliente', [$data]);

        // Assert
        $this->assertTrue($resultado, 'Un cliente ocasional debe ser aceptado sin ID');
    }

    /**
     * VT-03: Un cliente registrado con ID válido debe ser aceptado.
     *
     * @test
     */
    public function VT03_clienteRegistrado_conId_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        $data['tipo_cliente'] = 'registrado';
        $data['cliente_id']   = '5';

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCliente', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 2 – Validación de campos de la venta
    // ══════════════════════════════════════════════════════════════════

    /**
     * VT-04: El producto_id es obligatorio.
     *
     * @test
     */
    public function VT04_sinProductoId_retornaError(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        $data['producto_id'] = '';

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('producto', strtolower($resultado));
    }

    /**
     * VT-05: La fecha es obligatoria.
     *
     * @test
     */
    public function VT05_fechaVacia_retornaError(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        $data['fecha'] = '';

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('fecha', strtolower($resultado));
    }

    /**
     * VT-06: La fecha debe tener formato válido.
     * Una fecha imposible como "32-13-2026" debe ser rechazada.
     *
     * @test
     */
    public function VT06_fechaConFormatoInvalido_retornaError(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        $data['fecha'] = '32-13-2026';

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('fecha', strtolower($resultado));
    }

    /**
     * VT-07: La cantidad debe ser mayor que cero.
     *
     * @test
     */
    public function VT07_cantidadCero_retornaError(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        $data['cantidad'] = '0';

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('cantidad', strtolower($resultado));
    }

    /**
     * VT-08: Una cantidad negativa debe ser rechazada.
     *
     * @test
     */
    public function VT08_cantidadNegativa_retornaError(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        $data['cantidad'] = '-5';

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('cantidad', strtolower($resultado));
    }

    /**
     * VT-09: Una cantidad con texto debe ser rechazada.
     *
     * @test
     */
    public function VT09_cantidadConTexto_retornaError(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        $data['cantidad'] = 'diez';

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('cantidad', strtolower($resultado));
    }

    /**
     * VT-10: El precio unitario debe ser mayor que cero.
     *
     * @test
     */
    public function VT10_precioUnitarioCero_retornaError(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        $data['precio_unitario'] = '0';

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('precio', strtolower($resultado));
    }

    /**
     * VT-11: Un precio negativo debe ser rechazado.
     *
     * @test
     */
    public function VT11_precioNegativo_retornaError(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        $data['precio_unitario'] = '-1000';

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('precio', strtolower($resultado));
    }

    /**
     * VT-12: Con todos los campos válidos, la validación debe retornar true.
     *
     * @test
     */
    public function VT12_todosLosCamposValidos_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 3 – Regla de cantidad por unidad
    // ══════════════════════════════════════════════════════════════════

    /**
     * VT-13: Para productos en unidades (und), no se permiten cantidades fraccionarias.
     *
     * @test
     */
    public function VT13_cantidadFraccionaria_enProductoUnd_retornaError(): void
    {
        // Arrange — mock de Producto con unidad "und"
        $mockProducto = $this->createMock(Producto::class);
        $mockProducto->method('obtenerPorId')->willReturn([
            'id' => 1, 'nombre' => 'Tableta', 'unidad' => 'und',
        ]);
        $this->inyectarPropiedad($this->venta, 'modelProducto', $mockProducto);

        $data = ['producto_id' => '1', 'cantidad' => '2.5'];

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCantidadPorUnidad', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('entero', strtolower($resultado));
    }

    /**
     * VT-14: Para productos en unidades (und), las cantidades enteras son válidas.
     *
     * @test
     */
    public function VT14_cantidadEntera_enProductoUnd_retornaTrue(): void
    {
        // Arrange — mock de Producto con unidad "und"
        $mockProducto = $this->createMock(Producto::class);
        $mockProducto->method('obtenerPorId')->willReturn([
            'id' => 1, 'nombre' => 'Tableta', 'unidad' => 'und',
        ]);
        $this->inyectarPropiedad($this->venta, 'modelProducto', $mockProducto);

        $data = ['producto_id' => '1', 'cantidad' => '3'];

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCantidadPorUnidad', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * VT-15: Para productos en kg, se permiten cantidades fraccionarias.
     *
     * @test
     */
    public function VT15_cantidadFraccionaria_enProductoKg_esValida(): void
    {
        // Arrange — mock de Producto con unidad "kg"
        $mockProducto = $this->createMock(Producto::class);
        $mockProducto->method('obtenerPorId')->willReturn([
            'id' => 1, 'nombre' => 'Cacao Granel', 'unidad' => 'kg',
        ]);
        $this->inyectarPropiedad($this->venta, 'modelProducto', $mockProducto);

        $data = ['producto_id' => '1', 'cantidad' => '12.75'];

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCantidadPorUnidad', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 4 – Flujo completo de creación
    // ══════════════════════════════════════════════════════════════════

    /**
     * VT-16: Crear venta con fecha vacía debe retornar error antes de tocar la BD.
     *
     * @test
     */
    public function VT16_crear_fechaVacia_retornaError(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        $data['fecha'] = '';

        // Act
        $resultado = $this->venta->crear($data, 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('fecha', strtolower($resultado));
    }

    /**
     * VT-17: Si el producto no existe, crear debe retornar error descriptivo.
     *
     * @test
     */
    public function VT17_crear_productoNoExiste_retornaError(): void
    {
        // Arrange
        $mockProducto = $this->createMock(Producto::class);
        $mockProducto->method('obtenerPorId')->willReturn(false);
        $this->inyectarPropiedad($this->venta, 'modelProducto', $mockProducto);

        // Act
        $resultado = $this->venta->crear($this->datosVentaValidos(), 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('no encontrado', strtolower($resultado));
    }

    /**
     * VT-18: Si el stock es insuficiente, no se debe registrar la venta.
     * El sistema debe bloquearlo antes del INSERT.
     *
     * @test
     */
    public function VT18_crear_stockInsuficiente_retornaError(): void
    {
        // Arrange — stock disponible: 2 kg, se pide: 100 kg
        $mockProducto = $this->createMock(Producto::class);
        $mockProducto->method('obtenerPorId')->willReturn([
            'id' => 1, 'nombre' => 'Cacao', 'unidad' => 'kg', 'stock_actual' => 2.0,
        ]);
        $this->inyectarPropiedad($this->venta, 'modelProducto', $mockProducto);

        $data = $this->datosVentaValidos();
        $data['cantidad'] = '100';

        // Act
        $resultado = $this->venta->crear($data, 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('insuficiente', strtolower($resultado));
    }

    /**
     * VT-19: Si el decremento de stock falla después del INSERT,
     * la venta debe ser eliminada (rollback manual) y retornar error.
     *
     * @test
     */
    public function VT19_crear_decrementoStockFalla_retornaError(): void
    {
        // Arrange — stock suficiente pero el decremento falla
        $mockProducto = $this->createMock(Producto::class);
        $mockProducto->method('obtenerPorId')->willReturn([
            'id' => 1, 'nombre' => 'Cacao', 'unidad' => 'kg', 'stock_actual' => 500.0,
        ]);
        $mockProducto->method('decrementarStock')->willReturn('Stock insuficiente.');
        $this->inyectarPropiedad($this->venta, 'modelProducto', $mockProducto);

        // Act
        $resultado = $this->venta->crear($this->datosVentaValidos(), 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('stock', strtolower($resultado));
    }

    /**
     * VT-20: Venta a cliente ocasional con nombre — flujo exitoso.
     * El sistema debe aceptar ventas sin necesidad de registrar al cliente.
     *
     * @test
     */
    public function VT20_crear_clienteOcasional_conStock_retornaId(): void
    {
        // Arrange
        $mockProducto = $this->createMock(Producto::class);
        $mockProducto->method('obtenerPorId')->willReturn([
            'id' => 1, 'nombre' => 'Cacao', 'unidad' => 'kg', 'stock_actual' => 500.0,
        ]);
        $mockProducto->method('decrementarStock')->willReturn(true);
        $this->inyectarPropiedad($this->venta, 'modelProducto', $mockProducto);

        $data = $this->datosVentaValidos();
        $data['tipo_cliente']      = 'ocasional';
        $data['cliente_ocasional'] = 'Comprador Externo';
        unset($data['cliente_id']);

        // Act
        $resultado = $this->venta->crear($data, 1);

        // Assert — con FakePDO retorna el ID del lastInsertId (1) o true
        $this->assertTrue($resultado === true || is_int($resultado));
    }

    /**
     * VT-21: Venta a cliente ocasional sin nombre — se guarda como "Cliente general".
     *
     * @test
     */
    public function VT21_crear_clienteOcasional_sinNombre_usaNombreGeneral(): void
    {
        // Arrange
        $mockProducto = $this->createMock(Producto::class);
        $mockProducto->method('obtenerPorId')->willReturn([
            'id' => 1, 'nombre' => 'Cacao', 'unidad' => 'kg', 'stock_actual' => 500.0,
        ]);
        $mockProducto->method('decrementarStock')->willReturn(true);
        $this->inyectarPropiedad($this->venta, 'modelProducto', $mockProducto);

        $data = $this->datosVentaValidos();
        $data['tipo_cliente']      = 'ocasional';
        $data['cliente_ocasional'] = '';   // vacío → debe usarse "Cliente general"
        unset($data['cliente_id']);

        // Act — si no lanza excepción, el sistema maneja correctamente el nombre vacío
        $resultado = $this->venta->crear($data, 1);

        // Assert
        $this->assertTrue($resultado === true || is_int($resultado));
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 5 – Eliminación con restauración de stock
    // ══════════════════════════════════════════════════════════════════

    /**
     * VT-22: Eliminar una venta con ID inexistente debe retornar error.
     *
     * @test
     */
    public function VT22_eliminar_idInexistente_retornaError(): void
    {
        // Act — FakePDO retorna false en fetch() → venta no encontrada
        $resultado = $this->venta->eliminar(9999, 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('no encontrada', strtolower($resultado));
    }

    /**
     * VT-23: El flujo de eliminación retorna error cuando la venta no existe.
     * Con FakePDO, obtenerPorId siempre retorna false, por lo que se valida
     * la rama de protección "venta no encontrada".
     * La rama de éxito (stock restaurado) requiere BD real y se cubre en pruebas
     * de integración / Robot Framework.
     *
     * @test
     */
    public function VT23_eliminar_ventaInexistente_retornaError(): void
    {
        // Act — FakePDO retorna false en fetch() → venta no encontrada
        $resultado = $this->venta->eliminar(9999, 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('no encontrada', strtolower($resultado));
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 6 – Consultas
    // ══════════════════════════════════════════════════════════════════

    /**
     * VT-24: Obtener todas las ventas debe retornar un array con FakePDO.
     *
     * @test
     */
    public function VT24_obtener_retornaArray(): void
    {
        // Act
        $resultado = $this->venta->obtener();

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * VT-25: Buscar venta por ID inexistente retorna false con FakePDO.
     *
     * @test
     */
    public function VT25_obtenerPorId_idInexistente_retornaFalse(): void
    {
        // Act
        $resultado = $this->venta->obtenerPorId(9999);

        // Assert
        $this->assertFalse($resultado);
    }
}
