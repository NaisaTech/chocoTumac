<?php
/**
 * ReporteTest – ChocoTumac
 *
 * Pruebas automatizadas del modelo Reporte.
 * Cubre HU-7: Generación de Reportes.
 *
 * Patrón: AAA  |  Tipo: caja blanca + integración parcial
 * Runner: PHPUnit ^11
 *
 * Nota: las pruebas que dependen de datos reales requieren la BD
 * de prueba configurada. Las pruebas de interfaz/contrato verifican
 * que los métodos existan y retornen el tipo correcto con FakePDO.
 *
 * @package ChocoTumac\Tests
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

require_once __DIR__ . '/TestHelper.php';

class ReporteTest extends TestCase
{
    use TestHelper;

    private Reporte $reporte;

    protected function setUp(): void
    {
        $this->reporte = new Reporte();
        $this->inyectarPropiedad($this->reporte, 'conn', new FakePDO());
    }

    // RE-01 · ventas() retorna array con FakePDO
    #[Test]
    public function RE01_ventas_sinFiltros_retornaArray(): void
    {
        // Arrange — FakePDO::fetchAll() devuelve []
        // Act
        $resultado = $this->reporte->ventas();

        // Assert
        $this->assertIsArray($resultado);
    }

    // RE-02 · ventas() acepta filtros de fecha sin error
    #[Test]
    public function RE02_ventas_conFiltroFecha_noLanzaExcepcion(): void
    {
        // Arrange
        $desde = '2026-01-01';
        $hasta = '2026-12-31';

        // Act & Assert
        $this->assertIsArray($this->reporte->ventas($desde, $hasta));
    }

    // RE-03 · ventas() acepta búsqueda de texto sin error
    #[Test]
    public function RE03_ventas_conBusqueda_noLanzaExcepcion(): void
    {
        // Arrange
        $busqueda = 'Cacao';

        // Act & Assert
        $this->assertIsArray($this->reporte->ventas(null, null, null, $busqueda));
    }

    // RE-04 · totalesVentas() retorna array con claves esperadas
    #[Test]
    public function RE04_totalesVentas_retornaArrayConClaves(): void
    {
        // Arrange — FakePDO::fetchColumn() devuelve 0
        // Act
        $resultado = $this->reporte->totalesVentas();

        // Assert
        $this->assertIsArray($resultado);
        $claves = ['transacciones_totales', 'subtotal_suma', 'suma_iva', 'suma_total', 'promedio_venta', 'venta_maxima'];
        foreach ($claves as $clave) {
            $this->assertArrayHasKey($clave, $resultado, "Falta clave: $clave");
        }
    }

    // RE-05 · compras() retorna array sin errores
    #[Test]
    public function RE05_compras_sinFiltros_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->compras();

        // Assert
        $this->assertIsArray($resultado);
    }

    // RE-06 · compras() acepta filtro de proveedor
    #[Test]
    public function RE06_compras_conProveedorId_noLanzaExcepcion(): void
    {
        // Arrange
        $proveedor_id = 1;

        // Act & Assert
        $this->assertIsArray($this->reporte->compras(null, null, $proveedor_id));
    }

    // RE-07 · totalesCompras() retorna array con claves esperadas
    #[Test]
    public function RE07_totalesCompras_retornaArrayConClaves(): void
    {
        // Act
        $resultado = $this->reporte->totalesCompras();

        // Assert
        $this->assertIsArray($resultado);
        $claves = ['transacciones_totales', 'suma_total', 'promedio_compra', 'compra_maxima'];
        foreach ($claves as $clave) {
            $this->assertArrayHasKey($clave, $resultado, "Falta clave: $clave");
        }
    }

    // RE-08 · inventario() retorna array sin errores
    #[Test]
    public function RE08_inventario_sinFiltros_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->inventario();

        // Assert
        $this->assertIsArray($resultado);
    }

    // RE-09 · inventario() acepta búsqueda de texto
    #[Test]
    public function RE09_inventario_conBusqueda_noLanzaExcepcion(): void
    {
        // Arrange
        $busqueda = 'Fino';

        // Act & Assert
        $this->assertIsArray($this->reporte->inventario($busqueda));
    }

    // RE-10 · productosMasVendidos() retorna array
    #[Test]
    public function RE10_productosMasVendidos_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->productosMasVendidos();

        // Assert
        $this->assertIsArray($resultado);
    }

    // RE-11 · listaClientes() retorna array
    #[Test]
    public function RE11_listaClientes_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->listaClientes();

        // Assert
        $this->assertIsArray($resultado);
    }

    // RE-12 · listaProveedores() retorna array
    #[Test]
    public function RE12_listaProveedores_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->listaProveedores();

        // Assert
        $this->assertIsArray($resultado);
    }

    // RE-13 · resumenGeneral() retorna array con 8 claves KPI
    #[Test]
    public function RE13_resumenGeneral_retornaArrayConOchoClaves(): void
    {
        // Act
        $resultado = $this->reporte->resumenGeneral();

        // Assert
        $this->assertIsArray($resultado);
        $claves = [
            'ventas_totales', 'ingresos_total',
            'total_compras',  'egresos_total',
            'clientes_activos', 'productos_activos',
            'stock_bajo',     'sin_stock',
        ];
        foreach ($claves as $clave) {
            $this->assertArrayHasKey($clave, $resultado, "Falta KPI: $clave");
        }
    }
}