<?php
/**
 * ProductoVentaTest – ChocoTumac
 *
 * Pruebas adicionales para alcanzar ≥ 80 % de cobertura.
 * Cubre CRUD completo de Cliente, Proveedor, Usuario, Compra, Venta
 * y la gestión de tipos/productos de Producto.
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
//  Producto — crearTipo y validarCampos extendido
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
        $data = ['nombre' => '', 'unidad' => 'kg', 'unidad_venta' => 'kg', 'descripcion' => ''];
        $resultado = $this->producto->crearTipo($data);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    // PT-02 · crearTipo — unidad de inventario inválida
    /** @test */
    public function PT02_crearTipo_unidadInvalida_retornaError(): void
    {
        $data = ['nombre' => 'Cacao Especial', 'unidad' => 'litros', 'unidad_venta' => 'kg', 'descripcion' => ''];
        $resultado = $this->producto->crearTipo($data);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('unidad', strtolower($resultado));
    }

    // PT-03 · crearTipo — unidad de venta inválida
    /** @test */
    public function PT03_crearTipo_unidadVentaInvalida_retornaError(): void
    {
        $data = ['nombre' => 'Cacao Especial', 'unidad' => 'kg', 'unidad_venta' => 'tonelada', 'descripcion' => ''];
        $resultado = $this->producto->crearTipo($data);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('unidad', strtolower($resultado));
    }

    // PT-04 · crearTipo — datos válidos con FakePDO retorna true
    /** @test */
    public function PT04_crearTipo_datosValidos_retornaTrue(): void
    {
        // FakePDO::fetch() devuelve false → slug no existe → INSERT exitoso
        $data = ['nombre' => 'Derivado Cacao', 'unidad' => 'kg', 'unidad_venta' => 'und', 'descripcion' => 'Tipo para derivados'];
        $resultado = $this->producto->crearTipo($data);
        $this->assertTrue($resultado);
    }

    // PT-05 · actualizarTipo — unidad inválida retorna error
    /** @test */
    public function PT05_actualizarTipo_unidadInvalida_retornaError(): void
    {
        $data = ['nombre' => 'Cacao Premium', 'unidad' => 'metro', 'unidad_venta' => 'kg', 'descripcion' => ''];
        $resultado = $this->producto->actualizarTipo(1, $data);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('unidad', strtolower($resultado));
    }

    // PT-06 · actualizarTipo — datos válidos con FakePDO retorna true
    /** @test */
    public function PT06_actualizarTipo_datosValidos_retornaTrue(): void
    {
        $data = ['nombre' => 'Cacao Premium', 'unidad' => 'kg', 'unidad_venta' => 'kg', 'descripcion' => '', 'activo' => 1];
        $resultado = $this->producto->actualizarTipo(1, $data);
        $this->assertTrue($resultado);
    }

    // PT-07 · validarCampos producto — precio_venta negativo
    /** @test */
    public function PT07_validarCampos_precioVentaNegativo_retornaError(): void
    {
        $data = ['nombre' => 'Cacao Fino', 'tipo_id' => '1', 'presentacion' => '', 'stock_minimo' => '5', 'precio_venta' => '-100'];
        $resultado = $this->invocarPrivado($this->producto, 'validarCampos', [$data]);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('precio', strtolower($resultado));
    }

    // PT-08 · validarCampos producto — stock mínimo no numérico
    /** @test */
    public function PT08_validarCampos_stockMinimoTexto_retornaError(): void
    {
        $data = ['nombre' => 'Cacao Fino', 'tipo_id' => '1', 'presentacion' => '', 'stock_minimo' => 'mucho', 'precio_venta' => '5000'];
        $resultado = $this->invocarPrivado($this->producto, 'validarCampos', [$data]);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('stock', strtolower($resultado));
    }

    // PT-09 · obtenerTipos — retorna array con FakePDO
    /** @test */
    public function PT09_obtenerTipos_retornaArray(): void
    {
        $this->assertIsArray($this->producto->obtenerTipos());
    }

    // PT-10 · obtenerActivos — retorna array con FakePDO
    /** @test */
    public function PT10_obtenerActivos_retornaArray(): void
    {
        $this->assertIsArray($this->producto->obtenerActivos());
    }

    // PT-11 · obtener — retorna array con FakePDO
    /** @test */
    public function PT11_obtener_retornaArray(): void
    {
        $this->assertIsArray($this->producto->obtener());
    }

    // PT-12 · obtenerPorId — retorna false con FakePDO (sin BD real)
    /** @test */
    public function PT12_obtenerPorId_retornaFalseConFakePDO(): void
    {
        $resultado = $this->producto->obtenerPorId(99);
        $this->assertFalse($resultado);
    }

    // PT-13 · obtenerPorTipo — retorna array con FakePDO
    /** @test */
    public function PT13_obtenerPorTipo_retornaArray(): void
    {
        $this->assertIsArray($this->producto->obtenerPorTipo('cacao_grano'));
    }

    // PT-14 · crear producto — nombre vacío retorna error
    /** @test */
    public function PT14_crearProducto_nombreVacio_retornaError(): void
    {
        $data = ['nombre' => '', 'tipo_id' => '1', 'presentacion' => '', 'stock_minimo' => '0', 'precio_venta' => '5000'];
        $resultado = $this->producto->crear($data);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
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
        $data = $this->datosVentaValidos();
        $data['tipo_cliente'] = 'registrado';
        $data['cliente_id']   = '5';
        $resultado = $this->invocarPrivado($this->venta, 'validarCliente', [$data]);
        $this->assertTrue($resultado);
    }

    // VA-02 · validarCliente — tipo desconocido sin ID retorna error
    /** @test */
    public function VA02_tipoClienteDesconocido_retornaError(): void
    {
        $data = $this->datosVentaValidos();
        $data['tipo_cliente'] = 'mayorista';
        $data['cliente_id']   = '';
        $resultado = $this->invocarPrivado($this->venta, 'validarCliente', [$data]);
        $this->assertIsString($resultado);
    }

    // VA-03 · validarCampos — cantidad no numérica
    /** @test */
    public function VA03_cantidadTexto_retornaError(): void
    {
        $data = $this->datosVentaValidos();
        $data['cantidad'] = 'diez';
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('cantidad', strtolower($resultado));
    }

    // VA-04 · validarCampos — precio_unitario negativo
    /** @test */
    public function VA04_precioUnitarioNegativo_retornaError(): void
    {
        $data = $this->datosVentaValidos();
        $data['precio_unitario'] = '-1000';
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('precio', strtolower($resultado));
    }

    // VA-05 · validarCampos — precio_unitario no numérico
    /** @test */
    public function VA05_precioUnitarioTexto_retornaError(): void
    {
        $data = $this->datosVentaValidos();
        $data['precio_unitario'] = 'gratis';
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('precio', strtolower($resultado));
    }

    // VA-06 · validarCampos — datos completamente válidos retorna true
    /** @test */
    public function VA06_datosValidos_retornaTrue(): void
    {
        $data = $this->datosVentaValidos();
        $resultado = $this->invocarPrivado($this->venta, 'validarCampos', [$data]);
        $this->assertTrue($resultado);
    }

    // VA-07 · obtener — retorna array con FakePDO
    /** @test */
    public function VA07_obtener_retornaArray(): void
    {
        $this->assertIsArray($this->venta->obtener());
    }

    // VA-08 · obtenerPorId — retorna false con FakePDO
    /** @test */
    public function VA08_obtenerPorId_retornaFalseConFakePDO(): void
    {
        $resultado = $this->venta->obtenerPorId(99);
        $this->assertFalse($resultado);
    }

    // VA-09 · eliminar — ID inexistente retorna error
    /** @test */
    public function VA09_eliminar_idInexistente_retornaError(): void
    {
        // FakePDO::fetch() devuelve false → obtenerPorId retorna false
        $resultado = $this->venta->eliminar(999, 1);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('no encontrada', strtolower($resultado));
    }

    // VA-10 · crear — validación falla antes de llegar a la BD
    /** @test */
    public function VA10_crear_fechaVacia_retornaError(): void
    {
        $data = $this->datosVentaValidos();
        $data['fecha'] = '';
        $resultado = $this->venta->crear($data, 1);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('fecha', strtolower($resultado));
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
        $data = $this->datosCompraValidos();
        $data['proveedor_id'] = 'abc';
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('proveedor', strtolower($resultado));
    }

    // CA-02 · validarCampos — cantidad no numérica
    /** @test */
    public function CA02_cantidadNoNumerica_retornaError(): void
    {
        $data = $this->datosCompraValidos();
        $data['cantidad'] = 'muchos';
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('cantidad', strtolower($resultado));
    }

    // CA-03 · validarCampos — precio_unitario no numérico
    /** @test */
    public function CA03_precioUnitarioTexto_retornaError(): void
    {
        $data = $this->datosCompraValidos();
        $data['precio_unitario'] = 'caro';
        $resultado = $this->invocarPrivado($this->compra, 'validarCampos', [$data]);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('precio', strtolower($resultado));
    }

    // CA-04 · obtener — retorna array con FakePDO
    /** @test */
    public function CA04_obtener_retornaArray(): void
    {
        $this->assertIsArray($this->compra->obtener());
    }

    // CA-05 · obtenerPorId — retorna false con FakePDO
    /** @test */
    public function CA05_obtenerPorId_retornaFalseConFakePDO(): void
    {
        $resultado = $this->compra->obtenerPorId(99);
        $this->assertFalse($resultado);
    }

    // CA-06 · eliminar — ID inexistente retorna error
    /** @test */
    public function CA06_eliminar_idInexistente_retornaError(): void
    {
        $resultado = $this->compra->eliminar(999, 1);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('no encontrada', strtolower($resultado));
    }

    // CA-07 · crear — validación falla antes de la BD
    /** @test */
    public function CA07_crear_fechaVacia_retornaError(): void
    {
        $data = $this->datosCompraValidos();
        $data['fecha'] = '';
        $resultado = $this->compra->crear($data, 1);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('fecha', strtolower($resultado));
    }
}


