<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;

    /**
     * Получить PDO-соединение.
     *
     * При отсутствии .env или ошибке подключения возвращает null.
     */
    public static function get(): ?PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = $_ENV['DB_HOST'] ?? null;
        $port = $_ENV['DB_PORT'] ?? '3306';
        $name = $_ENV['DB_NAME'] ?? null;
        $user = $_ENV['DB_USER'] ?? null;
        $pass = $_ENV['DB_PASS'] ?? '';

        if (empty($host) || empty($name) || empty($user)) {
            return null;
        }

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            return self::$instance;
        } catch (PDOException $e) {
            // Логируем, но не падаем
            error_log('DB connection error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Проверить, доступно ли соединение.
     */
    public static function isAvailable(): bool
    {
        return self::get() !== null;
    }
}