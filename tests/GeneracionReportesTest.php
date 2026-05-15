<?php
/**
 * GeneracionReportesTest – ChocoTumac
 *
 * Suite de pruebas unitarias del modelo Reporte.
 * Valida el contrato de interfaz de HU-07: generación de reportes
 * de ventas, compras, inventario y KPIs generales del negocio.
 *
 * Cubre:
 *   - Reporte de ventas: sin filtros, con filtros de fecha, cliente y búsqueda
 *   - Totales de ventas con claves KPI requeridas
 *   - Reporte de compras: sin filtros, con filtros de proveedor y búsqueda
 *   - Totales de compras con claves KPI requeridas
 *   - Reporte de inventario: sin filtros y con búsqueda
 *   - Productos más vendidos
 *   - Listas de clientes y proveedores
 *   - Resumen general con los 8 KPIs del negocio
 *
 * Nota: estas pruebas verifican el contrato del modelo (tipos retornados
 * y claves presentes), no los valores reales (que dependen de datos en BD).
 *
 * Patrón:  AAA (Arrange – Act – Assert)
 * Tipo:    Contrato de interfaz con FakePDO
 * Runner:  PHPUnit 9
 *
 * @package ChocoTumac\Tests
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestHelper.php';

class GeneracionReportesTest extends TestCase
{
    use TestHelper;

    /** @var Reporte Instancia del modelo bajo prueba */
    private Reporte $reporte;

    protected function setUp(): void
    {
        $this->reporte = new Reporte();
        $this->inyectarPropiedad($this->reporte, 'conn', new FakePDO());
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 1 – Reporte de ventas
    // ══════════════════════════════════════════════════════════════════

    /**
     * RE-01: El reporte de ventas sin filtros debe retornar un array.
     *
     * @test
     */
    public function RE01_ventas_sinFiltros_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->ventas();

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * RE-02: El reporte de ventas acepta filtros de fecha sin lanzar excepciones.
     *
     * @test
     */
    public function RE02_ventas_conFiltroFecha_retornaArray(): void
    {
        // Arrange
        $desde = '2026-01-01';
        $hasta = '2026-12-31';

        // Act
        $resultado = $this->reporte->ventas($desde, $hasta);

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * RE-03: El reporte de ventas acepta filtro de cliente_id.
     *
     * @test
     */
    public function RE03_ventas_conClienteId_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->ventas(null, null, 1, null);

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * RE-04: El reporte de ventas acepta búsqueda de texto.
     *
     * @test
     */
    public function RE04_ventas_conBusquedaTexto_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->ventas(null, null, null, 'Cacao');

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * RE-05: El reporte de ventas acepta todos los filtros combinados.
     *
     * @test
     */
    public function RE05_ventas_conTodosLosFiltros_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->ventas('2026-01-01', '2026-12-31', 2, 'Cacao');

        // Assert
        $this->assertIsArray($resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 2 – Totales de ventas
    // ══════════════════════════════════════════════════════════════════

    /**
     * RE-06: Los totales de ventas deben retornar un array con las claves KPI requeridas.
     * Estas claves son la base para los dashboards y reportes ejecutivos.
     *
     * @test
     */
    public function RE06_totalesVentas_retornaArrayConClavesKPI(): void
    {
        // Act
        $resultado = $this->reporte->totalesVentas();

        // Assert
        $this->assertIsArray($resultado);
        $clavesRequeridas = [
            'transacciones_totales',
            'subtotal_suma',
            'suma_iva',
            'suma_total',
            'promedio_venta',
            'venta_maxima',
        ];
        foreach ($clavesRequeridas as $clave) {
            $this->assertArrayHasKey($clave, $resultado, "Falta la clave KPI: $clave");
        }
    }

    /**
     * RE-07: Los totales de ventas aceptan filtro de cliente_id.
     *
     * @test
     */
    public function RE07_totalesVentas_conClienteId_retornaArrayConClaves(): void
    {
        // Act
        $resultado = $this->reporte->totalesVentas(null, null, 1);

        // Assert
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('transacciones_totales', $resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 3 – Reporte de compras
    // ══════════════════════════════════════════════════════════════════

    /**
     * RE-08: El reporte de compras sin filtros debe retornar un array.
     *
     * @test
     */
    public function RE08_compras_sinFiltros_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->compras();

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * RE-09: El reporte de compras acepta filtro de proveedor_id.
     *
     * @test
     */
    public function RE09_compras_conProveedorId_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->compras(null, null, 1);

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * RE-10: El reporte de compras acepta búsqueda de texto.
     *
     * @test
     */
    public function RE10_compras_conBusquedaTexto_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->compras(null, null, null, 'Tumaco');

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * RE-11: El reporte de compras acepta todos los filtros combinados.
     *
     * @test
     */
    public function RE11_compras_conTodosLosFiltros_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->compras('2026-01-01', '2026-06-30', 1, 'Cacao');

        // Assert
        $this->assertIsArray($resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 4 – Totales de compras
    // ══════════════════════════════════════════════════════════════════

    /**
     * RE-12: Los totales de compras deben retornar un array con las claves KPI requeridas.
     *
     * @test
     */
    public function RE12_totalesCompras_retornaArrayConClavesKPI(): void
    {
        // Act
        $resultado = $this->reporte->totalesCompras();

        // Assert
        $this->assertIsArray($resultado);
        $clavesRequeridas = [
            'transacciones_totales',
            'suma_total',
            'promedio_compra',
            'compra_maxima',
        ];
        foreach ($clavesRequeridas as $clave) {
            $this->assertArrayHasKey($clave, $resultado, "Falta la clave KPI: $clave");
        }
    }

    /**
     * RE-13: Los totales de compras aceptan todos los filtros.
     *
     * @test
     */
    public function RE13_totalesCompras_conFiltros_retornaArrayConClaves(): void
    {
        // Act
        $resultado = $this->reporte->totalesCompras('2026-01-01', '2026-12-31', 1, 'Cacao');

        // Assert
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('transacciones_totales', $resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 5 – Reporte de inventario
    // ══════════════════════════════════════════════════════════════════

    /**
     * RE-14: El reporte de inventario sin filtros debe retornar un array.
     *
     * @test
     */
    public function RE14_inventario_sinFiltros_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->inventario();

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * RE-15: El reporte de inventario acepta búsqueda por nombre de producto.
     *
     * @test
     */
    public function RE15_inventario_conBusquedaNombre_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->inventario('Cacao Grano');

        // Assert
        $this->assertIsArray($resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 6 – Reportes adicionales
    // ══════════════════════════════════════════════════════════════════

    /**
     * RE-16: El reporte de productos más vendidos debe retornar un array.
     *
     * @test
     */
    public function RE16_productosMasVendidos_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->productosMasVendidos();

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * RE-17: La lista de clientes para reportes debe retornar un array.
     *
     * @test
     */
    public function RE17_listaClientes_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->listaClientes();

        // Assert
        $this->assertIsArray($resultado);
    }

    /**
     * RE-18: La lista de proveedores para reportes debe retornar un array.
     *
     * @test
     */
    public function RE18_listaProveedores_retornaArray(): void
    {
        // Act
        $resultado = $this->reporte->listaProveedores();

        // Assert
        $this->assertIsArray($resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 7 – Resumen ejecutivo (KPIs del negocio)
    // ══════════════════════════════════════════════════════════════════

    /**
     * RE-19: El resumen general debe retornar exactamente los 8 KPIs del negocio:
     *   ventas_totales, ingresos_total, total_compras, egresos_total,
     *   clientes_activos, productos_activos, stock_bajo, sin_stock.
     *
     * @test
     */
    public function RE19_resumenGeneral_retornaOchoKPIsDelNegocio(): void
    {
        // Act
        $resultado = $this->reporte->resumenGeneral();

        // Assert
        $this->assertIsArray($resultado);
        $kpisRequeridos = [
            'ventas_totales',
            'ingresos_total',
            'total_compras',
            'egresos_total',
            'clientes_activos',
            'productos_activos',
            'stock_bajo',
            'sin_stock',
        ];
        foreach ($kpisRequeridos as $kpi) {
            $this->assertArrayHasKey($kpi, $resultado, "Falta el KPI del negocio: $kpi");
        }
    }
}