// ════════════════════════════════════════════════════════════════════
//  Cliente — CRUD completo
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
        $data = $this->datosClienteValidos();
        $data['num_doc'] = '';
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('documento', strtolower($resultado));
    }

    // CLA-02 · validarCampos — teléfono con caracteres inválidos
    /** @test */
    public function CLA02_telefonoCaracteresInvalidos_retornaError(): void
    {
        $data = $this->datosClienteValidos();
        $data['telefono'] = 'tel#@!';
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('tel', strtolower($resultado));
    }

    // CLA-03 · validarCampos — email vacío es opcional (pasa validación)
    /** @test */
    public function CLA03_emailVacio_pasaValidacion(): void
    {
        $data = $this->datosClienteValidos();
        $data['email'] = '';
        $resultado = $this->invocarPrivado($this->cliente, 'validarCampos', [$data]);
        $this->assertTrue($resultado);
    }

    // CLA-04 · obtener — no lanza excepción con FakePDO
    /** @test */
    public function CLA04_obtener_noLanzaExcepcion(): void
    {
        $excepcion = null;
        try {
            $this->cliente->obtener();
        } catch (\Throwable $e) {
            $excepcion = $e;
        }
        $this->assertNull($excepcion, 'obtener() no debe lanzar excepción');
    }

    // CLA-05 · obtenerPorId — retorna false con FakePDO
    /** @test */
    public function CLA05_obtenerPorId_retornaFalseConFakePDO(): void
    {
        $resultado = $this->cliente->obtenerPorId(99);
        $this->assertFalse($resultado);
    }

    // CLA-06 · crear — nombre vacío retorna error
    /** @test */
    public function CLA06_crear_nombreVacio_retornaError(): void
    {
        $data = $this->datosClienteValidos();
        $data['nombre'] = '';
        $resultado = $this->cliente->crear($data);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    // CLA-07 · crear — datos válidos con FakePDO (existeDoc → false → inserta)
    /** @test */
    public function CLA07_crear_datosValidos_retornaTrue(): void
    {
        $data = $this->datosClienteValidos();
        $resultado = $this->cliente->crear($data);
        $this->assertTrue($resultado);
    }

    // CLA-08 · actualizar — email inválido retorna error
    /** @test */
    public function CLA08_actualizar_emailInvalido_retornaError(): void
    {
        $data = $this->datosClienteValidos();
        $data['email'] = 'mal-formato';
        $resultado = $this->cliente->actualizar(1, $data);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('correo', strtolower($resultado));
    }

    // CLA-09 · actualizar — datos válidos con FakePDO retorna true
    /** @test */
    public function CLA09_actualizar_datosValidos_retornaTrue(): void
    {
        $data = $this->datosClienteValidos();
        $resultado = $this->cliente->actualizar(1, $data);
        $this->assertTrue($resultado);
    }

    // CLA-10 · eliminar — retorna true con FakePDO
    /** @test */
    public function CLA10_eliminar_retornaTrue(): void
    {
        $resultado = $this->cliente->eliminar(1);
        $this->assertTrue($resultado);
    }
}


