<?php

declare(strict_types=1);

namespace App\Modules\Feo\Controllers;

use App\Core\Database\Connection;
use App\Modules\Feo\Repositories\FeoRepository;
use App\Modules\Feo\Services\FeoFilterService;
use App\Modules\Feo\Support\FeoStatusResolver;

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
     * Главный action: HTML-страница «Загрузка данных FEO».
     */
    public function index(): string
    {
        if (!Connection::isAvailable()) {
            return $this->renderNoDb();
        }

        return $this->renderIndex();
    }

    /**
     * AJAX/JSON-выдача списка заявок (HTML строки таблицы).
     *
     * GET-параметры (как в index22.php):
     *   ajax          = get_zayavki
     *   filter_zayavki = строка номеров
     *   show_only_available = '1' / '0'
     *   show_only_marshrut  = '1' / '0'
     *   show_only_flight    = '1' / '0'
     *   offset, limit
     */
    public function list(): void
    {
        if (!Connection::isAvailable()) {
            $this->jsonResponse(['success' => false, 'message' => 'База данных не подключена. Проверьте параметры .env.'], 503);
            return;
        }

        $offset             = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $limit              = isset($_GET['limit']) ? min(100, max(10, (int) $_GET['limit'])) : 50;
        $filterZayavki      = isset($_GET['filter_zayavki']) ? trim($_GET['filter_zayavki']) : '';
        $contentSearch      = isset($_GET['content_search']) ? trim($_GET['content_search']) : '';
        $showOnlyAvailable  = isset($_GET['show_only_available']) && $_GET['show_only_available'] === '1';
        $showOnlyMarshrut   = isset($_GET['show_only_marshrut']) && $_GET['show_only_marshrut'] === '1';
        $showOnlyFlight     = isset($_GET['show_only_flight']) && $_GET['show_only_flight'] === '1';

        $hasFilter = !empty($filterZayavki);

        try {
            $data = $this->filterService->execute(
                $filterZayavki,
                $showOnlyAvailable,
                $showOnlyMarshrut,
                $showOnlyFlight,
                $offset,
                $limit,
                $contentSearch
            );
        } catch (\Exception $e) {
            error_log('FeoController::list error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
            return;
        }

        // Рендерим HTML строки
        $html = $this->renderRows(
            $data['rows'],
            $data['availableBlockMap'],
            $data['marshrutMap'],
            $data['flightMap'],
            $data['flightDetailsMap'],
            $data['statusMap'],
            $data['priceMap'],
            $data['pricePerKgMap']
        );

        $zayavkaIds = $data['zayavkaIds'];

        $this->jsonResponse([
            'success'            => true,
            'html'               => $html,
            'total'              => $data['total'],
            'status_counts'      => $data['statusCounts'],
            'offset'             => $data['offset'],
            'limit'              => $data['limit'],
            'has_more'           => $data['hasMore'],
            'has_filter'         => $hasFilter,
            'filter_count'       => count($zayavkaIds),
            'found_zayavki'      => $data['foundZayavki'],
            'missing_zayavki'    => array_values($data['missingZayavki']),
            'show_only_available' => $showOnlyAvailable,
            'show_only_marshrut'  => $showOnlyMarshrut,
            'show_only_flight'    => $showOnlyFlight,
            '_checkboxEmpty'      => $data['_checkboxEmpty'] ?? false,
        ]);
    }

    /**
     * Рендеринг HTML-строк таблицы (точная копия логики index22.php строки 334–395).
     *
     * @param array $rows
     * @param array<string, string> $availableBlockMap  zayavka_id → block_id (dostupno)
     * @param array<string, string> $marshrutMap        zayavka_id → block_id (marshrut)
     * @param array<string, string> $flightMap          zayavka_id → flight_id
     * @param array<string, array>  $flightDetailsMap   flight_id → детали рейса
     * @param array<string, array>  $statusMap          статус → данные из таблицы status
     * @param array<string, float>  $priceMap           zayavka_id → стоимость
     * @param array<string, float>  $pricePerKgMap      zayavka_id → цена за кг
     */
    private function renderRows(
        array $rows,
        array $availableBlockMap,
        array $marshrutMap,
        array $flightMap,
        array $flightDetailsMap,
        array $statusMap,
        array $priceMap,
        array $pricePerKgMap
    ): string {
        $html = '';

        foreach ($rows as $row) {
            $zayavkaIdRaw = $row['zayavka_id'] ?? '';
            $zayavkaId = (string) $zayavkaIdRaw;

            // Доступно — нейтральный ref-chip
            $availableBlockIdRaw = $availableBlockMap[$zayavkaId] ?? null;
            $availableBlockId = $availableBlockIdRaw !== null ? (string) $availableBlockIdRaw : '';
            $availableDisplay = $availableBlockId !== ''
                ? '<span class="ref-chip ref-available">Блок #' . htmlspecialchars($availableBlockId, ENT_QUOTES, 'UTF-8') . '</span>'
                : '<span class="empty-cell">—</span>';

            // Маршрут — нейтральный ref-chip
            $marshrutIdRaw = $marshrutMap[$zayavkaId] ?? null;
            $marshrutId = $marshrutIdRaw !== null ? (string) $marshrutIdRaw : '';
            $marshrutDisplay = $marshrutId !== ''
                ? '<span class="ref-chip ref-route">М#' . htmlspecialchars($marshrutId, ENT_QUOTES, 'UTF-8') . '</span>'
                : '<span class="empty-cell">—</span>';

            // Рейс — нейтральный ref-chip
            $flightIdRaw = $flightMap[$zayavkaId] ?? null;
            $flightIdStr = $flightIdRaw !== null ? (string) $flightIdRaw : '';
            $flightDisplay = $flightIdStr !== ''
                ? '<span class="ref-chip ref-flight">Р#' . htmlspecialchars($flightIdStr, ENT_QUOTES, 'UTF-8') . '</span>'
                : '<span class="empty-cell">—</span>';

            // Масса, кг (mass_netto в тоннах → умножаем на 1000)
            $massKg = isset($row['mass_netto']) && $row['mass_netto'] !== '' && $row['mass_netto'] !== null
                ? floatval($row['mass_netto']) * 1000
                : null;
            $massDisplay = $massKg !== null ? number_format($massKg, 0, '.', ' ') : '';

            // Статус рейса — TransportERP flight-status pattern
            $statusDisplay = '<span style="color: var(--text-faint);">—</span>';
            if ($flightIdRaw !== null && isset($flightDetailsMap[$flightIdRaw])) {
                $flightStatus = $flightDetailsMap[$flightIdRaw]['status'];
                $statusData = $statusMap[$flightStatus] ?? null;
                if ($statusData) {
                    $styleClass = ltrim($statusData['style'] ?? '', '.');
                    $statusDisplay = '<span class="flight-status ' . htmlspecialchars($styleClass, ENT_QUOTES, 'UTF-8') . '">'
                        . '<span class="flight-status-dot"></span>'
                        . htmlspecialchars($statusData['наименование'] ?? $flightStatus, ENT_QUOTES, 'UTF-8')
                        . '</span>';
                } else {
                    $statusDisplay = '<span class="flight-status status-completed">'
                        . '<span class="flight-status-dot"></span>'
                        . htmlspecialchars($flightStatus, ENT_QUOTES, 'UTF-8')
                        . '</span>';
                }
            }

            // Даты рейса — split на ДАТА С и ДАТА ПО
            $dateFrom = '—';
            $dateTo   = '—';
            if ($flightIdRaw !== null && isset($flightDetailsMap[$flightIdRaw])) {
                $datesFull = FeoStatusResolver::formatFlightDates($flightDetailsMap[$flightIdRaw]);
                $split     = $this->splitDateRange($datesFull);
                $dateFrom  = $split['date_from'];
                $dateTo    = $split['date_to'];
            }

            // Стоимость
            $costDisplay = '<span class="empty-cell">—</span>';
            $zayavkaKeyRaw = $row['zayavka_id'] ?? '';
            if ($zayavkaKeyRaw !== '' && isset($priceMap[$zayavkaKeyRaw])) {
                $costDisplay = number_format($priceMap[$zayavkaKeyRaw], 0, '.', ' ') . ' ₽';
            }

            // ₽/КГ
            $pricePerKgDisplay = '<span class="empty-cell">—</span>';
            if ($zayavkaKeyRaw !== '' && isset($pricePerKgMap[$zayavkaKeyRaw])) {
                $pricePerKgDisplay = number_format($pricePerKgMap[$zayavkaKeyRaw], 2, '.', ' ');
            }

            // Экранируем текстовые значения для title
            $regionVal    = (string) ($row['mno_region'] ?? '');
            $moVal        = (string) ($row['mno_mo'] ?? '');
            $senderVal    = (string) ($row['naim_oo_gruzootpravitel'] ?? '');
            $addressVal   = (string) ($row['mno_adres_pogruzki'] ?? '');

            $html .= '<tr data-id="' . htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8') . '"'
                  . ' data-zayavka-id="' . htmlspecialchars($zayavkaId, ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<td class="cell-num">' . htmlspecialchars((string) ($row['zayavka_id'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td class="cell-num">' . $massDisplay . '</td>';
            $html .= '<td class="cell-ellipsis cell-region" title="' . htmlspecialchars($regionVal, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($regionVal, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td class="cell-ellipsis cell-mo" title="' . htmlspecialchars($moVal, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($moVal, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td class="cell-ellipsis cell-sender" title="' . htmlspecialchars($senderVal, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($senderVal, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td class="cell-ellipsis cell-address" title="' . htmlspecialchars($addressVal, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($addressVal, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . $availableDisplay . '</td>';
            $html .= '<td>' . $marshrutDisplay . '</td>';
            $html .= '<td>' . $flightDisplay . '</td>';
            $html .= '<td>' . $statusDisplay . '</td>';
            $html .= '<td class="cell-date">' . htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td class="cell-date">' . htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td class="cell-money">' . $costDisplay . '</td>';
            $html .= '<td class="cell-money">' . $pricePerKgDisplay . '</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    private function renderIndex(): string
    {
        // Capture feo/index.php content
        ob_start();
        require __DIR__ . '/../../../Views/feo/index.php';
        $content = ob_get_clean();

        // Render inside layout
        ob_start();
        $title = 'Загрузка данных FEO — Demo ERP';
        $pageTitle = 'Заявки ФЭО';
        require __DIR__ . '/../../../Views/layouts/main.php';
        return ob_get_clean();
    }

    private function renderNoDb(): string
    {
        // Capture no-db message content
        ob_start();
        $title = 'Загрузка данных FEO — Demo ERP';
        $pageTitle = 'Заявки ФЭО';
        ?>
        <div class="page-head">
            <div class="page-head-left">
                <div class="page-eyebrow">Основное / Заявки ФЭО</div>
                <div class="page-title">Загрузка данных FEO</div>
                <div class="page-summary"><span>Функционал требует подключения к БД</span></div>
            </div>
        </div>
        <div class="form-alert alert-warning" style="margin: 0;">
            <strong>База данных не подключена.</strong>
            Проверьте параметры .env (DB_HOST, DB_NAME, DB_USER, DB_PASS).
        </div>
        <?php
        $content = ob_get_clean();

        // Render inside layout
        ob_start();
        require __DIR__ . '/../../../Views/layouts/main.php';
        return ob_get_clean();
    }

    private function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Разбирает строку дат на ДАТА С и ДАТА ПО.
     */
    private function splitDateRange(string $datesFull): array
    {
        if ($datesFull === '' || $datesFull === '—') {
            return ['date_from' => '—', 'date_to' => '—'];
        }
        if (preg_match('/^с\s+(\d{2}\.\d{2}\.\d{4})\s+по\s+(\d{2}\.\d{2}\.\d{4})$/', $datesFull, $m)) {
            return ['date_from' => $m[1], 'date_to' => $m[2]];
        }
        if (preg_match('/^с\s+(\d{2}\.\d{2}\.\d{4})$/', $datesFull, $m)) {
            return ['date_from' => $m[1], 'date_to' => '—'];
        }
        if (preg_match('/^(\d{2}\.\d{2}\.\d{4})\s*[-–]\s*(\d{2}\.\d{2}\.\d{4})$/', $datesFull, $m)) {
            return ['date_from' => $m[1], 'date_to' => $m[2]];
        }
        if (preg_match('/^по\s+(\d{2}\.\d{2}\.\d{4})$/', $datesFull, $m)) {
            return ['date_from' => '—', 'date_to' => $m[1]];
        }
        if (preg_match('/^(\d{2}\.\d{2}\.\d{4})$/', $datesFull, $m)) {
            return ['date_from' => $m[1], 'date_to' => '—'];
        }
        return ['date_from' => $datesFull, 'date_to' => '—'];
    }

    /**
     * Подсчёт статусов для текущего набора строк.
     */
    private function computeStatusCounts(array $rows, array $flightMap, array $flightDetailsMap): array
    {
        $counts = ['planned' => 0, 'found' => 0, 'started' => 0, 'completed' => 0];
        foreach ($rows as $row) {
            $zId = (string) ($row['zayavka_id'] ?? '');
            $fId = $flightMap[$zId] ?? null;
            if ($fId === null || !isset($flightDetailsMap[$fId])) continue;
            $status = $flightDetailsMap[$fId]['status'] ?? '';
            switch ($status) {
                case 'planned_route': case 'planned-route': $counts['planned']++; break;
                case 'found': $counts['found']++; break;
                case 'started': $counts['started']++; break;
                case 'completed': $counts['completed']++; break;
            }
        }
        return $counts;
    }
}
