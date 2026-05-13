<?php
/**
 * VentaTest – ChocoTumac
 *
 * Pruebas automatizadas del modelo Venta.
 * Cubre HU-5: Registro de Ventas de Cacao.
 *
 * Patrón: AAA (Arrange – Act – Assert)
 * Tipo:   Pruebas unitarias de caja blanca
 * Runner: PHPUnit ^11
 *
 * @package ChocoTumac\Tests
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/TestHelper.php';

class VentaTest extends TestCase
{
    use TestHelper;

    private Venta $venta;

    /** Crea instancia limpia antes de cada test */
    protected function setUp(): void
    {
        $this->venta = new Venta();
    }

    // ════════════════════════════════════════════════════════════════
    // VT-01 · Validar cliente — sin cliente ni ocasional
    // ════════════════════════════════════════════════════════════════
    /** @test */
    public function VT01_sinClienteRegistradoNiOcasional_retornaError(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        unset($data['cliente_id']);
        $data['tipo_cliente'] = 'registrado';   // registrado pero sin ID

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCliente', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('cliente registrado', $resultado);
    }

    // ════════════════════════════════════════════════════════════════
    // VT-02 · Validar cliente — cliente ocasional aceptado
    // ════════════════════════════════════════════════════════════════
    /** @test */
    public function VT02_clienteOcasional_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        unset($data['cliente_id']);
        $data['tipo_cliente'] = 'ocasional';

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCliente', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }

    // ════════════════════════════════════════════════════════════════
    // VT-03 · Validar campos — sin producto_id
    // ════════════════════════════════════════════════════════════════
    /** @test */
    public function VT03_sinProductoId_retornaError(): void
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

    // ════════════════════════════════════════════════════════════════
    // VT-04 · Validar campos — fecha vacía
    // ════════════════════════════════════════════════════════════════
    /** @test */
    public function VT04_fechaVacia_retornaError(): void
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

    // ════════════════════════════════════════════════════════════════
    // VT-05 · Validar campos — fecha con formato inválido
    // ════════════════════════════════════════════════════════════════
    /** @test */
    public function VT05_fechaInvalida_retornaError(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        $data['fecha'] = '32-13-2026';   // fecha imposible

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('fecha', strtolower($resultado));
    }

    // ════════════════════════════════════════════════════════════════
    // VT-06 · Validar campos — cantidad cero
    // ════════════════════════════════════════════════════════════════
    /** @test */
    public function VT06_cantidadCero_retornaError(): void
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

    // ════════════════════════════════════════════════════════════════
    // VT-07 · Validar campos — cantidad negativa
    // ════════════════════════════════════════════════════════════════
    /** @test */
    public function VT07_cantidadNegativa_retornaError(): void
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

    // ════════════════════════════════════════════════════════════════
    // VT-08 · Validar cantidad por unidad — fracción en producto "und"
    // ════════════════════════════════════════════════════════════════
    /** @test */
    public function VT08_fraccionEnProductoUnd_retornaError(): void
    {
        // Arrange
        $data = ['producto_id' => '1', 'cantidad' => '2.5'];

        // Stub: simula producto con unidad "und"
        $mockProducto = $this->createMock(Producto::class);
        $mockProducto->method('obtenerPorId')->willReturn([
            'id'     => 1,
            'nombre' => 'Tableta',
            'unidad' => 'und',
        ]);
        $this->inyectarPropiedad($this->venta, 'modelProducto', $mockProducto);

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCantidadPorUnidad', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('entero', strtolower($resultado));
    }

    // ════════════════════════════════════════════════════════════════
    // VT-09 · Validar cantidad por unidad — entero en producto "und" OK
    // ════════════════════════════════════════════════════════════════
    /** @test */
    public function VT09_enteroEnProductoUnd_retornaTrue(): void
    {
        // Arrange
        $data = ['producto_id' => '1', 'cantidad' => '3'];

        $mockProducto = $this->createMock(Producto::class);
        $mockProducto->method('obtenerPorId')->willReturn([
            'id'     => 1,
            'nombre' => 'Tableta',
            'unidad' => 'und',
        ]);
        $this->inyectarPropiedad($this->venta, 'modelProducto', $mockProducto);

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCantidadPorUnidad', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }

    // ════════════════════════════════════════════════════════════════
    // VT-10 · Validar campos — precio unitario cero
    // ════════════════════════════════════════════════════════════════
    /** @test */
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
}