// ════════════════════════════════════════════════════════════════════
//  Proveedor — CRUD completo
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
        $data = $this->datosProveedorValidos();
        $data['nombre'] = 'A';
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('2 caracteres', $resultado);
    }

    // PVA-02 · validarCampos — número de documento vacío
    /** @test */
    public function PVA02_numDocVacio_retornaError(): void
    {
        $data = $this->datosProveedorValidos();
        $data['num_doc'] = '';
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('documento', strtolower($resultado));
    }

    // PVA-03 · validarCampos — tipo_doc inválido
    /** @test */
    public function PVA03_tipoDocInvalido_retornaError(): void
    {
        $data = $this->datosProveedorValidos();
        $data['tipo_doc'] = 'CURP';
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('documento', strtolower($resultado));
    }

    // PVA-04 · validarCampos — teléfono inválido
    /** @test */
    public function PVA04_telefonoInvalido_retornaError(): void
    {
        $data = $this->datosProveedorValidos();
        $data['telefono'] = 'abc!!';
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('tel', strtolower($resultado));
    }

    // PVA-05 · validarCampos — email vacío pasa validación (campo opcional)
    /** @test */
    public function PVA05_emailVacio_pasaValidacion(): void
    {
        $data = $this->datosProveedorValidos();
        $data['email'] = '';
        $resultado = $this->invocarPrivado($this->proveedor, 'validarCampos', [$data]);
        $this->assertTrue($resultado);
    }

    // PVA-06 · obtener — no lanza excepción con FakePDO
    /** @test */
    public function PVA06_obtener_noLanzaExcepcion(): void
    {
        $excepcion = null;
        try {
            $this->proveedor->obtener();
        } catch (\Throwable $e) {
            $excepcion = $e;
        }
        $this->assertNull($excepcion, 'obtener() no debe lanzar excepción');
    }

    // PVA-07 · obtenerPorId — retorna false con FakePDO
    /** @test */
    public function PVA07_obtenerPorId_retornaFalseConFakePDO(): void
    {
        $resultado = $this->proveedor->obtenerPorId(99);
        $this->assertFalse($resultado);
    }

    // PVA-08 · crear — nombre vacío retorna error
    /** @test */
    public function PVA08_crear_nombreVacio_retornaError(): void
    {
        $data = $this->datosProveedorValidos();
        $data['nombre'] = '';
        $resultado = $this->proveedor->crear($data);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    // PVA-09 · crear — datos válidos con FakePDO retorna true
    /** @test */
    public function PVA09_crear_datosValidos_retornaTrue(): void
    {
        $data = $this->datosProveedorValidos();
        $resultado = $this->proveedor->crear($data);
        $this->assertTrue($resultado);
    }

    // PVA-10 · actualizar — email inválido retorna error
    /** @test */
    public function PVA10_actualizar_emailInvalido_retornaError(): void
    {
        $data = $this->datosProveedorValidos();
        $data['email'] = 'no-valido';
        $resultado = $this->proveedor->actualizar(1, $data);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('correo', strtolower($resultado));
    }

    // PVA-11 · actualizar — datos válidos con FakePDO retorna true
    /** @test */
    public function PVA11_actualizar_datosValidos_retornaTrue(): void
    {
        $data = $this->datosProveedorValidos();
        $resultado = $this->proveedor->actualizar(1, $data);
        $this->assertTrue($resultado);
    }

    // PVA-12 · eliminar — retorna true con FakePDO
    /** @test */
    public function PVA12_eliminar_retornaTrue(): void
    {
        $resultado = $this->proveedor->eliminar(1);
        $this->assertTrue($resultado);
    }
}


