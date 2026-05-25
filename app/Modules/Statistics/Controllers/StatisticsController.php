<?php

declare(strict_types=1);

namespace App\Modules\Statistics\Controllers;

use App\Core\Database\Connection;
use App\Modules\Statistics\Services\StatisticsService;

class StatisticsController
{
    private StatisticsService $service;

    public function __construct()
    {
        $this->service = new StatisticsService();
    }

    public function index(): string
    {
        if (!Connection::isAvailable()) {
            return $this->renderNoDb();
        }

        return $this->renderIndex();
    }

    private function renderIndex(): string
    {
        $filters = $this->service->normalizeFilters();

        $data = $this->service->buildStatistics(
            $filters['period'],
            $filters['date_from'],
            $filters['date_to']
        );

        ob_start();
        $period    = $filters['period'];
        $dateFrom  = $filters['date_from'];
        $dateTo    = $filters['date_to'];
        $warning   = $filters['warning'];
        $summary   = $data['summary'];
        $rows      = $data['rows'];
        $service   = $this->service;
        require __DIR__ . '/../../../Views/statistics/index.php';
        $content = ob_get_clean();

        ob_start();
        $title      = 'Статистика вывозов — Demo ERP';
        $pageTitle  = 'Статистика вывозов';
        $pageModule = 'statistics';
        require __DIR__ . '/../../../Views/layouts/main.php';
        return ob_get_clean();
    }

    private function renderNoDb(): string
    {
        ob_start();
        $title      = 'Статистика вывозов — Demo ERP';
        $pageTitle  = 'Статистика вывозов';
        $pageModule = 'statistics';
        ?>
        <div class="page-head">
            <div class="page-head-left">
                <div class="page-eyebrow">Статистика / Вывозы по периодам</div>
                <div class="page-title">Статистика вывозов</div>
                <div class="page-summary"><span>Количество заявок и масса по завершённым рейсам</span></div>
            </div>
        </div>
        <div class="form-alert alert-warning" style="margin: 0;">
            <strong>База данных не подключена.</strong>
            Проверьте параметры .env (DB_HOST, DB_NAME, DB_USER, DB_PASS).
        </div>
        <?php
        $content = ob_get_clean();

        ob_start();
        require __DIR__ . '/../../../Views/layouts/main.php';
        return ob_get_clean();
    }
}
