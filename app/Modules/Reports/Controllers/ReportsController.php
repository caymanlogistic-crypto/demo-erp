<?php

declare(strict_types=1);

namespace App\Modules\Reports\Controllers;

use App\Core\Database\Connection;
use App\Modules\Reports\Services\ReportsService;

/**
 * Контроллер модуля «Отчётность».
 *
 * Новый URL:
 *   ?module=reports&period=month&date_type=delivery&dimension=fo&chart_metric=requests
 *
 * Старый URL (совместимость):
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
        $period      = $this->service->normalizePeriod($_GET['period'] ?? 'month');
        $dateType    = $this->service->normalizeDateType($_GET['date_type'] ?? 'delivery');
        $chartMetric = $this->service->normalizeChartMetric($_GET['chart_metric'] ?? 'requests');

        // Обратная совместимость: report= → dimension=
        $dimension = $this->resolveDimension();

        $data = $this->service->buildReports($period, $dateType, $dimension);
        $chartData = $this->service->buildChartData($data['rows'], $dimension, $chartMetric);

        // Внедряем dimension и chart_metric в результат
        $data['dimension']    = $dimension;
        $data['date_type']    = $dateType;
        $data['chart_metric'] = $chartMetric;

        ob_start();
        $reportData = $data;
        $chartData  = $chartData;
        $period     = $period;
        $dateType   = $dateType;
        $dimension  = $dimension;
        $chartMetric = $chartMetric;
        $service    = $this->service;
        require __DIR__ . '/../../../Views/reports/index.php';
        $content = ob_get_clean();

        ob_start();
        $title      = 'Отчётность — Demo ERP';
        $pageTitle  = 'Отчётность';
        $pageModule = 'reports';
        require __DIR__ . '/../../../Views/layouts/main.php';
        return ob_get_clean();
    }

    /**
     * Определить dimension из URL с приоритетом:
     *   1. dimension=... (новый параметр)
     *   2. report=delivered_regions → region
     *   3. report=status_summary → status
     *   4. report=delivered_fo → fo (default)
     */
    private function resolveDimension(): string
    {
        // Новый параметр имеет приоритет
        if (isset($_GET['dimension'])) {
            return $this->service->normalizeDimension($_GET['dimension']);
        }

        // Старая совместимость
        $report = $_GET['report'] ?? '';
        return match ($report) {
            'delivered_regions' => 'region',
            'status_summary'    => 'status',
            default             => 'fo',
        };
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
