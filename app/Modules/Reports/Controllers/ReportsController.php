<?php

declare(strict_types=1);

namespace App\Modules\Reports\Controllers;

use App\Core\Database\Connection;
use App\Modules\Reports\Services\ReportsService;

/**
 * Контроллер модуля «Отчётность».
 *
 * URL:
 *   ?module=reports&period=week&date_type=delivery&dimension=fo&chart_metric=requests
 *   ?module=reports&period=custom&date_from=...&date_to=...&dimension=region
 *
 * Старые URL (совместимость):
 *   ?module=reports&report=delivered_fo       → dimension=fo
 *   ?module=reports&report=delivered_regions  → dimension=region
 *   ?module=reports&report=status_summary     → dimension=status
 */
class ReportsController
{
    private ReportsService $service;

    public function __construct()
    {
        $this->service = new ReportsService();
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

        $period      = $filters['period'];
        $dateType    = $filters['date_type'];
        $dimension   = $filters['dimension'];
        $chartMetric = $filters['chart_metric'];
        $dateFrom    = $filters['date_from'];
        $dateTo      = $filters['date_to'];
        $warning     = $filters['warning'];

        $data = $this->service->buildReports($period, $dateType, $dimension, $dateFrom, $dateTo);
        $chartData = $this->service->buildChartData($data, $dimension, $chartMetric);

        ob_start();
        $period      = $period;
        $dateType    = $dateType;
        $dimension   = $dimension;
        $chartMetric = $chartMetric;
        $dateFrom    = $dateFrom;
        $dateTo      = $dateTo;
        $warning     = $warning;
        $data        = $data;
        $summary     = $data['summary'];
        $rows        = $data['rows'];
        $unmatched   = $data['unmatched'];
        $chartData   = $chartData;
        $service     = $this->service;
        require __DIR__ . '/../../../Views/reports/index.php';
        $content = ob_get_clean();

        ob_start();
        $title      = 'Отчётность — Demo ERP';
        $pageTitle  = 'Отчётность';
        $pageModule = 'reports';
        require __DIR__ . '/../../../Views/layouts/main.php';
        return ob_get_clean();
    }

    private function renderNoDb(): string
    {
        ob_start();
        $title      = 'Отчётность — Demo ERP';
        $pageTitle  = 'Отчётность';
        $pageModule = 'reports';
        ?>
        <div class="page-head">
            <div class="page-head-left">
                <div class="page-title">Отчётность</div>
                <div class="page-summary"><span>Сводные отчёты по заявкам, статусам, регионам и федеральным округам</span></div>
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
