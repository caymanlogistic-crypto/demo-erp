<?php

declare(strict_types=1);

namespace App\Modules\Flights\Controllers;

use App\Core\Database\Connection;
use App\Modules\Flights\Repositories\FlightsRepository;
use App\Modules\Flights\Services\FlightsTimelineService;

class FlightsController
{
    private FlightsRepository $repository;
    private FlightsTimelineService $service;

    public function __construct()
    {
        $this->repository = new FlightsRepository();
        $this->service    = new FlightsTimelineService();
    }

    /**
     * Главный action: HTML-страница «Таймлайн рейсов».
     */
    public function index(): string
    {
        if (!Connection::isAvailable()) {
            return $this->renderNoDb();
        }

        return $this->renderIndex();
    }

    private function renderIndex(): string
    {
        // Таб
        $allowedTabs = ['all', 'planned', 'in_transit', 'unloaded', 'unassigned'];
        $tab = $_GET['tab'] ?? 'all';
        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'all';
        }

        // Данные
        $statuses  = $this->repository->getStatuses();
        $tabCounts = $this->repository->getTabCounts();
        $result    = $this->repository->getFlights($tab);
        $flights   = $this->service->enrichFlights($result['flights']);

        // Status map для отображения
        $statusMap = [];
        foreach ($statuses as $s) {
            $statusMap[$s['статус']] = $s;
        }

        // Capture flights/index.php
        ob_start();
        $data = [
            'tab'        => $tab,
            'tabCounts'  => $tabCounts,
            'flights'    => $flights,
            'statusMap'  => $statusMap,
            'service'    => $this->service,
        ];
        require __DIR__ . '/../../../Views/flights/index.php';
        $content = ob_get_clean();

        // Render inside layout
        ob_start();
        $title = 'Таймлайн рейсов — Demo ERP';
        $pageTitle = 'Таймлайн рейсов';
        $pageModule = 'flights';
        require __DIR__ . '/../../../Views/layouts/main.php';
        return ob_get_clean();
    }

    private function renderNoDb(): string
    {
        ob_start();
        $title = 'Таймлайн рейсов — Demo ERP';
        $pageTitle = 'Таймлайн рейсов';
        $pageModule = 'flights';
        ?>
        <div class="page-head">
            <div class="page-head-left">
                <div class="page-eyebrow">Рейсы / Таймлайн рейсов</div>
                <div class="page-title">Таймлайн рейсов</div>
                <div class="page-summary"><span>Операционная лента рейсов · read-only</span></div>
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
