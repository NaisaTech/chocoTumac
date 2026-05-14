<?php
/**
 * GestionClientesTest – ChocoTumac
 *
 * Suite de pruebas unitarias del modelo Cliente.
 * Valida todas las reglas de negocio definidas en HU-03:
 * registro, edición y eliminación de clientes de cacao.
 *
 * Cubre:
 *   - Validaciones de campos (nombre, documento, email, teléfono)
 *   - Reglas especiales para NIT (dígito de verificación)
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

class GestionClientesTest extends TestCase
{
    use TestHelper;

    /** @var Cliente Instancia del modelo bajo prueba */
    private Cliente $cliente;

    /**
     * Crea una instancia limpia del modelo antes de cada prueba.
     * Se inyecta FakePDO para evitar dependencia de la base de datos real.
     */
    protected function setUp(): void
    {
        $this->cliente = new Cliente();
        $this->inyectarPropiedad($this->cliente, 'conn', new FakePDO());
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 1 – Validación de nombre
    // ══════════════════════════════════════════════════════════════════

    /**
     * CL-01: El nombre es obligatorio para registrar un cliente.
     * Un nombre vacío debe retornar un mensaje de error.
     *
     * @test
     */
    public function CL01_nombreVacio_retornaError(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['nombre'] = '';

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado, 'Debe retornar un mensaje de error cuando el nombre está vacío');
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    /**
     * CL-02: El nombre debe tener al menos 2 caracteres.
     * Un nombre de 1 caracter debe ser rechazado.
     *
     * @test
     */
    public function CL02_nombreDeUnCaracter_retornaError(): void
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

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 2 – Validación de documento
    // ══════════════════════════════════════════════════════════════════

    /**
     * CL-03: El tipo de documento debe ser uno de los valores permitidos:
     * CC, NIT, CE o Pasaporte. Un valor desconocido debe ser rechazado.
     *
     * @test
     */
    public function CL03_tipoDocumentoInvalido_retornaError(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['tipo_doc'] = 'RUT';   // valor no permitido

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('documento', strtolower($resultado));
    }

    /**
     * CL-04: El número de documento solo puede contener dígitos.
     * Un número con letras debe ser rechazado.
     *
     * @test
     */
    public function CL04_numeroDocumentoConLetras_retornaError(): void
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

    /**
     * CL-05: Para tipo NIT, el dígito de verificación debe ser un dígito 0-9.
     * Una letra como dígito de verificación debe ser rechazada.
     *
     * @test
     */
    public function CL05_digitoVerificacionNIT_conLetra_retornaError(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['tipo_doc']   = 'NIT';
        $data['digito_ver'] = 'A';   // solo se permiten dígitos 0-9

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarDigitoNIT', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('dígito', strtolower($resultado));
    }

    /**
     * CL-06: Para tipo NIT, un dígito de verificación numérico válido debe pasar.
     *
     * @test
     */
    public function CL06_digitoVerificacionNIT_valido_retornaTrue(): void
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

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 3 – Validación de contacto (email y teléfono)
    // ══════════════════════════════════════════════════════════════════

    /**
     * CL-07: El email, cuando se proporciona, debe tener formato válido.
     * Un email sin arroba debe ser rechazado.
     *
     * @test
     */
    public function CL07_emailSinArroba_retornaError(): void
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

    /**
     * CL-08: El email es opcional. Un email vacío debe pasar la validación.
     *
     * @test
     */
    public function CL08_emailVacio_esOpcional_pasaValidacion(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['email'] = '';

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);

        // Assert
        $this->assertTrue($resultado, 'El email es opcional, un campo vacío debe pasar');
    }

    /**
     * CL-09: El teléfono debe tener al menos 7 dígitos.
     * Un teléfono de 3 dígitos debe ser rechazado.
     *
     * @test
     */
    public function CL09_telefonoDeMenosDe7Digitos_retornaError(): void
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

    /**
     * CL-10: El teléfono no puede contener caracteres especiales inválidos.
     *
     * @test
     */
    public function CL10_telefonoConCaracteresInvalidos_retornaError(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['telefono'] = 'tel#@!';

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('tel', strtolower($resultado));
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 4 – Validación de datos completos
    // ══════════════════════════════════════════════════════════════════

    /**
     * CL-11: Con todos los campos válidos, la validación debe retornar true.
     *
     * @test
     */
    public function CL11_todosLosCamposValidos_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 5 – Operaciones CRUD
    // ══════════════════════════════════════════════════════════════════

    /**
     * CL-12: Intentar crear un cliente con nombre vacío debe retornar error.
     * La validación ocurre antes de cualquier operación en la base de datos.
     *
     * @test
     */
    public function CL12_crear_nombreVacio_retornaError(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['nombre'] = '';

        // Act
        $resultado = $this->cliente->crear($data);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    /**
     * CL-13: Crear un cliente con datos válidos debe retornar true.
     * Con FakePDO, existeDoc() retorna false → se ejecuta el INSERT.
     *
     * @test
     */
    public function CL13_crear_datosValidos_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();

        // Act
        $resultado = $this->cliente->crear($data);

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * CL-14: Actualizar con email inválido debe retornar error.
     *
     * @test
     */
    public function CL14_actualizar_emailInvalido_retornaError(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['email'] = 'mal-formato';

        // Act
        $resultado = $this->cliente->actualizar(1, $data);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('correo', strtolower($resultado));
    }

    /**
     * CL-15: Actualizar con datos válidos debe retornar true.
     *
     * @test
     */
    public function CL15_actualizar_datosValidos_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();

        // Act
        $resultado = $this->cliente->actualizar(1, $data);

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * CL-16: Eliminar un cliente debe retornar true con FakePDO.
     *
     * @test
     */
    public function CL16_eliminar_retornaTrue(): void
    {
        // Arrange — ID existente (FakePDO no valida existencia real)

        // Act
        $resultado = $this->cliente->eliminar(1);

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * CL-17: Obtener todos los clientes no debe lanzar excepciones.
     *
     * @test
     */
    public function CL17_obtener_noLanzaExcepcion(): void
    {
        // Arrange — ya configurado con FakePDO en setUp()

        // Act & Assert — si lanza excepción, el test falla automáticamente
        $excepcion = null;
        try {
            $this->cliente->obtener();
        } catch (\Throwable $e) {
            $excepcion = $e;
        }
        $this->assertNull($excepcion, 'obtener() no debe lanzar excepción con FakePDO');
    }

    /**
     * CL-18: Buscar un cliente por ID inexistente con FakePDO debe retornar false.
     *
     * @test
     */
    public function CL18_obtenerPorId_idInexistente_retornaFalse(): void
    {
        // Arrange
        $idInexistente = 9999;

        // Act
        $resultado = $this->cliente->obtenerPorId($idInexistente);

        // Assert
        $this->assertFalse($resultado);
    }
}
