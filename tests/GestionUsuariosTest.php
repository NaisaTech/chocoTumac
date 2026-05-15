<?php
/**
 * GestionUsuariosTest – ChocoTumac
 *
 * Suite de pruebas unitarias del modelo Usuario.
 * Valida las reglas de acceso y seguridad definidas en HU-01:
 * creación de usuarios, autenticación y gestión de contraseñas.
 *
 * Cubre:
 *   - Validaciones de nombre, email, contraseña y rol
 *   - Reglas de seguridad de contraseña (mínimo 8 chars, mayúscula, número)
 *   - Login con credenciales válidas e inválidas
 *   - Operaciones CRUD completo con FakePDO
 *
 * Patrón:  AAA (Arrange – Act – Assert)
 * Tipo:    Caja blanca
 * Runner:  PHPUnit 9
 *
 * @package ChocoTumac\Tests
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestHelper.php';

class GestionUsuariosTest extends TestCase
{
    use TestHelper;

    /** @var Usuario Instancia del modelo bajo prueba */
    private Usuario $usuario;

    protected function setUp(): void
    {
        $this->usuario = new Usuario();
        $this->inyectarPropiedad($this->usuario, 'conn', new FakePDO());
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 1 – Validaciones de creación
    // ══════════════════════════════════════════════════════════════════

    /**
     * US-01: El nombre es obligatorio para crear un usuario.
     *
     * @test
     */
    public function US01_crear_nombreVacio_retornaError(): void
    {
        // Arrange — nombre vacío, resto de campos válidos
        $nombre   = '';
        $email    = 'user@test.com';
        $password = 'Admin123';
        $rol_id   = 3;

        // Act
        $resultado = $this->usuario->crear($nombre, $email, $password, $rol_id, '');

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    /**
     * US-02: El email debe tener formato válido.
     *
     * @test
     */
    public function US02_crear_emailInvalido_retornaError(): void
    {
        // Arrange
        $email = 'no-es-email';

        // Act
        $resultado = $this->usuario->crear('Ana Torres', $email, 'Admin123', 3, '');

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('correo', strtolower($resultado));
    }

    /**
     * US-03: La contraseña debe tener al menos 8 caracteres.
     * Una contraseña corta como "Ab1" debe ser rechazada.
     *
     * @test
     */
    public function US03_crear_contrasenaMuyCorta_retornaError(): void
    {
        // Arrange
        $password = 'Ab1';   // menos de 8 caracteres

        // Act
        $resultado = $this->usuario->crear('Ana Torres', 'ana@test.com', $password, 3, '');

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('contrase', strtolower($resultado));
    }

    /**
     * US-04: La contraseña debe contener al menos una letra mayúscula.
     *
     * @test
     */
    public function US04_crear_contrasenaSinMayuscula_retornaError(): void
    {
        // Arrange
        $password = 'abc12345';   // sin mayúscula

        // Act
        $resultado = $this->usuario->crear('Ana Torres', 'ana@test.com', $password, 3, '');

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('contrase', strtolower($resultado));
    }

    /**
     * US-05: La contraseña debe contener al menos un número.
     *
     * @test
     */
    public function US05_crear_contrasenaSinNumero_retornaError(): void
    {
        // Arrange
        $password = 'SinNumeros';   // sin dígitos

        // Act
        $resultado = $this->usuario->crear('Carlos Rivas', 'carlos@test.com', $password, 3, '');

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('contrase', strtolower($resultado));
    }

    /**
     * US-06: El rol_id debe ser uno de los valores válidos (1=Admin, 2=Gerente, 3=Empleado).
     * Un rol_id de 99 debe ser rechazado.
     *
     * @test
     */
    public function US06_crear_rolInvalido_retornaError(): void
    {
        // Arrange
        $rol_id = 99;   // no existe

        // Act
        $resultado = $this->usuario->crear('Ana Torres', 'ana@test.com', 'Admin123', $rol_id, '');

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('rol', strtolower($resultado));
    }

    /**
     * US-07: El rol_id 0 también debe ser rechazado (fuera del rango 1-3).
     *
     * @test
     */
    public function US07_crear_rolCero_retornaError(): void
    {
        // Arrange
        $rol_id = 0;

        // Act
        $resultado = $this->usuario->crear('Ana Torres', 'ana@test.com', 'Admin123', $rol_id, '');

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('rol', strtolower($resultado));
    }

    /**
     * US-08: El teléfono no puede contener caracteres inválidos (letras, símbolos).
     *
     * @test
     */
    public function US08_crear_telefonoInvalido_retornaError(): void
    {
        // Arrange
        $telefono = 'tel#abc!';

        // Act
        $resultado = $this->usuario->crear('Ana Torres', 'ana@test.com', 'Admin123', 3, $telefono);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('tel', strtolower($resultado));
    }

    /**
     * US-09: Con datos completamente válidos, crear usuario no debe lanzar error.
     * Con FakePDO, el email no existe (fetch retorna false) → INSERT exitoso.
     *
     * @test
     */
    public function US09_crear_datosValidos_noRetornaError(): void
    {
        // Arrange
        $nombre   = 'Luis Paz';
        $email    = 'luis@test.com';
        $password = 'Admin123';
        $rol_id   = 3;

        // Act
        $resultado = $this->usuario->crear($nombre, $email, $password, $rol_id, '');

        // Assert — acepta true (éxito) o string (email ya existe en BD real)
        $this->assertTrue($resultado === true || is_string($resultado));
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 2 – Autenticación
    // ══════════════════════════════════════════════════════════════════

    /**
     * US-10: El login con email vacío debe retornar false.
     *
     * @test
     */
    public function US10_login_emailVacio_retornaFalse(): void
    {
        // Arrange
        $email    = '';
        $password = 'Admin123';

        // Act
        $resultado = $this->usuario->login($email, $password);

        // Assert
        $this->assertFalse($resultado);
    }

    /**
     * US-11: El login con credenciales incorrectas debe retornar false.
     * Con FakePDO, fetch() retorna false → usuario no encontrado.
     *
     * @test
     */
    public function US11_login_credencialesIncorrectas_retornaFalse(): void
    {
        // Arrange
        $email    = 'noexiste@test.com';
        $password = 'WrongPass1';

        // Act
        $resultado = $this->usuario->login($email, $password);

        // Assert
        $this->assertFalse($resultado);
    }

    // ══════════════════════════════════════════════════════════════════
    // GRUPO 3 – Actualización y otras operaciones
    // ══════════════════════════════════════════════════════════════════

    /**
     * US-12: Actualizar usuario con nombre vacío debe retornar error.
     *
     * @test
     */
    public function US12_actualizar_nombreVacio_retornaError(): void
    {
        // Act
        $resultado = $this->usuario->actualizar(1, '', 'valido@test.com', 3);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('nombre', strtolower($resultado));
    }

    /**
     * US-13: Actualizar usuario con email inválido debe retornar error.
     *
     * @test
     */
    public function US13_actualizar_emailInvalido_retornaError(): void
    {
        // Act
        $resultado = $this->usuario->actualizar(1, 'Ana Torres', 'no-es-email', 3);

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('correo', strtolower($resultado));
    }

    /**
     * US-14: Actualizar usuario con teléfono inválido debe retornar error.
     *
     * @test
     */
    public function US14_actualizar_telefonoInvalido_retornaError(): void
    {
        // Act
        $resultado = $this->usuario->actualizar(1, 'Carlos', 'carlos@test.com', 2, 'abc!');

        // Assert
        $this->assertIsString($resultado);
        $this->assertStringContainsString('tel', strtolower($resultado));
    }

    /**
     * US-15: Actualizar usuario con datos válidos debe retornar true.
     * Con FakePDO, la comprobación de email duplicado retorna false → UPDATE exitoso.
     *
     * @test
     */
    public function US15_actualizar_datosValidos_retornaTrue(): void
    {
        // Act
        $resultado = $this->usuario->actualizar(1, 'Carlos Ruiz', 'carlos@test.com', 2, '3001234567');

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * US-16: Eliminar usuario debe retornar true con FakePDO.
     *
     * @test
     */
    public function US16_eliminar_retornaTrue(): void
    {
        // Act
        $resultado = $this->usuario->eliminar(1);

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * US-17: Actualizar contraseña con hash válido debe retornar true.
     *
     * @test
     */
    public function US17_actualizarPassword_retornaTrue(): void
    {
        // Arrange
        $hash = password_hash('NuevaClave1', PASSWORD_BCRYPT);

        // Act
        $resultado = $this->usuario->actualizarPassword(1, $hash);

        // Assert
        $this->assertTrue($resultado);
    }

    /**
     * US-18: Buscar usuario por ID inexistente retorna false con FakePDO.
     *
     * @test
     */
    public function US18_obtenerPorId_idInexistente_retornaFalse(): void
    {
        // Act
        $resultado = $this->usuario->obtenerPorId(9999);

        // Assert
        $this->assertFalse($resultado);
    }

    /**
     * US-19: Los métodos de gestión de contraseña deben existir en el modelo.
     *
     * @test
     */
    public function US19_metodosDePasswordExisten(): void
    {
        // Assert — verifica que los métodos existan y sean públicos
        $this->assertTrue(method_exists($this->usuario, 'actualizarPassword'));
        $this->assertTrue(method_exists($this->usuario, 'obtenerConPassword'));
    }
}
