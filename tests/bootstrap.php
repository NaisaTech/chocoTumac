<?php
/**
 * Bootstrap de pruebas – ChocoTumac
 */

define('CHOCOTUMAC_ROOT', dirname(__DIR__));
define('CHOCOTUMAC_APP',  true);

// ── Stub: FakePDOStatement ────────────────────────────────────────
class FakePDOStatement
{
    private string $sql;

    public function __construct(string $sql = '')
    {
        $this->sql = strtolower($sql);
    }

    public function execute(array $params = []): bool { return true; }

    /**
     * Devuelve datos según el contexto de la query:
     * - Queries de totales (COUNT/SUM/AVG/MAX) → array con claves esperadas
     * - Cualquier otra query (obtenerPorId, etc.) → false (sin resultado)
     *   Esto hace que !$prod sea true y validarCantidadPorUnidad() retorne true
     *   sin intentar acceder a $prod['unidad'].
     */
    public function fetch(int $mode = PDO::FETCH_ASSOC): array|false
    {
        $esTotales = str_contains($this->sql, 'count(')
                  || str_contains($this->sql, 'sum(')
                  || str_contains($this->sql, 'avg(')
                  || str_contains($this->sql, 'max(');

        if (!$esTotales) {
            return false;   // simula "no encontrado" para obtenerPorId, etc.
        }

        // Devuelve todas las claves posibles que los tests RE04 y RE07 verifican
        return [
            'transacciones_totales' => 0,
            'subtotal_suma'         => 0,
            'suma_iva'              => 0,
            'suma_total'            => 0,
            'promedio_venta'        => 0,
            'venta_maxima'          => 0,
            'venta_minima'          => 0,
            'promedio_compra'       => 0,
            'compra_maxima'         => 0,
            'compra_minima'         => 0,
        ];
    }

    public function fetchAll(int $mode = PDO::FETCH_ASSOC): array { return []; }
    public function fetchColumn(int $col = 0): int                { return 0; }
    public function rowCount(): int                               { return 0; }
}

// ── Stub: FakePDO ────────────────────────────────────────────────
class FakePDO extends PDO
{
    public function __construct() {}

    public function prepare(string $sql, array $options = []): FakePDOStatement
    {
        return new FakePDOStatement($sql);
    }

    public function query(string $sql, ?int $fetchMode = null, mixed ...$args): FakePDOStatement
    {
        return new FakePDOStatement($sql);
    }

    public function lastInsertId(?string $name = null): string { return '1'; }
    public function beginTransaction(): bool { return true; }
    public function commit(): bool           { return true; }
    public function rollBack(): bool         { return true; }
}

// ── Stub: Database ───────────────────────────────────────────────
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
$modelos = ['Cliente', 'Proveedor', 'Producto', 'Compra', 'Venta', 'Usuario', 'Reporte'];

foreach ($modelos as $modelo) {
    $path = CHOCOTUMAC_ROOT . "/models/{$modelo}.php";
    if (file_exists($path)) {
        require_once $path;
    }
}