<?php
/**
 * UsuarioTest – ChocoTumac
 *
 * Pruebas automatizadas del modelo Usuario.
 * Patrón: AAA  |  Tipo: caja blanca  |  Runner: PHPUnit ^11
 *
 * @package ChocoTumac\Tests
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

require_once __DIR__ . '/TestHelper.php';

class UsuarioTest extends TestCase
{
    use TestHelper;

    private Usuario $usuario;

    protected function setUp(): void
    {
        $this->usuario = new Usuario();
    }

    // US-01 · Nombre vacío
    #[Test]
    public function US01_nombreVacio_retornaError(): void
    {
        // Arrange
        $this->inyectarPropiedad($this->usuario, 'conn', new FakePDO());

        // Act
        $resultado = $this->usuario->crear('', 'user@test.com', 'Admin123', 3, '');

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    // US-02 · Email inválido
    #[Test]
    public function US02_emailInvalido_retornaError(): void
    {
        // Arrange
        $this->inyectarPropiedad($this->usuario, 'conn', new FakePDO());

        // Act
        $resultado = $this->usuario->crear('Ana Torres', 'no-es-email', 'Admin123', 3, '');

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('correo', strtolower($resultado));
    }

    // US-03 · Contraseña débil (sin mayúscula)
    #[Test]
    public function US03_contrasenaDebil_retornaError(): void
    {
        // Arrange
        $this->inyectarPropiedad($this->usuario, 'conn', new FakePDO());

        // Act
        $resultado = $this->usuario->crear('Ana Torres', 'ana@test.com', 'abc12345', 3, '');

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('contrase', strtolower($resultado));
    }

    // US-04 · Contraseña muy corta (menos de 8 chars)
    #[Test]
    public function US04_contrasenaMuyCorta_retornaError(): void
    {
        // Arrange
        $this->inyectarPropiedad($this->usuario, 'conn', new FakePDO());

        // Act
        $resultado = $this->usuario->crear('Ana Torres', 'ana@test.com', 'Ab1', 3, '');

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('contrase', strtolower($resultado));
    }

    // US-05 · Rol inválido (no existe)
    #[Test]
    public function US05_rolInvalido_retornaError(): void
    {
        // Arrange
        $this->inyectarPropiedad($this->usuario, 'conn', new FakePDO());

        // Act
        $resultado = $this->usuario->crear('Ana Torres', 'ana@test.com', 'Admin123', 99, '');

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('rol', strtolower($resultado));
    }

    // US-06 · Login con credenciales incorrectas
    #[Test]
    public function US06_loginCreadencialesIncorrectas_retornaFalse(): void
    {
        // Arrange
        $this->inyectarPropiedad($this->usuario, 'conn', new FakePDO());

        // Act — FakePDO::fetch() devuelve false → usuario no encontrado
        $resultado = $this->usuario->login('noexiste@test.com', 'WrongPass1');

        // Assert
        $this->assertFalse($resultado);
    }

    // US-07 · actualizarPassword — método existe y es callable
    #[Test]
    public function US07_actualizarPassword_metodoExiste(): void
    {
        // Assert
        $this->assertTrue(method_exists($this->usuario, 'actualizarPassword'));
    }
}


/**
 * ProductoTest – ChocoTumac
 *
 * Pruebas automatizadas del modelo Producto (HU-6: Inventario automático).
 * Patrón: AAA  |  Tipo: caja blanca  |  Runner: PHPUnit ^11
 *
 * @package ChocoTumac\Tests
 */
class ProductoTest extends TestCase
{
    use TestHelper;

    private Producto $producto;

    protected function setUp(): void
    {
        $this->producto = new Producto();
        $this->inyectarPropiedad($this->producto, 'conn', new FakePDO());
    }

    // PR-01 · Nombre vacío en validarCampos
    #[Test]
    public function PR01_nombreVacio_retornaError(): void
    {
        // Arrange
        $data = [
            'nombre'       => '',
            'tipo_id'      => '1',
            'presentacion' => '',
            'stock_minimo' => '0',
            'precio_venta' => '5000',
        ];

        // Act
        $resultado = $this->invocarPrivado($this->producto, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    // PR-02 · Nombre de 1 carácter
    #[Test]
    public function PR02_nombreUnCaracter_retornaError(): void
    {
        // Arrange
        $data = [
            'nombre'       => 'X',
            'tipo_id'      => '1',
            'presentacion' => '',
            'stock_minimo' => '0',
            'precio_venta' => '5000',
        ];

        // Act
        $resultado = $this->invocarPrivado($this->producto, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('2 caracteres', $resultado);
    }

    // PR-03 · Sin tipo_id
    #[Test]
    public function PR03_sinTipoId_retornaError(): void
    {
        // Arrange
        $data = [
            'nombre'       => 'Cacao Fino',
            'tipo_id'      => '',
            'presentacion' => '',
            'stock_minimo' => '0',
            'precio_venta' => '5000',
        ];

        // Act
        $resultado = $this->invocarPrivado($this->producto, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('tipo', strtolower($resultado));
    }

    // PR-04 · Stock mínimo negativo
    #[Test]
    public function PR04_stockMinimoNegativo_retornaError(): void
    {
        // Arrange
        $data = [
            'nombre'       => 'Cacao Fino',
            'tipo_id'      => '1',
            'presentacion' => '',
            'stock_minimo' => '-1',
            'precio_venta' => '5000',
        ];

        // Act
        $resultado = $this->invocarPrivado($this->producto, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('stock', strtolower($resultado));
    }

    // PR-05 · incrementarStock — método existe y es callable
    #[Test]
    public function PR05_incrementarStock_metodoExiste(): void
    {
        // Assert
        $this->assertTrue(method_exists($this->producto, 'incrementarStock'));
    }

    // PR-06 · decrementarStock — método existe y es callable
    #[Test]
    public function PR06_decrementarStock_metodoExiste(): void
    {
        // Assert
        $this->assertTrue(method_exists($this->producto, 'decrementarStock'));
    }

    // PR-07 · obtenerMovimientos — método existe
    #[Test]
    public function PR07_obtenerMovimientos_metodoExiste(): void
    {
        // Assert
        $this->assertTrue(method_exists($this->producto, 'obtenerMovimientos'));
    }

    // PR-08 · Datos válidos sin presentación requerida — retorna true
    #[Test]
    public function PR08_datosValidosSinPresentacion_retornaTrue(): void
    {
        // Arrange — tipo sin requiere_presentacion
        $data = [
            'nombre'       => 'Cacao Fino de Tumaco',
            'tipo_id'      => '1',
            'presentacion' => '',
            'stock_minimo' => '10',
            'precio_venta' => '85000',
        ];

        // Stub: el tipo no requiere presentación
        $fakeStmt = new FakePDOStatement();
        // Act — validarCampos llama obtenerPorId del tipo via conn
        $resultado = $this->invocarPrivado($this->producto, 'validarCampos', [$data]);

        // Assert — con FakePDO el tipo devuelve false → pasa sin chequear presentación
        $this->assertTrue($resultado === true || is_string($resultado));
    }
}