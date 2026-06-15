<?php

namespace App\Models;

use App\Core\Model;

/**
 * ModuloModel
 *
 * Encapsula todas las operaciones de BD sobre la tabla `modulo`,
 * eliminando el SQL inline que existía en ModuloController (SEC-001).
 */
class ModuloModel extends Model
{
    protected string $table      = 'modulo';
    protected string $primaryKey = 'id_modulo';

    /**
     * Listado completo con nombre de padre y abuelo (para la vista de administración).
     */
    public function listarConJerarquia(): array
    {
        return $this->query("
            SELECT m.*, p.nombre AS padre_nombre, pp.nombre AS abuelo_nombre
            FROM modulo m
            LEFT JOIN modulo p  ON p.id_modulo  = m.id_padre
            LEFT JOIN modulo pp ON pp.id_modulo = p.id_padre
            ORDER BY COALESCE(pp.orden, p.orden, m.orden), m.id_padre, m.orden
        ")->fetchAll();
    }

    /**
     * Módulos activos para construir el árbol de jerarquía,
     * excluyendo opcionalmente un nodo (al editar, para evitar ciclos).
     */
    public function activosParaJerarquia(int $excluirId = 0): array
    {
        if ($excluirId) {
            return $this->query(
                "SELECT id_modulo, nombre, id_padre
                   FROM modulo
                  WHERE estado = 'ACTIVO' AND id_modulo <> ?
                  ORDER BY id_padre, nombre",
                [$excluirId]
            )->fetchAll();
        }
        return $this->query(
            "SELECT id_modulo, nombre, id_padre
               FROM modulo
              WHERE estado = 'ACTIVO'
              ORDER BY id_padre, nombre"
        )->fetchAll();
    }

    /**
     * Buscar módulo por PK con prepared statement (corrige SEC-001).
     * Sobrescribe el find() del Model base para documentar explícitamente.
     */
    public function porId(int $id): ?array
    {
        return $this->query(
            "SELECT * FROM modulo WHERE id_modulo = ? LIMIT 1",
            [$id]
        )->fetch() ?: null;
    }

    /**
     * Insertar nuevo módulo y dar ver=1 al rol ADMINISTRADOR automáticamente.
     *
     * @return int  id del nuevo módulo
     */
    public function crear(
        string  $codigo,
        string  $nombre,
        string  $icono,
        ?string $url,
        ?int    $idPadre,
        int     $orden,
        string  $estado = 'ACTIVO'
    ): int {
        $this->query(
            "INSERT INTO modulo (codigo, nombre, icono, url, id_padre, orden, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$codigo, $nombre, $icono, $url, $idPadre, $orden, $estado]
        );
        $id = (int) \App\Core\Database::getInstance()->lastInsertId();

        // Dar ver=1 al ADMINISTRADOR en el nuevo módulo
        $this->query(
            "INSERT IGNORE INTO rol_modulo (id_rol, id_modulo, ver, crear, editar, eliminar)
             SELECT id_rol, ?, 1, 0, 0, 0 FROM rol WHERE rol = 'ADMINISTRADOR'",
            [$id]
        );

        return $id;
    }

    /**
     * Actualizar módulo existente.
     */
    public function actualizar(
        int     $id,
        string  $codigo,
        string  $nombre,
        string  $icono,
        ?string $url,
        ?int    $idPadre,
        int     $orden,
        string  $estado
    ): void {
        $this->query(
            "UPDATE modulo SET codigo=?, nombre=?, icono=?, url=?, id_padre=?, orden=?, estado=?
              WHERE id_modulo=?",
            [$codigo, $nombre, $icono, $url, $idPadre, $orden, $estado, $id]
        );
    }

    /**
     * Construye el árbol de jerarquía desde un array plano de módulos.
     * Lógica extraída del helper privado del controlador.
     *
     * @param array $modulos  Resultado de activosParaJerarquia()
     * @return array  Raíces con sus hijos y nietos anidados
     */
    public function construirArbol(array $modulos): array
    {
        $idx = [];
        foreach ($modulos as $m) {
            $idx[$m['id_modulo']] = $m + ['hijos' => []];
        }

        $roots = [];
        foreach ($modulos as $m) {
            $id  = $m['id_modulo'];
            $pid = $m['id_padre'];
            if ($pid === null) {
                $roots[] = &$idx[$id];
            } elseif (isset($idx[$pid])) {
                $idx[$pid]['hijos'][] = &$idx[$id];
            }
        }
        return $roots;
    }

    /**
     * Mapa URL → código de módulo para el PermisoMiddleware.
     * Ordenado de más específico a más genérico (LENGTH DESC) para
     * que el middleware resuelva correctamente rutas anidadas.
     */
    public function mapaUrlCodigo(): array
    {
        $rows = $this->query(
            "SELECT url, codigo FROM modulo
              WHERE url IS NOT NULL AND url != '' AND estado = 'ACTIVO'
              ORDER BY LENGTH(url) DESC"
        )->fetchAll();

        $mapa = [];
        foreach ($rows as $r) {
            $mapa[$r['url']] = $r['codigo'];
        }
        return $mapa;
    }

}