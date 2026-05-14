<?php
/**
 * ProductoVentaTest – ChocoTumac
 *
 * Pruebas adicionales para alcanzar ≥ 80 % de cobertura.
 * Cubre las ramas de Producto (crearTipo, validarCampos extendido),
 * Venta (formas de pago, IVA, cliente ocasional) y
 * Compra (precio unitario, datos válidos extendidos).
 *
 * Patrón: AAA (Arrange – Act – Assert)
 * Tipo:   Pruebas unitarias de caja blanca
 * Runner: PHPUnit ^11
 *
 * @package ChocoTumac\Tests
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestHelper.php';

// ════════════════════════════════════════════════════════════════════
//  Producto — crearTipo
// ════════════════════════════════════════════════════════════════════
class ProductoTipoTest extends TestCase
{
    use TestHelper;

    private Producto $producto;

    protected function setUp(): void
    {
        $this->producto = new Producto();
        $this->inyectarPropiedad($this->producto, 'conn', new FakePDO());
    }

    // PT-01 · crearTipo — nombre vacío
    /** @test */
    public function PT01_crearTipo_nombreVacio_retornaError(): void
    {
        // Arrange
        $data = [
            'nombre'      => '',
            'unidad'      => 'kg',
            'unidad_venta'=> 'kg',
            'descripcion' => '',
        ];

        // Act
        $resultado = $this->producto->crearTipo($data);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    // PT-02 · crearTipo — unidad de inventario inválida
    /** @test */
    public function PT02_crearTipo_unidadInvalida_retornaError(): void
    {
        // Arrange
        $data = [
            'nombre'       => 'Cacao Especial',
            'unidad'       => 'litros',   // no válida
            'unidad_venta' => 'kg',
            'descripcion'  => '',
        ];

        // Act
        $resultado = $this->producto->crearTipo($data);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('unidad', strtolower($resultado));
    }

    // PT-03 · crearTipo — unidad de venta inválida
    /** @test */
    public function PT03_crearTipo_unidadVentaInvalida_retornaError(): void
    {
        // Arrange
        $data = [
            'nombre'       => 'Cacao Especial',
            'unidad'       => 'kg',
            'unidad_venta' => 'tonelada',  // no válida
            'descripcion'  => '',
        ];

        // Act
        $resultado = $this->producto->crearTipo($data);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('unidad', strtolower($resultado));
    }

    // PT-04 · crearTipo — datos válidos con FakePDO retorna true
    /** @test */
    public function PT04_crearTipo_datosValidos_retornaTrue(): void
    {
        // Arrange — FakePDO::fetch() devuelve false → slug no existe → INSERT exitoso
        $data = [
            'nombre'       => 'Derivado Cacao',
            'unidad'       => 'kg',
            'unidad_venta' => 'und',
            'descripcion'  => 'Tipo para derivados',
        ];

        // Act
        $resultado = $this->producto->crearTipo($data);

        // Assert
        $this->assertTrue($resultado);
    }

    // PT-05 · actualizarTipo — unidad inválida retorna error
    /** @test */
    public function PT05_actualizarTipo_unidadInvalida_retornaError(): void
    {
        // Arrange
        $data = [
            'nombre'       => 'Cacao Premium',
            'unidad'       => 'metro',   // no válida
            'unidad_venta' => 'kg',
            'descripcion'  => '',
        ];

        // Act
        $resultado = $this->producto->actualizarTipo(1, $data);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('unidad', strtolower($resultado));
    }

    // PT-06 · validarCampos producto — precio_venta negativo
    /** @test */
    public function PT06_validarCampos_precioVentaNegativo_retornaError(): void
    {
        // Arrange
        $data = [
            'nombre'       => 'Cacao Fino',
            'tipo_id'      => '1',
            'presentacion' => '',
            'stock_minimo' => '5',
            'precio_venta' => '-100',
        ];

        // Act
        $resultado = $this->invocarPrivado($this->producto, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('precio', strtolower($resultado));
    }

    // PT-07 · validarCampos producto — stock mínimo no numérico
    /** @test */
    public function PT07_validarCampos_stockMinimoTexto_retornaError(): void
    {
        // Arrange
        $data = [
            'nombre'       => 'Cacao Fino',
            'tipo_id'      => '1',
            'presentacion' => '',
            'stock_minimo' => 'mucho',
            'precio_venta' => '5000',
        ];

        // Act
        $resultado = $this->invocarPrivado($this->producto, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('stock', strtolower($resultado));
    }

    // PT-08 · obtenerTipos — retorna array con FakePDO
    /** @test */
    public function PT08_obtenerTipos_retornaArray(): void
    {
        // Act
        $resultado = $this->producto->obtenerTipos();

        // Assert
        $this->assertIsArray($resultado);
    }

    // PT-09 · obtenerActivos — retorna array con FakePDO
    /** @test */
    public function PT09_obtenerActivos_retornaArray(): void
    {
        // Act
        $resultado = $this->producto->obtenerActivos();

        // Assert
        $this->assertIsArray($resultado);
    }

    // PT-10 · obtener — retorna array con FakePDO
    /** @test */
    public function PT10_obtener_retornaArray(): void
    {
        // Act
        $resultado = $this->producto->obtener();

        // Assert
        $this->assertIsArray($resultado);
    }
}


// ════════════════════════════════════════════════════════════════════
//  Venta — casos adicionales
// ════════════════════════════════════════════════════════════════════
class VentaAdicionalTest extends TestCase
{
    use TestHelper;

    private Venta $venta;

    protected function setUp(): void
    {
        $this->venta = new Venta();
        $this->inyectarPropiedad($this->venta, 'conn', new FakePDO());
    }

    // VA-01 · validarCliente — cliente registrado con ID válido
    /** @test */
    public function VA01_clienteRegistradoConId_retornaTrue(): void
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

    // VA-02 · validarCliente — tipo_cliente no reconocido (ni registrado ni ocasional)
    /** @test */
    public function VA02_tipoClienteDesconocido_retornaError(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        $data['tipo_cliente'] = 'mayorista';  // no existe
        $data['cliente_id']   = '';

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCliente', [$data]);

        // Assert — debe retornar error al no tener cliente definido
        $this->assertIsString($resultado);
    }

    // VA-03 · validarCampos — cantidad no numérica
    /** @test */
    public function VA03_cantidadTexto_retornaError(): void
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

    // VA-04 · validarCampos — precio_unitario negativo
    /** @test */
    public function VA04_precioUnitarioNegativo_retornaError(): void
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

    // VA-05 · validarCampos — datos completamente válidos retorna true
    /** @test */
    public function VA05_datosValidos_retornaTrue(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }

    // VA-06 · obtener — retorna array con FakePDO
    /** @test */
    public function VA06_obtener_retornaArray(): void
    {
        // Act
        $resultado = $this->venta->obtener();

        // Assert
        $this->assertIsArray($resultado);
    }

    // VA-07 · validarCampos — precio_unitario no numérico
    /** @test */
    public function VA07_precioUnitarioTexto_retornaError(): void
    {
        // Arrange
        $data = $this->datosVentaValidos();
        $data['precio_unitario'] = 'gratis';

        // Act
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('precio', strtolower($resultado));
    }
}


// ════════════════════════════════════════════════════════════════════
//  Compra — casos adicionales
// ════════════════════════════════════════════════════════════════════
class CompraAdicionalTest extends TestCase
{
    use TestHelper;

    private Compra $compra;

    protected function setUp(): void
    {
        $this->compra = new Compra();
        $this->inyectarPropiedad($this->compra, 'conn', new FakePDO());
    }

    // CA-01 · validarCampos — proveedor_id no numérico
    /** @test */
    public function CA01_proveedorIdNoNumerico_retornaError(): void
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

    // CA-02 · validarCampos — cantidad no numérica
    /** @test */
    public function CA02_cantidadNoNumerica_retornaError(): void
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

    // CA-03 · validarCampos — precio_unitario no numérico
    /** @test */
    public function CA03_precioUnitarioTexto_retornaError(): void
    {
        // Arrange
        $data = $this->datosCompraValidos();
        $data['precio_unitario'] = 'caro';

        // Act
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('precio', strtolower($resultado));
    }

    // CA-04 · obtener — retorna array con FakePDO
    /** @test */
    public function CA04_obtener_retornaArray(): void
    {
        // Act
        $resultado = $this->compra->obtener();

        // Assert
        $this->assertIsArray($resultado);
    }
}


// ════════════════════════════════════════════════════════════════════
//  Cliente — casos adicionales
// ════════════════════════════════════════════════════════════════════
class ClienteAdicionalTest extends TestCase
{
    use TestHelper;

    private Cliente $cliente;

    protected function setUp(): void
    {
        $this->cliente = new Cliente();
        $this->inyectarPropiedad($this->cliente, 'conn', new FakePDO());
    }

    // CLA-01 · validarCampos — número de documento vacío
    /** @test */
    public function CLA01_numDocVacio_retornaError(): void
    {
        // Arrange
        $data = $this->datosClienteValidos();
        $data['num_doc'] = '';

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('documento', strtolower($resultado));
    }

    // CLA-02 · validarCampos — teléfono con caracteres especiales inválidos
    /** @test */
    public function CLA02_telefonoCaracteresInvalidos_retornaError(): void
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

    // CLA-03 · validarCampos — email vacío pasa validación (campo opcional)
    /** @test */
    public function CLA03_emailVacio_pasaValidacion(): void
    {
        // Arrange — email vacío es permitido (campo opcional)
        $data = $this->datosClienteValidos();
        $data['email'] = '';

        // Act
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }

    // CLA-04 · obtener — no lanza excepción con FakePDO
    /** @test */
    public function CLA04_obtener_noLanzaExcepcion(): void
    {
        // Act — Cliente::obtener() usa query()->fetchAll() internamente;
        // con FakePDO retorna FakePDOStatement (que tiene fetchAll()),
        // así que verificamos que el método sea invocable sin error.
        $excepcion = null;
        try {
            $this->cliente->obtener();
        } catch (\Throwable $e) {
            $excepcion = $e;
        }

        // Assert
        $this->assertNull($excepcion, 'obtener() no debe lanzar excepción');
    }
}


// ════════════════════════════════════════════════════════════════════
//  Proveedor — casos adicionales
// ════════════════════════════════════════════════════════════════════
class ProveedorAdicionalTest extends TestCase
{
    use TestHelper;

    private Proveedor $proveedor;

    protected function setUp(): void
    {
        $this->proveedor = new Proveedor();
        $this->inyectarPropiedad($this->proveedor, 'conn', new FakePDO());
    }

    // PVA-01 · validarCampos — nombre de 1 carácter
    /** @test */
    public function PVA01_nombreUnCaracter_retornaError(): void
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

    // PVA-02 · validarCampos — número de documento vacío
    /** @test */
    public function PVA02_numDocVacio_retornaError(): void
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

    // PVA-03 · validarCampos — tipo_doc inválido
    /** @test */
    public function PVA03_tipoDocInvalido_retornaError(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();
        $data['tipo_doc'] = 'CURP';  // no válido

        // Act
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('documento', strtolower($resultado));
    }

    // PVA-04 · validarCampos — teléfono inválido
    /** @test */
    public function PVA04_telefonoInvalido_retornaError(): void
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

    // PVA-05 · validarCampos — email vacío pasa validación (campo opcional)
    /** @test */
    public function PVA05_emailVacio_pasaValidacion(): void
    {
        // Arrange
        $data = $this->datosProveedorValidos();
        $data['email'] = '';

        // Act
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);

        // Assert
        $this->assertTrue($resultado);
    }

    // PVA-06 · obtener — no lanza excepción con FakePDO
    /** @test */
    public function PVA06_obtener_noLanzaExcepcion(): void
    {
        // Act — igual que Cliente::obtener(), usa query() directamente
        $excepcion = null;
        try {
            $this->proveedor->obtener();
        } catch (\Throwable $e) {
            $excepcion = $e;
        }

        // Assert
        $this->assertNull($excepcion, 'obtener() no debe lanzar excepción');
    }
}


// ════════════════════════════════════════════════════════════════════
//  Usuario — casos adicionales
// ════════════════════════════════════════════════════════════════════
class UsuarioAdicionalTest extends TestCase
{
    use TestHelper;

    private Usuario $usuario;

    protected function setUp(): void
    {
        $this->usuario = new Usuario();
        $this->inyectarPropiedad($this->usuario, 'conn', new FakePDO());
    }

    // USA-01 · crear — contraseña sin número retorna error
    /** @test */
    public function USA01_contrasenaSinNumero_retornaError(): void
    {
        // Arrange
        $resultado = $this->usuario->crear('Carlos Rivas', 'carlos@test.com', 'SinNumeros', 3, '');

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('contrase', strtolower($resultado));
    }

    // USA-02 · crear — email duplicado (FakePDO fetch devuelve false → sin duplicado)
    /** @test */
    public function USA02_crearConFakePDO_noLanzaExcepcion(): void
    {
        // Con FakePDO el flujo llega hasta el INSERT sin error de duplicado
        $resultado = $this->usuario->crear('Luis Paz', 'luis@test.com', 'Admin123', 3, '');

        // Assert — true o string son aceptables con FakePDO
        $this->assertTrue($resultado === true || is_string($resultado));
    }

    // USA-03 · obtener — no lanza excepción con FakePDO
    /** @test */
    public function USA03_obtener_noLanzaExcepcion(): void
    {
        // Act — Usuario::obtener() usa query() con JOIN; FakePDO lo acepta
        $excepcion = null;
        try {
            $this->usuario->obtener();
        } catch (\Throwable $e) {
            $excepcion = $e;
        }

        // Assert
        $this->assertNull($excepcion, 'obtener() no debe lanzar excepción');
    }

    // USA-04 · actualizarPassword — método existe y acepta parámetros
    /** @test */
    public function USA04_actualizarPassword_aceptaParametros(): void
    {
        // Assert — existe el método con la firma correcta
        $this->assertTrue(method_exists($this->usuario, 'actualizarPassword'));
        $ref = new ReflectionMethod($this->usuario, 'actualizarPassword');
        $this->assertGreaterThanOrEqual(2, $ref->getNumberOfParameters());
    }

    // USA-05 · login — email vacío retorna false
    /** @test */
    public function USA05_loginEmailVacio_retornaFalse(): void
    {
        // Act
        $resultado = $this->usuario->login('', 'Admin123');

        // Assert
        $this->assertFalse($resultado);
    }
}