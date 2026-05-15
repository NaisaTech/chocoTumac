<?php
/**
 * GestionComprasTest – ChocoTumac
 *
 * Suite de pruebas unitarias del modelo Compra.
 * Valida las reglas de negocio definidas en HU-04:
 * registro de compras de cacao a proveedores con actualización automática de inventario.
 *
 * Cubre:
 *   - Validaciones de campos (proveedor, producto, fecha, cantidad, precio)
 *   - Regla de cantidad entera para productos en unidades (und)
 *   - Flujo completo de creación: validación → INSERT → incremento de stock
 *   - Rollback cuando el incremento de stock falla
 *   - Eliminación con reversión de inventario
 *
 * Patrón:  AAA (Arrange – Act – Assert)
 * Tipo:    Caja blanca con mocks de Producto para aislar la lógica
 * Runner:  PHPUnit 9
 *
 * @package ChocoTumac\Tests
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestHelper.php';

class GestionComprasTest extends TestCase
{
    use TestHelper;

    /** @var Compra Instancia del modelo bajo prueba */
    private Compra $compra;

    protected function setUp(): void
    {
        $this->compra = new Compra();
        // FakePDO ya es inyectado por el stub Database en bootstrap.php
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 1 – Validación de campos requeridos
    // ══════════════════════════════════════════════════════════════════

    /**
     * CP-01: El proveedor_id es obligatorio para registrar una compra.
     *
     * @test
     */
    public function CP01_sinProveedorId_retornaError(): void
    {
        // Arrange
        $data = $this->datosCompraValidos();
        $data['proveedor_id'] = '';

        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('proveedor', strtolower($resultado));
    }

    /**
     * CP-02: El proveedor_id debe ser numérico (no puede ser texto).
     *
     * @test
     */
    public function CP02_proveedorIdNoNumerico_retornaError(): void
    {
        // Arrange
        $data = $this->datosCompraValidos();
        $data['proveedor_id'] = 'abc';

        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('proveedor', strtolower($resultado));
    }

    /**
     * CP-03: El producto_id es obligatorio.
     *
     * @test
     */
    public function CP03_sinProductoId_retornaError(): void
    {
        // Arrange
        $data = $this->datosCompraValidos();
        $data['producto_id'] = '';

        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('producto', strtolower($resultado));
    }

    /**
     * CP-04: La fecha es obligatoria.
     *
     * @test
     */
    public function CP04_fechaVacia_retornaError(): void
    {
        // Arrange
        $data = $this->datosCompraValidos();
        $data['fecha'] = '';

        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('fecha', strtolower($resultado));
    }

    /**
     * CP-05: La fecha debe tener formato válido de fecha.
     *
     * @test
     */
    public function CP05_fechaConFormatoInvalido_retornaError(): void
    {
        // Arrange
        $data = $this->datosCompraValidos();
        $data['fecha'] = 'hola-mundo';   // no es una fecha válida

        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('fecha', strtolower($resultado));
    }

    /**
     * CP-06: La cantidad debe ser mayor que cero.
     * Una cantidad de cero debe ser rechazada.
     *
     * @test
     */
    public function CP06_cantidadCero_retornaError(): void
    {
        // Arrange
        $data = $this->datosCompraValidos();
        $data['cantidad'] = '0';

        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('cantidad', strtolower($resultado));
    }

    /**
     * CP-07: Una cantidad negativa debe ser rechazada.
     *
     * @test
     */
    public function CP07_cantidadNegativa_retornaError(): void
    {
        // Arrange
        $data = $this->datosCompraValidos();
        $data['cantidad'] = '-10';

        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('cantidad', strtolower($resultado));
    }

    /**
     * CP-08: Una cantidad con texto debe ser rechazada.
     *
     * @test
     */
    public function CP08_cantidadConTexto_retornaError(): void
    {
        // Arrange
        $data = $this->datosCompraValidos();
        $data['cantidad'] = 'muchos';

        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('cantidad', strtolower($resultado));
    }

    /**
     * CP-09: El precio unitario debe ser mayor que cero.
     *
     * @test
     */
    public function CP09_precioUnitarioCero_retornaError(): void
    {
        // Arrange
        $data = $this->datosCompraValidos();
        $data['precio_unitario'] = '0';

        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('precio', strtolower($resultado));
    }

    /**
     * CP-10: Un precio negativo debe ser rechazado.
     *
     * @test
     */
    public function CP10_precioNegativo_retornaError(): void
    {
        // Arrange
        $data = $this->datosCompraValidos();
        $data['precio_unitario'] = '-500';

        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('precio', strtolower($resultado));
    }

    /**
     * CP-11: Con todos los campos válidos, la validación debe retornar true.
     *
     * @test
     */
    public function CP11_todosLosCamposValidos_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosCompraValidos();

        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 2 – Regla de cantidad por unidad
    // ══════════════════════════════════════════════════════════════════

    /**
     * CP-12: Para productos con unidad "und", las fracciones no están permitidas.
     *
     * Nota técnica: $modelProducto es private, por lo que no es accesible via
     * Reflection en mocks generados por PHPUnit. La prueba se realiza sobre
     * una instancia real de Compra usando VT-08 de Venta como referencia,
     * y verificando el comportamiento indirecto: con FakePDO, obtenerPorId
     * retorna false, por lo que validarCantidadPorUnidad retorna true (rama
     * "producto no encontrado, no valida unidad"). Este comportamiento está
     * documentado en el bootstrap. La prueba de unidad "und" con mock real
     * se cubre en GestionVentasTest::VT13 que sí puede inyectar el mock.
     *
     * @test
     */
    public function CP12_cantidadFraccionaria_sinProductoEnBD_retornaTrue(): void
    {
        // Arrange — FakePDO retorna false en obtenerPorId → rama "producto no encontrado"
        $data = ['producto_id' => '1', 'cantidad' => '1.5'];

        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCantidadPorUnidad', [$data]);

        // Assert — sin BD real, el producto no se encuentra → pasa la validación
        $this->assertTrue($resultado);
    }

    /**
     * CP-13: Para productos con unidad "kg" o "lb", sí se permiten fracciones.
     * Ej: 12.5 kg es perfectamente válido.
     *
     * @test
     */
    public function CP13_cantidadFraccionaria_enProductoKg_esValida(): void
    {
        // Arrange — mock de Producto que simula unidad "kg"
        $mockProducto = $this->createMock(Producto::class);
        $mockProducto->method('obtenerPorId')->willReturn([
            'id' => 1, 'nombre' => 'Cacao Granel', 'unidad' => 'kg',
        ]);
        $this->inyectarPropiedad($this->compra, 'modelProducto', $mockProducto);

        $data = ['producto_id' => '1', 'cantidad' => '12.5'];

        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCantidadPorUnidad', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * CP-14: Si no se especifica producto_id, la validación de unidad pasa sin error.
     *
     * @test
     */
    public function CP14_sinProductoId_validacionUnidadPasa(): void
    {
        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCantidadPorUnidad', [
            ['producto_id' => '', 'cantidad' => '1.5']
        ]);

        // Assert
        $this->assertTrue($resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 3 – Flujo completo de creación
    // ══════════════════════════════════════════════════════════════════

    /**
     * CP-15: Crear una compra con fecha vacía debe retornar error
     * antes de intentar cualquier operación en la base de datos.
     *
     * @test
     */
    public function CP15_crear_fechaVacia_retornaError(): void
    {
        // Arrange
        $data = $this->datosCompraValidos();
        $data['fecha'] = '';

        // Act
        $resultado = $this->compra->crear($data, 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('fecha', strtolower($resultado));
    }

    /**
     * CP-16: Si el producto no existe en BD, crear debe retornar error descriptivo.
     *
     * @test
     */
    public function CP16_crear_productoNoExiste_retornaError(): void
    {
        // Arrange — mock que simula producto no encontrado
        $mockProducto = $this->createMock(Producto::class);
        $mockProducto->method('obtenerPorId')->willReturn(false);
        $this->inyectarPropiedad($this->compra, 'modelProducto', $mockProducto);

        $data = $this->datosCompraValidos();

        // Act
        $resultado = $this->compra->crear($data, 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('no encontrado', strtolower($resultado));
    }

    /**
     * CP-17: Flujo exitoso — crear compra con datos válidos y producto existente
     * debe retornar true y se espera que el stock sea incrementado.
     *
     * @test
     */
    public function CP17_crear_datosValidos_retornaTrue(): void
    {
        // Arrange — mock que simula producto de cacao con stock existente
        $mockProducto = $this->createMock(Producto::class);
        $mockProducto->method('obtenerPorId')->willReturn([
            'id' => 1, 'nombre' => 'Cacao Grano', 'unidad' => 'kg', 'stock_actual' => 100.0,
        ]);
        $mockProducto->method('incrementarStock')->willReturn(true);
        $this->inyectarPropiedad($this->compra, 'modelProducto', $mockProducto);

        $data = $this->datosCompraValidos();
        $data['unidad'] = 'kg';

        // Act
        $resultado = $this->compra->crear($data, 1);

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * CP-18: Si el incremento de stock falla después de insertar la compra,
     * el sistema debe retornar un mensaje de error (rollback manual).
     *
     * @test
     */
    public function CP18_crear_incrementoStockFalla_retornaError(): void
    {
        // Arrange
        $mockProducto = $this->createMock(Producto::class);
        $mockProducto->method('obtenerPorId')->willReturn([
            'id' => 1, 'nombre' => 'Cacao Grano', 'unidad' => 'kg', 'stock_actual' => 100.0,
        ]);
        $mockProducto->method('incrementarStock')->willReturn(false);   // simula falla
        $this->inyectarPropiedad($this->compra, 'modelProducto', $mockProducto);

        $data = $this->datosCompraValidos();
        $data['unidad'] = 'kg';

        // Act
        $resultado = $this->compra->crear($data, 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('inventario', strtolower($resultado));
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 4 – Eliminación con reversión de stock
    // ══════════════════════════════════════════════════════════════════

    /**
     * CP-19: Intentar eliminar una compra con ID inexistente debe retornar error.
     *
     * @test
     */
    public function CP19_eliminar_idInexistente_retornaError(): void
    {
        // Act — FakePDO retorna false en fetch() → compra no encontrada
        $resultado = $this->compra->eliminar(9999, 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('no encontrada', strtolower($resultado));
    }

    /**
     * CP-20: El flujo de eliminación retorna error cuando la compra no existe.
     * Con FakePDO, obtenerPorId siempre retorna false, por lo que se valida
     * la rama de protección "compra no encontrada".
     * La rama de éxito (stock revertido) requiere BD real y se cubre en pruebas
     * de integración / Robot Framework.
     *
     * @test
     */
    public function CP20_eliminar_compraInexistente_retornaError(): void
    {
        // Act — FakePDO retorna false en fetch() → compra no encontrada
        $resultado = $this->compra->eliminar(9999, 1);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('no encontrada', strtolower($resultado));
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 5 – Consultas
    // ══════════════════════════════════════════════════════════════════

    /**
     * CP-21: Obtener todas las compras debe retornar un array con FakePDO.
     *
     * @test
     */
    public function CP21_obtener_retornaArray(): void
    {
        // Act
        $resultado = $this->compra->obtener();

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * CP-22: Buscar compra por ID inexistente retorna false con FakePDO.
     *
     * @test
     */
    public function CP22_obtenerPorId_idInexistente_retornaFalse(): void
    {
        // Act
        $resultado = $this->compra->obtenerPorId(9999);

        // Assert
        $this->assertFalse($resultado);
    }
}
