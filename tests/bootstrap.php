<?php
/**
 * Bootstrap de pruebas – ChocoTumac
 *
 * Ejecutado por PHPUnit antes de cualquier test.
 * Define stubs mínimos para que los modelos puedan instanciarse
 * sin una conexión MySQL real, permitiendo probar la lógica
 * de validación de forma completamente aislada (unit tests puros).
 */

// ── Rutas base ────────────────────────────────────────────────────
define('CHOCOTUMAC_ROOT', dirname(__DIR__));
define('CHOCOTUMAC_APP',  true);

// ── Stub: PDOStatement ────────────────────────────────────────────
/**
 * Simula PDOStatement para que los modelos no fallen al llamar
 * prepare() / execute() / fetch() en tests de validación pura.
 */
class FakePDOStatement {
    public function execute(array $params = []): bool { return true; }
    public function fetch(int $mode = PDO::FETCH_ASSOC)      { return false; }
    public function fetchAll(int $mode = PDO::FETCH_ASSOC): array { return []; }
    public function fetchColumn(int $col = 0) { return 0; }
    public function rowCount(): int { return 0; }
}

// ── Stub: PDO ────────────────────────────────────────────────────
/**
 * Simula PDO para aislar los tests de la base de datos real.
 * Solo se usan en pruebas de validación; los tests de integración
 * deben apuntar a una BD de prueba real.
 */
class FakePDO extends PDO {
    public function __construct() {}   // Evita que PDO conecte
    public function prepare(string $sql, array $options = []): FakePDOStatement {
        return new FakePDOStatement();
    }
    public function query(string $sql, ?int $fetchMode = null, mixed ...$args): FakePDOStatement {
        return new FakePDOStatement();
    }
    public function lastInsertId(?string $name = null): string { return '1'; }
    public function beginTransaction(): bool { return true; }
    public function commit(): bool          { return true; }
    public function rollBack(): bool        { return true; }
}

// ── Stub: Database ───────────────────────────────────────────────
/**
 * Reemplaza la clase Database real para que los modelos reciban
 * un FakePDO en lugar de intentar conectar a MySQL.
 */
class Database {
    public function connect(): FakePDO {
        return new FakePDO();
    }
}

// ── Autoload de modelos ───────────────────────────────────────────
$modelos = [
    'Cliente', 'Proveedor', 'Producto', 'Compra', 'Venta', 'Usuario', 'Reporte'
];
foreach ($modelos as $modelo) {
    $path = CHOCOTUMAC_ROOT . "/models/{$modelo}.php";
    if (file_exists($path)) {
        require_once $path;
    }
}