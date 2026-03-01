<?php

namespace App\Core;

use PDO;

/**
 * Singleton PDO — remplace la fonction globale getPDO().
 * Lecture des constantes de config (DB_HOST, DB_NAME, DB_USER, DB_PASS)
 * définies dans includes/config.php.
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            // S'assure que config.php est chargé (pour les constantes DB_*)
            if (!defined('DB_HOST')) {
                require_once dirname(__DIR__, 2) . '/includes/config.php';
            }

            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                DB_HOST,
                DB_NAME
            );

            self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$instance;
    }
}
