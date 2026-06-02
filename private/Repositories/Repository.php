<?php
/**
 * ============================================================================
 * BASE REPOSITORY
 * ============================================================================
 *
 * Clase base para todos los repositories.
 * Proporciona acceso a PDO y utilidades comunes.
 *
 * Los repositories en Dedumsoft siguen la convencion:
 *   - Solo invocan funciones PL/pgSQL (fun_*)
 *   - NO contienen logica de negocio
 *   - NO contienen SQL directo (salvo SELECT fun_*(...))
 *   - Devuelven arrays o booleanos
 *
 * @package Dedumsoft\Repositories
 */

abstract class Repository
{
    /** @var PDO */
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Ejecuta una funcion PL/pgSQL que retorna BOOLEAN (C/U/D).
     *
     * @param string $sql    SELECT fun_name(:param1, ...)
     * @param array  $params Parametros nombrados
     * @return bool
     */
    protected function execBool(string $sql, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Ejecuta una funcion PL/pgSQL que retorna JSON (lista).
     *
     * @param string $sql    SELECT fun_name(...) AS data
     * @param array  $params Parametros nombrados
     * @return array<int, array<string, mixed>>
     */
    protected function execJsonList(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $json = $row['data'] ?? '[]';
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Ejecuta una funcion PL/pgSQL que retorna JSON (single row).
     *
     * @param string $sql    SELECT fun_name(...) AS data
     * @param array  $params Parametros nombrados
     * @return array<string, mixed>|null
     */
    protected function execJsonOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['data'] === null) {
            return null;
        }
        $decoded = json_decode($row['data'], true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Ejecuta una funcion PL/pgSQL que retorna un SETOF (tabla).
     *
     * @param string $sql    SELECT col1, col2 FROM fun_name(...)
     * @param array  $params Parametros nombrados
     * @return array<int, array<string, mixed>>
     */
    protected function execSet(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
