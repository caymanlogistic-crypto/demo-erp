<?php

declare(strict_types=1);

namespace App\Modules\Feo\Controllers;

use App\Core\Database\Connection;
use App\Modules\Feo\Repositories\FeoRepository;
use App\Modules\Feo\Services\FeoFilterService;

class FeoController
{
    private FeoRepository $repository;
    private FeoFilterService $filterService;

    public function __construct()
    {
        $this->repository    = new FeoRepository();
        $this->filterService = new FeoFilterService($this->repository);
    }

    /**
     * Главный action: HTML-страница списка заявок.
     */
    public function index(): string
    {
        if (!Connection::isAvailable()) {
            return $this->renderNoDb();
        }

        $filterType = $_GET['filter'] ?? 'all';
        $allowedFilters = ['all', 'available', 'routes', 'flights'];
        if (!in_array($filterType, $allowedFilters, true)) {
            $filterType = 'all';
        }

        $numbers = $_GET['numbers'] ?? '';
        $page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        try {
            $data = $this->filterService->execute($numbers, $filterType, $page, 50);
        } catch (\Exception $e) {
            error_log('FeoController::index error: ' . $e->getMessage());
            return $this->renderError('Ошибка при получении данных: ' . $e->getMessage());
        }

        return $this->renderIndex($data);
    }

    /**
     * AJAX/JSON-выдача списка заявок.
     */
    public function list(): void
    {
        if (!Connection::isAvailable()) {
            $this->jsonResponse(['error' => 'База данных не подключена. Проверьте параметры .env.'], 503);
            return;
        }

        $filterType = $_GET['filter'] ?? 'all';
        $allowedFilters = ['all', 'available', 'routes', 'flights'];
        if (!in_array($filterType, $allowedFilters, true)) {
            $filterType = 'all';
        }

        $numbers = $_GET['numbers'] ?? '';
        $page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        try {
            $data = $this->filterService->execute($numbers, $filterType, $page, 50);
        } catch (\Exception $e) {
            error_log('FeoController::list error: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Ошибка при получении данных.'], 500);
            return;
        }

        // Передаём строки как есть — все JOIN уже сделаны в репозитории
        $this->jsonResponse([
            'rows'          => $data['rows'],
            'total'         => $data['total'],
            'page'          => $data['page'],
            'totalPages'    => $data['totalPages'],
            'foundZayavki'   => $data['foundZayavki'],
            'missingZayavki' => $data['missingZayavki'],
        ]);
    }

    private function renderIndex(array $data): string
    {
        ob_start();
        require __DIR__ . '/../../../Views/feo/index.php';
        return ob_get_clean();
    }

    private function renderNoDb(): string
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Заявки ФЭО — Demo ERP</title>
        </head>
        <body>
            <h1>Заявки ФЭО</h1>
            <div style="padding: 2rem; background: #fff3cd; border: 1px solid #ffc107; color: #856404; border-radius: 4px; margin: 2rem 0;">
                <strong>База данных не подключена.</strong><br>
                Проверьте параметры .env (DB_HOST, DB_NAME, DB_USER, DB_PASS).<br>
                Функционал ФЭО требует подключения к БД.
            </div>
            <p><a href="/">← На главную</a></p>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private function renderError(string $message): string
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Ошибка — Заявки ФЭО</title>
        </head>
        <body>
            <h1>Заявки ФЭО</h1>
            <div style="padding: 2rem; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px; margin: 2rem 0;">
                <strong>Ошибка:</strong> <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <p><a href="/">← На главную</a></p>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}