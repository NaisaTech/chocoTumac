<?php
/**
 * Bootstrap de pruebas – ChocoTumac
 *
 * Ejecutado por PHPUnit antes de cualquier test.
 * Define stubs mínimos para que los modelos puedan instanciarse
 * sin una conexión MySQL real, permitiendo probar la lógica
 * de validación de forma completamente aislada (unit tests puros).
 */

define('CHOCOTUMAC_ROOT', dirname(__DIR__));
define('CHOCOTUMAC_APP',  true);

// ── Stub: FakePDOStatement ────────────────────────────────────────
// Nombre distinto para evitar conflicto con PDOStatement nativo
class FakePDOStatement
{
    public function execute(array $params = []): bool    { return true; }
    public function fetch(int $mode = PDO::FETCH_ASSOC)  { return false; }
    public function fetchAll(int $mode = PDO::FETCH_ASSOC): array { return []; }
    public function fetchColumn(int $col = 0)            { return 0; }
    public function rowCount(): int                      { return 0; }
}

// ── Stub: FakePDO ────────────────────────────────────────────────
class FakePDO extends PDO
{
    public function __construct() {}
    public function prepare(string $sql, array $options = []): FakePDOStatement
    {
        return new FakePDOStatement();
    }
    public function query(string $sql, ?int $fetchMode = null, mixed ...$args): FakePDOStatement
    {
        return new FakePDOStatement();
    }
    public function lastInsertId(?string $name = null): string { return '1'; }
    public function beginTransaction(): bool { return true; }
    public function commit(): bool           { return true; }
    public function rollBack(): bool         { return true; }
}

// ── Stub: Database ───────────────────────────────────────────────
// Usamos class_exists para evitar "Cannot declare class Database,
// because the name is already in use" cuando config/database.php
// ya fue cargado por algún modelo via require_once.
if (!class_exists('Database', false)) {
    class Database
    {
        public function connect(): FakePDO
        {
            return new FakePDO();
        }
    }
}

// ── Autoload de modelos ───────────────────────────────────────────
// Cargamos DESPUÉS de definir el stub de Database para que el
// require_once dentro de cada modelo encuentre la clase ya definida
// y no intente incluir config/database.php (que tiene la real).
$modelos = ['Cliente', 'Proveedor', 'Producto', 'Compra', 'Venta', 'Usuario', 'Reporte'];

foreach ($modelos as $modelo) {
    $path = CHOCOTUMAC_ROOT . "/models/{$modelo}.php";
    if (file_exists($path)) {
        require_once $path;
    }
}