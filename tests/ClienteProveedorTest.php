<?php
/**
 * ClienteTest – ChocoTumac
 *
 * Pruebas automatizadas del modelo Cliente (soporte HU-5).
 * Patrón: AAA  |  Tipo: caja blanca  |  Runner: PHPUnit ^11
 *
 * @package ChocoTumac\Tests
 */

use PHPUnit\Framework\TestCase;


require_once __DIR__ . '/TestHelper.php';

class ClienteProveedorTest extends TestCase
{
    use TestHelper;

    private Cliente $cliente;

    protected function setUp(): void
    {
        $this->cliente = new Cliente();
    }

    // CL-01 · Nombre vacío
    /** @test */
    public function CL01_nombreVacio_retornaError(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['nombre'] = '';

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    // CL-02 · Nombre de 1 carácter (mínimo 2)
   /** @test */
    public function CL02_nombreUnCaracter_retornaError(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['nombre'] = 'X';

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('2 caracteres', $resultado);
    }

    // CL-03 · Tipo de documento inválido
    /** @test */
    public function CL03_tipoDocInvalido_retornaError(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['tipo_doc'] = 'RUT';   // no existe en el enum

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('documento', strtolower($resultado));
    }

    // CL-04 · Número de documento con letras
    /** @test */
    public function CL04_numDocConLetras_retornaError(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['num_doc'] = 'ABC-123';

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('documento', strtolower($resultado));
    }

    // CL-05 · Email con formato inválido
    /** @test */
    public function CL05_emailInvalido_retornaError(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['email'] = 'correo-sin-arroba';

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('correo', strtolower($resultado));
    }

    // CL-06 · Teléfono demasiado corto (menos de 7 dígitos)
    /** @test */
    public function CL06_telefonoCorto_retornaError(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['telefono'] = '123';

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('tel', strtolower($resultado));
    }

    // CL-07 · Dígito de verificación NIT inválido (letra)
   /** @test */
    public function CL07_digitoVerNITInvalido_retornaError(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['tipo_doc']   = 'NIT';
        $data['digito_ver'] = 'A';   // debe ser dígito 0-9

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarDigitoNIT', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('dígito', strtolower($resultado));
    }

    // CL-08 · Dígito de verificación NIT válido
   /** @test */
    public function CL08_digitoVerNITValido_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['tipo_doc']   = 'NIT';
        $data['digito_ver'] = '5';

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarDigitoNIT', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }

    // CL-09 · Datos completamente válidos — retorna true
    /** @test */
    public function CL09_datosValidos_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }
}


/**
 * ProveedorTest – ChocoTumac
 *
 * Pruebas automatizadas del modelo Proveedor (soporte HU-4).
 * Patrón: AAA  |  Tipo: caja blanca  |  Runner: PHPUnit ^11
 *
 * @package ChocoTumac\Tests
 */
class ProveedorTest extends TestCase
{
    use TestHelper;

    private Proveedor $proveedor;

    protected function setUp(): void
    {
        $this->proveedor = new Proveedor();
    }

    // PV-01 · Nombre vacío
  /** @test */
    public function PV01_nombreVacio_retornaError(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();
        $data['nombre'] = '';

        // Act
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    // PV-02 · Tipo de proveedor inválido
    /** @test */
    public function PV02_tipoProveedorInvalido_retornaError(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();
        $data['tipo_proveedor'] = 'Minorista';   // no está en el enum

        // Act
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('proveedor', strtolower($resultado));
    }

    // PV-03 · Email inválido
    /** @test */
    public function PV03_emailInvalido_retornaError(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();
        $data['email'] = 'no-es-email';

        // Act
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('correo', strtolower($resultado));
    }

    // PV-04 · Número de documento con letras
   /** @test */
    public function PV04_numDocConLetras_retornaError(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();
        $data['num_doc'] = 'NIT-ABC';

        // Act
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('documento', strtolower($resultado));
    }

    // PV-05 · Datos completamente válidos — retorna true
   /** @test */
    public function PV05_datosValidos_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();

        // Act
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }
}