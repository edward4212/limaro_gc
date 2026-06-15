<?php

namespace App\Core;

use PDO;
use PDOStatement;

/**
 * Modelo base — provee acceso a PDO y métodos CRUD genéricos.
 * Todos los modelos extienden esta clase.
 */
abstract class Model
{
    /** @var PDO Instancia de base de datos */
    protected PDO $db;

    /** @var string Nombre de la tabla */
    protected string $table = '';

    /** @var string Clave primaria */
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // -------------------------------------------------------------------
    // Consultas base
    // -------------------------------------------------------------------

    /**
     * Preparar y ejecutar una sentencia SQL.
     *
     * @param string $sql    SQL con placeholders
     * @param array  $params Valores para bindear
     */
    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Obtener todos los registros de la tabla.
     */
    public function all(string $orderBy = ''): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        return $this->query($sql)->fetchAll();
    }

    /**
     * Buscar por clave primaria.
     */
    public function find(int|string $id): ?array
    {
        $sql  = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1";
        $row  = $this->query($sql, [$id])->fetch();
        return $row ?: null;
    }

    /**
     * Insertar un registro.
     *
     * @param array $data Columna => valor
     * @return int ID del registro insertado
     */
    public function insert(array $data): int
    {
        $columns     = implode('`, `', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$this->table}` (`{$columns}`) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualizar registro por clave primaria.
     */
    public function update(int|string $id, array $data): bool
    {
        $set = implode(' = ?, ', array_map(fn($c) => "`$c`", array_keys($data))) . ' = ?';
        $sql = "UPDATE `{$this->table}` SET {$set} WHERE `{$this->primaryKey}` = ?";
        $params = array_merge(array_values($data), [$id]);
        return $this->query($sql, $params)->rowCount() > 0;
    }

    /**
     * Eliminar registro por clave primaria.
     */
    public function delete(int|string $id): bool
    {
        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?";
        return $this->query($sql, [$id])->rowCount() > 0;
    }

    /**
     * Contar registros con filtro opcional.
     */
    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        if ($where) {
            $sql .= " WHERE $where";
        }
        return (int) $this->query($sql, $params)->fetchColumn();
    }

    /**
     * Llamar a un stored procedure.
     *
     * @param string $proc   Nombre del SP
     * @param array  $params Parámetros posicionales
     */
    protected function callSP(string $proc, array $params = []): PDOStatement
    {
        $placeholders = implode(', ', array_fill(0, count($params), '?'));
        $sql = "CALL {$proc}({$placeholders})";
        return $this->query($sql, $params);
    }

    /**
     * Iniciar transacción.
     */

    // -------------------------------------------------------------------
    // Paginación (PERF-001)
    // -------------------------------------------------------------------

    /**
     * Ejecuta una query paginada y devuelve los datos + metadatos de paginación.
     *
     * Uso en un modelo hijo:
     *   return $this->paginar(
     *       "SELECT * FROM solicitud WHERE estado_solicitud = ?",
     *       ['CREADA'], $pagina, 50
     *   );
     *
     * @param string $sql       SQL completo SIN LIMIT ni OFFSET
     * @param array  $params    Parámetros de la query principal
     * @param int    $pagina    Página solicitada (base 1)
     * @param int    $porPagina Filas por página (default 50, máx 200)
     * @return array{
     *     data: array,
     *     total: int,
     *     pagina: int,
     *     por_pagina: int,
     *     total_paginas: int,
     *     tiene_anterior: bool,
     *     tiene_siguiente: bool
     * }
     */
    protected function paginar(
        string $sql,
        array  $params    = [],
        int    $pagina    = 1,
        int    $porPagina = 50
    ): array {
        $pagina    = max(1, $pagina);
        $porPagina = min(200, max(1, $porPagina));
        $offset    = ($pagina - 1) * $porPagina;

        // COUNT sin ORDER BY (optimización: evitar filesort en el conteo)
        $sqlCount = preg_replace('/ORDER\s+BY.*/is', '', $sql);
        $total    = (int) $this->query(
            "SELECT COUNT(*) FROM ({$sqlCount}) AS _pag_count",
            $params
        )->fetchColumn();

        $data = $this->query(
            "{$sql} LIMIT {$porPagina} OFFSET {$offset}",
            $params
        )->fetchAll();

        $totalPaginas = $total > 0 ? (int) ceil($total / $porPagina) : 1;

        return [
            'data'            => $data,
            'total'           => $total,
            'pagina'          => $pagina,
            'por_pagina'      => $porPagina,
            'total_paginas'   => $totalPaginas,
            'tiene_anterior'  => $pagina > 1,
            'tiene_siguiente' => $pagina < $totalPaginas,
        ];
    }

    /**
     * Helper: extrae el número de página del request GET (?pagina=N).
     */
    public static function paginaActual(int $default = 1): int
    {
        return max(1, (int)($_GET['pagina'] ?? $default));
    }

    protected function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /**
     * Confirmar transacción.
     */
    protected function commit(): void
    {
        $this->db->commit();
    }

    /**
     * Revertir transacción.
     */
    protected function rollback(): void
    {
        $this->db->rollBack();
    }
}