// ════════════════════════════════════════════════════════════════════
//  Usuario — CRUD completo
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
        $resultado = $this->usuario->crear('Carlos Rivas', 'carlos@test.com', 'SinNumeros', 3, '');
        $this->assertIsString($resultado);
        $this->assertStringContainsString('contrase', strtolower($resultado));
    }

    // USA-02 · crear — datos válidos con FakePDO no lanza excepción
    /** @test */
    public function USA02_crearConFakePDO_noLanzaExcepcion(): void
    {
        // FakePDO::fetch() devuelve false → email no existe → INSERT
        $resultado = $this->usuario->crear('Luis Paz', 'luis@test.com', 'Admin123', 3, '');
        $this->assertTrue($resultado === true || is_string($resultado));
    }

    // USA-03 · obtener — no lanza excepción con FakePDO
    /** @test */
    public function USA03_obtener_noLanzaExcepcion(): void
    {
        $excepcion = null;
        try {
            $this->usuario->obtener();
        } catch (\Throwable $e) {
            $excepcion = $e;
        }
        $this->assertNull($excepcion, 'obtener() no debe lanzar excepción');
    }

    // USA-04 · obtenerPorId — retorna false con FakePDO
    /** @test */
    public function USA04_obtenerPorId_retornaFalseConFakePDO(): void
    {
        $resultado = $this->usuario->obtenerPorId(99);
        $this->assertFalse($resultado);
    }

    // USA-05 · login — email vacío retorna false
    /** @test */
    public function USA05_loginEmailVacio_retornaFalse(): void
    {
        $resultado = $this->usuario->login('', 'Admin123');
        $this->assertFalse($resultado);
    }

    // USA-06 · login — credenciales incorrectas retorna false
    /** @test */
    public function USA06_loginCredencialesIncorrectas_retornaFalse(): void
    {
        $resultado = $this->usuario->login('noexiste@test.com', 'WrongPass1');
        $this->assertFalse($resultado);
    }

    // USA-07 · actualizar — nombre vacío retorna error
    /** @test */
    public function USA07_actualizar_nombreVacio_retornaError(): void
    {
        $resultado = $this->usuario->actualizar(1, '', 'valido@test.com', 3);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    // USA-08 · actualizar — email inválido retorna error
    /** @test */
    public function USA08_actualizar_emailInvalido_retornaError(): void
    {
        $resultado = $this->usuario->actualizar(1, 'Ana Torres', 'no-es-email', 3);
        $this->assertIsString($resultado);
        $this->assertStringContainsString('correo', strtolower($resultado));
    }

    // USA-09 · actualizar — datos válidos con FakePDO retorna true
    /** @test */
    public function USA09_actualizar_datosValidos_retornaTrue(): void
    {
        // FakePDO::fetch() devuelve false → correo no duplicado → UPDATE
        $resultado = $this->usuario->actualizar(1, 'Ana Torres', 'ana@test.com', 3);
        $this->assertTrue($resultado);
    }

    // USA-10 · eliminar — retorna true con FakePDO
    /** @test */
    public function USA10_eliminar_retornaTrue(): void
    {
        $resultado = $this->usuario->eliminar(1);
        $this->assertTrue($resultado);
    }

    // USA-11 · actualizarPassword — retorna true con FakePDO
    /** @test */
    public function USA11_actualizarPassword_retornaTrue(): void
    {
        $hash = password_hash('NuevaClave1', PASSWORD_BCRYPT);
        $resultado = $this->usuario->actualizarPassword(1, $hash);
        $this->assertTrue($resultado);
    }

    // USA-12 · obtenerConPassword — retorna false con FakePDO
    /** @test */
    public function USA12_obtenerConPassword_retornaFalse(): void
    {
        $resultado = $this->usuario->obtenerConPassword(99);
        $this->assertFalse($resultado);
    }
}