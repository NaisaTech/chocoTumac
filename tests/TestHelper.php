<?php
/**
 * Trait TestHelper – ChocoTumac
 *
 * Provee métodos utilitarios reutilizables en toda la suite:
 *   - invocarPrivado(): accede a métodos privados/protegidos via Reflection
 *   - inyectarPropiedad(): inyecta dependencias (como FakePDO) via Reflection
 *   - Fábricas de datos válidos por modelo (DRY: evita repetir arrays en cada test)
 *
 * Patrón AAA: estas utilidades se usan en la fase Arrange de cada prueba.
 *
 * @package ChocoTumac\Tests
 */
trait TestHelper
{
    /**
     * Invoca un método privado o protegido de un objeto.
     * Permite hacer pruebas de caja blanca sobre la lógica interna
     * sin exponer los métodos en la interfaz pública del modelo.
     *
     * @param object $obj    Instancia del modelo bajo prueba
     * @param string $method Nombre del método privado a invocar
     * @param array  $args   Argumentos para el método
     * @return mixed         Resultado devuelto por el método
     */
    protected function invocarPrivado(object $obj, string $method, array $args = []): mixed
    {
        $ref = new ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($obj, $args);
    }

    /**
     * Inyecta un valor en una propiedad privada o protegida.
     * Se usa para sustituir $conn con FakePDO o $modelProducto con un mock.
     *
     * @param object $obj      Instancia del modelo
     * @param string $property Nombre de la propiedad a reemplazar
     * @param mixed  $value    Valor a inyectar (FakePDO, mock, etc.)
     */
    protected function inyectarPropiedad(object $obj, string $property, mixed $value): void
    {
        $ref = new ReflectionProperty($obj, $property);
        $ref->setAccessible(true);
        $ref->setValue($obj, $value);
    }

    // ── Fábricas de datos válidos ─────────────────────────────────────────

    /**
     * Datos mínimos válidos para crear o validar un Cliente.
     * Tipo CC (no requiere dígito de verificación).
     */
    protected function datosClienteValidos(): array
    {
        return [
            'nombre'       => 'Maria López',
            'tipo_doc'     => 'CC',
            'num_doc'      => '987654321',
            'digito_ver'   => '',
            'telefono'     => '3001234567',
            'email'        => 'maria@ejemplo.com',
            'direccion'    => 'Calle 10 # 5-20',
            'ciudad'       => 'Tumaco',
            'departamento' => 'Nariño',
        ];
    }

    /**
     * Datos mínimos válidos para crear o validar un Proveedor.
     * Tipo NIT con dígito de verificación.
     */
    protected function datosProveedorValidos(): array
    {
        return [
            'nombre'           => 'Cacao Sur S.A.S.',
            'tipo_doc'         => 'NIT',
            'num_doc'          => '900111222',
            'digito_ver'       => '3',
            'tipo_proveedor'   => 'Cooperativa',
            'persona_contacto' => 'Juan Mena',
            'telefono'         => '3112223344',
            'email'            => 'contacto@cacaosur.co',
            'direccion'        => 'Vía principal km 3',
            'ciudad'           => 'Tumaco',
            'departamento'     => 'Nariño',
        ];
    }

    /**
     * Datos mínimos válidos para crear o validar una Compra.
     * Producto 1 (cacao en grano), 50 unidades a $8.500.
     */
    protected function datosCompraValidos(): array
    {
        return [
            'proveedor_id'    => '1',
            'producto_id'     => '1',
            'fecha'           => date('Y-m-d'),
            'cantidad'        => '50',
            'precio_unitario' => '8500',
            'observaciones'   => '',
        ];
    }

    /**
     * Datos mínimos válidos para crear o validar una Venta.
     * Cliente registrado, producto 1, 5 unidades a $15.000.
     */
    protected function datosVentaValidos(): array
    {
        return [
            'tipo_cliente'    => 'registrado',
            'cliente_id'      => '1',
            'producto_id'     => '1',
            'fecha'           => date('Y-m-d'),
            'cantidad'        => '5',
            'precio_unitario' => '15000',
            'iva_porcentaje'  => '19',
            'forma_pago'      => 'contado',
        ];
    }
}
