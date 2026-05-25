<?php

declare(strict_types=1);

namespace App\Modules\Reports\Controllers;

use App\Core\Database\Connection;
use App\Modules\Reports\Services\ReportsService;

/**
 * Контроллер модуля «Отчётность».
 *
 * URL:
 *   ?module=reports                        → default = delivered_fo
 *   ?module=reports&report=delivered_fo
 *   ?module=reports&report=delivered_regions
 *   ?module=reports&report=status_summary
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
        $report = $this->normalizeReport($_GET['report'] ?? 'delivered_fo');

        $data = match ($report) {
            'delivered_regions' => $this->service->buildDeliveredByRegions(),
            'status_summary'    => $this->service->buildStatusSummary(),
            default             => $this->service->buildDeliveredByFO(),
        };

        // Добавляем заголовки отчёта
        $reportTitle = match ($report) {
            'delivered_fo'       => 'Сдано по федеральным округам',
            'delivered_regions'  => 'Сдано по регионам',
            'status_summary'     => 'Сводка по статусам',
            default              => 'Сдано по федеральным округам',
        };

        $data['report_title'] = $reportTitle;
        $data['active_report'] = $report;
        $data['service'] = $this->service;

        ob_start();
        $reportData = $data;
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
                <div class="page-eyebrow">Отчётность</div>
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

    private function normalizeReport(string $report): string
    {
        $allowed = ['delivered_fo', 'delivered_regions', 'status_summary'];
        return in_array($report, $allowed, true) ? $report : 'delivered_fo';
    }
}
