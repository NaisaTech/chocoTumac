<?php
/**
 * Trait TestHelper – ChocoTumac
 *
 * Provee métodos utilitarios para las clases de prueba:
 *   - Invocar métodos privados/protegidos via Reflection (caja blanca)
 *   - Inyectar propiedades privadas (como $conn) con un stub
 *
 * Patrón AAA: las clases de test usan este trait para el paso
 * Arrange cuando necesitan acceso a la lógica interna del modelo.
 */
trait TestHelper
{
    /**
     * Invoca un método privado o protegido de un objeto.
     *
     * @param object $obj    Instancia del objeto bajo prueba.
     * @param string $method Nombre del método privado.
     * @param array  $args   Argumentos a pasar al método.
     * @return mixed         Resultado del método.
     */
    protected function invocarPrivado(object $obj, string $method, array $args = []): mixed
    {
        $ref = new ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($obj, $args);
    }

    /**
     * Inyecta un valor en una propiedad privada o protegida.
     *
     * @param object $obj      Instancia del objeto.
     * @param string $property Nombre de la propiedad.
     * @param mixed  $value    Valor a inyectar.
     */
    protected function inyectarPropiedad(object $obj, string $property, mixed $value): void
    {
        $ref = new ReflectionProperty($obj, $property);
        $ref->setAccessible(true);
        $ref->setValue($obj, $value);
    }

    /**
     * Crea datos base válidos de venta para reutilizar en tests.
     *
     * @return array
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

    /**
     * Crea datos base válidos de compra para reutilizar en tests.
     *
     * @return array
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
     * Crea datos base válidos de cliente para reutilizar en tests.
     *
     * @return array
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
     * Crea datos base válidos de proveedor para reutilizar en tests.
     *
     * @return array
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
}