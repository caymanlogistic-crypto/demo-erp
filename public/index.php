<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$rootPath = dirname(__DIR__);

if (file_exists($rootPath . '/.env')) {
    $dotenv = Dotenv::createImmutable($rootPath);
    $dotenv->safeLoad();
}

date_default_timezone_set('Europe/Warsaw');

/**
 * Простая маршрутизация через query-параметр ?module=...
 *
 * Позже можно заменить на полноценный роутер.
 */

$module = $_GET['module'] ?? 'home';
$action = $_GET['action'] ?? 'index';

$allowedModules = ['home', 'feo'];

if (!in_array($module, $allowedModules, true)) {
    $module = 'home';
}

try {
    switch ($module) {
        case 'feo':
            $controller = new \App\Modules\Feo\Controllers\FeoController();
            if ($action === 'list') {
                $controller->list();
                exit;
            }
            echo $controller->index();
            break;

        case 'home':
        default:
            echo 'Demo ERP работает';
            break;
    }
} catch (\Throwable $e) {
    error_log('Fatal error in index.php: ' . $e->getMessage());
    if ($_ENV['APP_DEBUG'] ?? false) {
        echo '<h1>Ошибка</h1><pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        echo '<h1>Внутренняя ошибка сервера</h1><p>Попробуйте позже или обратитесь к администратору.</p>';
    }
}
