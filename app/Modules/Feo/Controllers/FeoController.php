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
                $limit
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

            // Доступно
            $availableBlockIdRaw = $availableBlockMap[$zayavkaId] ?? null;
            $availableBlockId = $availableBlockIdRaw !== null ? (string) $availableBlockIdRaw : '';
            $availableDisplay = $availableBlockId !== ''
                ? '<span class="status-available">Блок #' . htmlspecialchars($availableBlockId, ENT_QUOTES, 'UTF-8') . '</span>'
                : '<span style="color:#999;">—</span>';

            // Маршрут
            $marshrutIdRaw = $marshrutMap[$zayavkaId] ?? null;
            $marshrutId = $marshrutIdRaw !== null ? (string) $marshrutIdRaw : '';
            $marshrutDisplay = $marshrutId !== ''
                ? '<span class="status-marshrut">М#' . htmlspecialchars($marshrutId, ENT_QUOTES, 'UTF-8') . '</span>'
                : '<span style="color:#999;">—</span>';

            // Рейс — сохраняем int для ключа массива, string для htmlspecialchars
            $flightIdRaw = $flightMap[$zayavkaId] ?? null;
            $flightIdStr = $flightIdRaw !== null ? (string) $flightIdRaw : '';
            $flightDisplay = $flightIdStr !== ''
                ? '<span class="status-flight">Р#' . htmlspecialchars($flightIdStr, ENT_QUOTES, 'UTF-8') . '</span>'
                : '<span style="color:#999;">—</span>';

            // Масса, кг (mass_netto в тоннах → умножаем на 1000)
            $massKg = isset($row['mass_netto']) && $row['mass_netto'] !== '' && $row['mass_netto'] !== null
                ? floatval($row['mass_netto']) * 1000
                : null;
            $massDisplay = $massKg !== null ? number_format($massKg, 0, '.', ' ') : '';

            // Статус рейса
            $statusDisplay = '<span style="color:#999;">—</span>';
            if ($flightIdRaw !== null && isset($flightDetailsMap[$flightIdRaw])) {
                $flightStatus = $flightDetailsMap[$flightIdRaw]['status'];
                $statusData = $statusMap[$flightStatus] ?? null;
                if ($statusData) {
                    $styleClass = ltrim($statusData['style'] ?? '', '.');
                    $statusDisplay = '<span class="status-badge ' . htmlspecialchars($styleClass, ENT_QUOTES, 'UTF-8') . '">'
                        . htmlspecialchars($statusData['наименование'] ?? $flightStatus, ENT_QUOTES, 'UTF-8')
                        . '</span>';
                } else {
                    $statusDisplay = '<span class="status-badge" style="background: #6c757d;">'
                        . htmlspecialchars($flightStatus, ENT_QUOTES, 'UTF-8')
                        . '</span>';
                }
            }

            // Даты рейса
            $datesDisplay = '<span style="color:#999;">—</span>';
            if ($flightIdRaw !== null && isset($flightDetailsMap[$flightIdRaw])) {
                $datesDisplay = FeoStatusResolver::formatFlightDates($flightDetailsMap[$flightIdRaw]);
            }

            // Стоимость
            $costDisplay = '<span style="color:#999;">—</span>';
            $zayavkaKeyRaw = $row['zayavka_id'] ?? '';
            if ($zayavkaKeyRaw !== '' && isset($priceMap[$zayavkaKeyRaw])) {
                $costDisplay = number_format($priceMap[$zayavkaKeyRaw], 0, '.', ' ') . ' ₽';
            }

            // ₽/КГ
            $pricePerKgDisplay = '<span style="color:#999;">—</span>';
            if ($zayavkaKeyRaw !== '' && isset($pricePerKgMap[$zayavkaKeyRaw])) {
                $pricePerKgDisplay = number_format($pricePerKgMap[$zayavkaKeyRaw], 2, '.', ' ');
            }

            $html .= '<tr data-id="' . htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8') . '"'
                  . ' data-zayavka-id="' . htmlspecialchars($zayavkaId, ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<td style="width: 80px;">' . htmlspecialchars((string) ($row['zayavka_id'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td style="width: 90px; text-align: right;">' . $massDisplay . '</td>';
            $html .= '<td style="width: 150px;">' . htmlspecialchars((string) ($row['mno_region'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td style="width: 150px;">' . htmlspecialchars((string) ($row['mno_mo'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td style="width: 200px;">' . htmlspecialchars((string) ($row['naim_oo_gruzootpravitel'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td style="text-align: left;">' . htmlspecialchars((string) ($row['mno_adres_pogruzki'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td style="width: 100px; text-align: center;">' . $availableDisplay . '</td>';
            $html .= '<td style="width: 100px; text-align: center;">' . $marshrutDisplay . '</td>';
            $html .= '<td style="width: 150px; text-align: center;">' . $flightDisplay . '</td>';
            $html .= '<td style="width: 140px; text-align: center;">' . $statusDisplay . '</td>';
            $html .= '<td style="width: 180px; text-align: center; font-size: 12px;">' . $datesDisplay . '</td>';
            $html .= '<td style="width: 100px; text-align: right;">' . $costDisplay . '</td>';
            $html .= '<td style="width: 80px; text-align: right;">' . $pricePerKgDisplay . '</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    private function renderIndex(): string
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
            <title>Загрузка данных FEO — Demo ERP</title>
        </head>
        <body>
            <h1>Загрузка данных FEO</h1>
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

    private function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
