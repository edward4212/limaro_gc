<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Singleton de conexión PDO a MySQL/MariaDB.
 * Toda la aplicación usa esta misma instancia.
 */
class Database
{
    /** @var PDO|null Instancia única */
    private static ?PDO $instance = null;

    /**
     * Obtener la instancia PDO (crea conexión la primera vez).
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require APP_ROOT . '/config/database.php';

            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                if (APP_DEBUG) {
                    throw $e;
                }
                http_response_code(500);
                die('Error de conexión a la base de datos. Contacte al administrador.');
            }
        }

        return self::$instance;
    }

    /** Evitar instanciación directa */
    private function __construct() {}
    private function __clone() {}
}
