<?php
/**
 * GestionProveedoresTest – ChocoTumac
 *
 * Suite de pruebas unitarias del modelo Proveedor.
 * Valida todas las reglas de negocio definidas en HU-02:
 * registro, edición y eliminación de proveedores de cacao.
 *
 * Cubre:
 *   - Validaciones de nombre, tipo de proveedor, documento, email, teléfono
 *   - Operaciones CRUD completo con FakePDO
 *
 * Patrón:  AAA (Arrange – Act – Assert)
 * Tipo:    Caja blanca (accede a validarCampos() privado via Reflection)
 * Runner:  PHPUnit 9
 *
 * @package ChocoTumac\Tests
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestHelper.php';

class GestionProveedoresTest extends TestCase
{
    use TestHelper;

    /** @var Proveedor Instancia del modelo bajo prueba */
    private Proveedor $proveedor;

    /**
     * Crea una instancia limpia con FakePDO antes de cada prueba.
     */
    protected function setUp(): void
    {
        $this->proveedor = new Proveedor();
        $this->inyectarPropiedad($this->proveedor, 'conn', new FakePDO());
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 1 – Validación de nombre
    // ══════════════════════════════════════════════════════════════════

    /**
     * PV-01: El nombre del proveedor es obligatorio.
     *
     * @test
     */
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

    /**
     * PV-02: El nombre debe tener al menos 2 caracteres.
     *
     * @test
     */
    public function PV02_nombreDeUnCaracter_retornaError(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();
        $data['nombre'] = 'A';

        // Act
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('2 caracteres', $resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 2 – Validación de tipo de proveedor
    // ══════════════════════════════════════════════════════════════════

    /**
     * PV-03: El tipo de proveedor debe ser uno de los valores definidos:
     * Agricultor, Intermediario, Cooperativa o Empresa.
     *
     * @test
     */
    public function PV03_tipoProveedorInvalido_retornaError(): void
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

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 3 – Validación de documento
    // ══════════════════════════════════════════════════════════════════

    /**
     * PV-04: El tipo de documento debe ser válido (NIT, CC, CE, Pasaporte).
     *
     * @test
     */
    public function PV04_tipoDocumentoInvalido_retornaError(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();
        $data['tipo_doc'] = 'CURP';   // tipo extranjero, no permitido

        // Act
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('documento', strtolower($resultado));
    }

    /**
     * PV-05: El número de documento solo puede contener dígitos.
     *
     * @test
     */
    public function PV05_numeroDocumentoConLetras_retornaError(): void
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

    /**
     * PV-06: El número de documento no puede estar vacío.
     *
     * @test
     */
    public function PV06_numeroDocumentoVacio_retornaError(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();
        $data['num_doc'] = '';

        // Act
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('documento', strtolower($resultado));
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 4 – Validación de contacto
    // ══════════════════════════════════════════════════════════════════

    /**
     * PV-07: El email, cuando se proporciona, debe tener formato válido.
     *
     * @test
     */
    public function PV07_emailInvalido_retornaError(): void
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

    /**
     * PV-08: El email es opcional. Un campo vacío debe pasar la validación.
     *
     * @test
     */
    public function PV08_emailVacio_esOpcional_pasaValidacion(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();
        $data['email'] = '';

        // Act
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);

        // Assert
        $this->assertTrue($resultado, 'El email es opcional para proveedores');
    }

    /**
     * PV-09: El teléfono no puede contener caracteres inválidos.
     *
     * @test
     */
    public function PV09_telefonoInvalido_retornaError(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();
        $data['telefono'] = 'abc!!';

        // Act
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('tel', strtolower($resultado));
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 5 – Validación completa y CRUD
    // ══════════════════════════════════════════════════════════════════

    /**
     * PV-10: Con todos los campos válidos, la validación debe retornar true.
     *
     * @test
     */
    public function PV10_todosLosCamposValidos_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();

        // Act
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * PV-11: Crear con nombre vacío debe retornar error antes de tocar la BD.
     *
     * @test
     */
    public function PV11_crear_nombreVacio_retornaError(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();
        $data['nombre'] = '';

        // Act
        $resultado = $this->proveedor->crear($data);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    /**
     * PV-12: Crear con datos válidos debe retornar true con FakePDO.
     *
     * @test
     */
    public function PV12_crear_datosValidos_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();

        // Act
        $resultado = $this->proveedor->crear($data);

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * PV-13: Actualizar con email inválido debe retornar error.
     *
     * @test
     */
    public function PV13_actualizar_emailInvalido_retornaError(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();
        $data['email'] = 'no-valido';

        // Act
        $resultado = $this->proveedor->actualizar(1, $data);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('correo', strtolower($resultado));
    }

    /**
     * PV-14: Actualizar con datos válidos debe retornar true.
     *
     * @test
     */
    public function PV14_actualizar_datosValidos_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();

        // Act
        $resultado = $this->proveedor->actualizar(1, $data);

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * PV-15: Eliminar un proveedor debe retornar true con FakePDO.
     *
     * @test
     */
    public function PV15_eliminar_retornaTrue(): void
    {
        // Act
        $resultado = $this->proveedor->eliminar(1);

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * PV-16: Obtener todos los proveedores no debe lanzar excepciones.
     *
     * @test
     */
    public function PV16_obtener_noLanzaExcepcion(): void
    {
        // Act & Assert
        $excepcion = null;
        try {
            $this->proveedor->obtener();
        } catch (\Throwable $e) {
            $excepcion = $e;
        }
        $this->assertNull($excepcion, 'obtener() no debe lanzar excepciones');
    }

    /**
     * PV-17: Buscar proveedor por ID inexistente retorna false con FakePDO.
     *
     * @test
     */
    public function PV17_obtenerPorId_idInexistente_retornaFalse(): void
    {
        // Act
        $resultado = $this->proveedor->obtenerPorId(9999);

        // Assert
        $this->assertFalse($resultado);
    }
}
