<?php

declare(strict_types=1);

namespace App\Modules\Flights\Services;

use App\Modules\Flights\Repositories\FlightsRepository;

/**
 * Сервис для обогащения данных рейсов:
 *  - заявки из feo
 *  - Google Sheets данные (через fallback — пока пустые)
 *  - price_per_kg
 *  - агрегация веса, адресов, прозвона
 */
class FlightsTimelineService
{
    private FlightsRepository $repository;
    private GoogleSheetsTimelineService $googleSheets;

    /**
     * Google Sheets данные, проиндексированные по zayavka_id.
     *
     * @var array<string, array{route: string, comments: string, driver_info: string, prep_time: string}>
     */
    private array $googleData;

    public function __construct()
    {
        $this->repository   = new FlightsRepository();
        $this->googleSheets = new GoogleSheetsTimelineService();
        $this->googleData   = $this->googleSheets->getIndexedByZayavkaId();
    }

    /**
     * Получить googleData (для summary-подсчётов).
     */
    public function getGoogleData(): array
    {
        return $this->googleData;
    }

    /**
     * Обогатить рейсы данными заявок.
     *
     * @param array $flights Сырые данные рейсов из БД
     * @return array Обогащённые рейсы
     */
    public function enrichFlights(array $flights): array
    {
        if (empty($flights)) {
            return [];
        }

        // Собираем все zayavka_id из всех рейсов
        $allZayavkaIds = [];
        foreach ($flights as $flight) {
            $ids = array_filter(explode(',', $flight['zayavki_ids'] ?? ''));
            foreach ($ids as $id) {
                $id = trim($id);
                if ($id !== '') {
                    $allZayavkaIds[$id] = true;
                }
            }
        }
        $uniqueIds = array_keys($allZayavkaIds);

        // Получаем данные заявок из feo
        $zayavkiDataMap = empty($uniqueIds)
            ? []
            : $this->repository->getZayavkiData($uniqueIds);

        // Получаем price_per_kg
        $pricePerKgMap = empty($uniqueIds)
            ? []
            : $this->repository->getPricePerKg($uniqueIds);

        // Обогащаем каждый рейс
        $enriched = [];
        foreach ($flights as $flight) {
            $zayavkaIdsRaw = array_filter(explode(',', $flight['zayavki_ids'] ?? ''));
            $zayavkaIds = array_map('trim', $zayavkaIdsRaw);
            $zayavkaIds = array_filter($zayavkaIds, fn($id) => $id !== '');

            if (!empty($zayavkaIds)) {
                $zayavki = [];
                $uniqueAddresses = [];
                $totalMassKg = 0.0;
                $prepTimeFilledCount = 0;

                foreach ($zayavkaIds as $zid) {
                    $zData = $zayavkiDataMap[$zid] ?? null;
                    if ($zData === null) {
                        continue;
                    }

                    $enrichedZ = $zData;

                    // Google Sheets данные (реальные, не fallback)
                    $gsData = $this->googleData[$zid] ?? null;
                    $prepTime = $gsData['prep_time'] ?? '';
                    $comments = $gsData['comments'] ?? '';
                    $driverInfo = $gsData['driver_info'] ?? '';
                    $route = $gsData['route'] ?? '';

                    $enrichedZ['prep_time'] = $prepTime;
                    $enrichedZ['comments'] = $comments;
                    $enrichedZ['driver_info'] = $driverInfo;
                    $enrichedZ['route'] = $route;

                    if ($prepTime !== '' && is_numeric($prepTime)) {
                        $prepTimeFilledCount++;
                    }

                    // Уникальные адреса
                    $addr = trim($enrichedZ['mno_adres_pogruzki'] ?? '');
                    if ($addr !== '' && !in_array($addr, $uniqueAddresses, true)) {
                        $uniqueAddresses[] = $addr;
                    }

                    // Масса
                    $massKg = floatval($zData['mass_netto'] ?? 0) * 1000;
                    $totalMassKg += $massKg;

                    // price_per_kg
                    $enrichedZ['price_per_kg'] = $pricePerKgMap[$zid] ?? null;

                    $zayavki[] = $enrichedZ;
                }

                $flight['zayavki'] = $zayavki;
                $flight['unique_addresses_count'] = count($uniqueAddresses);
                $flight['total_zayavki_count'] = count($zayavki);
                $flight['total_mass_kg'] = $totalMassKg;
                $flight['prep_time_filled_count'] = $prepTimeFilledCount;
            } else {
                $flight['zayavki'] = [];
                $flight['unique_addresses_count'] = 0;
                $flight['total_zayavki_count'] = 0;
                $flight['total_mass_kg'] = 0;
                $flight['prep_time_filled_count'] = 0;
            }

            $enriched[] = $flight;
        }

        // Обогащаем warehouse_label / warehouse_title
        $this->enrichWarehouseLabels($enriched);

        return $enriched;
    }

    /**
     * Обогатить рейсы складскими названиями (одним запросом, без N+1).
     *
     * Алгоритм:
     *  1. Собрать все source_warehouse_id и destination_warehouse_id.
     *  2. Одним SQL-запросом получить id → name из warehouses.
     *  3. Для каждого рейса сформировать warehouse_label и warehouse_title.
     *
     * Правила:
     *  - generator_to_warehouse   → название склада назначения
     *  - warehouse_to_warehouse   → Склад А → Склад Б
     *  - warehouse_to_utilizer    → название склада-источника
     *  - generator_to_utilizer    → —
     *  - fallback unload_type=SKLAD → как generator_to_warehouse
     *
     * @param array $flights (by reference)
     */
    private function enrichWarehouseLabels(array &$flights): void
    {
        // Собираем все складские ID
        $warehouseIds = [];
        foreach ($flights as $flight) {
            $sid = isset($flight['source_warehouse_id']) ? (int) $flight['source_warehouse_id'] : 0;
            $did = isset($flight['destination_warehouse_id']) ? (int) $flight['destination_warehouse_id'] : 0;
            if ($sid > 0) {
                $warehouseIds[$sid] = true;
            }
            if ($did > 0) {
                $warehouseIds[$did] = true;
            }
        }

        $namesMap = empty($warehouseIds)
            ? []
            : $this->repository->getWarehouseNames(array_keys($warehouseIds));

        $loadLabels = 0;
        $unloadLabels = 0;
        $missingIds = 0;

        foreach ($flights as &$flight) {
            $routeType = $flight['route_type'] ?? '';
            $unloadType = $flight['unload_type'] ?? '';
            $srcId = isset($flight['source_warehouse_id']) ? (int) $flight['source_warehouse_id'] : 0;
            $dstId = isset($flight['destination_warehouse_id']) ? (int) $flight['destination_warehouse_id'] : 0;

            $flight['warehouse_load_label'] = '';
            $flight['warehouse_load_title'] = '';
            $flight['warehouse_unload_label'] = '';
            $flight['warehouse_unload_title'] = '';

            // Определяем эффективный route_type с учётом fallback
            $effectiveRoute = $routeType;
            if ($effectiveRoute === '' && $unloadType === 'SKLAD') {
                $effectiveRoute = 'generator_to_warehouse';
            }

            switch ($effectiveRoute) {
                case 'generator_to_warehouse':
                    // ОО → склад: загрузка —, выгрузка = склад назначения
                    $name = $namesMap[$dstId] ?? null;
                    if ($name !== null) {
                        $flight['warehouse_unload_label'] = $name;
                        $flight['warehouse_unload_title'] = 'Склад выгрузки: ' . $name;
                        $unloadLabels++;
                    } elseif ($dstId > 0) {
                        $flight['warehouse_unload_label'] = 'Склад #' . $dstId;
                        $flight['warehouse_unload_title'] = 'Склад выгрузки #' . $dstId;
                        $missingIds++;
                    }
                    break;

                case 'warehouse_to_utilizer':
                    // склад → утилизатор: загрузка = склад-источник, выгрузка —
                    $name = $namesMap[$srcId] ?? null;
                    if ($name !== null) {
                        $flight['warehouse_load_label'] = $name;
                        $flight['warehouse_load_title'] = 'Склад загрузки: ' . $name;
                        $loadLabels++;
                    } elseif ($srcId > 0) {
                        $flight['warehouse_load_label'] = 'Склад #' . $srcId;
                        $flight['warehouse_load_title'] = 'Склад загрузки #' . $srcId;
                        $missingIds++;
                    }
                    break;

                case 'warehouse_to_warehouse':
                    // склад → склад: загрузка = источник, выгрузка = назначение
                    $srcName = $namesMap[$srcId] ?? null;
                    $dstName = $namesMap[$dstId] ?? null;

                    if ($srcName !== null) {
                        $flight['warehouse_load_label'] = $srcName;
                        $flight['warehouse_load_title'] = 'Склад загрузки: ' . $srcName;
                        $loadLabels++;
                    } elseif ($srcId > 0) {
                        $flight['warehouse_load_label'] = 'Склад #' . $srcId;
                        $flight['warehouse_load_title'] = 'Склад загрузки #' . $srcId;
                        $missingIds++;
                    }

                    if ($dstName !== null) {
                        $flight['warehouse_unload_label'] = $dstName;
                        $flight['warehouse_unload_title'] = 'Склад выгрузки: ' . $dstName;
                        $unloadLabels++;
                    } elseif ($dstId > 0) {
                        $flight['warehouse_unload_label'] = 'Склад #' . $dstId;
                        $flight['warehouse_unload_title'] = 'Склад выгрузки #' . $dstId;
                        $missingIds++;
                    }
                    break;

                case 'generator_to_utilizer':
                default:
                    // ОО → утилизатор: складов нет
                    break;
            }
        }
        unset($flight);

        // Диагностический маркер (без персональных данных)
        $GLOBALS['_FLIGHTS_WAREHOUSE_DIAG'] = [
            'loadLabels' => $loadLabels,
            'unloadLabels' => $unloadLabels,
            'missing' => $missingIds,
        ];
    }

    /**
     * Форматировать дату рейса для отображения (без emoji).
     *
     * В legacy были emoji ⚠️ ✅ 🚚 📅 в зависимости от состояния и таба.
     * В Demo ERP emoji не используются.
     */
    public function formatFlightDate(array $flight, string $tab): string
    {
        // Фактические даты
        if (!empty($flight['actual_start_date'])) {
            return date('d.m.Y', strtotime($flight['actual_start_date']));
        }

        // Плановые даты
        if (!empty($flight['planned_start_date'])) {
            return date('d.m.Y', strtotime($flight['planned_start_date']));
        }
        if (!empty($flight['planned_start_date_from']) && !empty($flight['planned_start_date_to'])) {
            return date('d.m.Y', strtotime($flight['planned_start_date_from']))
                . ' - '
                . date('d.m.Y', strtotime($flight['planned_start_date_to']));
        }
        if (!empty($flight['planned_start_date_from'])) {
            return 'с ' . date('d.m.Y', strtotime($flight['planned_start_date_from']));
        }
        if (!empty($flight['planned_start_date_to'])) {
            return 'по ' . date('d.m.Y', strtotime($flight['planned_start_date_to']));
        }

        return '—';
    }

    /**
     * Дата-маркер класс для строки (не emoji, а CSS-класс).
     */
    public function getDateMarkerClass(array $flight, string $tab): string
    {
        if ($tab !== 'all') {
            return '';
        }

        $isUnassigned = empty($flight['planned_start_date'])
            && empty($flight['planned_start_date_from'])
            && empty($flight['planned_start_date_to'])
            && empty($flight['actual_start_date']);

        if ($isUnassigned) return 'date-marker-unassigned';
        if (!empty($flight['actual_start_date']) && !empty($flight['actual_end_date'])) return 'date-marker-completed';
        if (!empty($flight['actual_start_date']) && empty($flight['actual_end_date'])) return 'date-marker-in-transit';
        return 'date-marker-planned';
    }

    /**
     * Статус рейса в формате для отображения.
     *
     * @return array{label: string, css_class: string}
     */
    public function getFlightStatus(array $flight, array $statusMap): array
    {
        $status = $flight['status'] ?? '';
        $driverId = $flight['driver_id'] ?? null;

        // Особый случай: status=found, driver_id пустой
        if ($status === 'found' && empty($driverId)) {
            return ['label' => 'данные водителя ?', 'css_class' => 'status-waiting-driver'];
        }

        $statusData = $statusMap[$status] ?? null;
        if ($statusData) {
            $styleClass = ltrim($statusData['style'] ?? '', '.');
            return ['label' => $statusData['наименование'] ?? $status, 'css_class' => $styleClass];
        }

        // Неизвестный статус
        return ['label' => $status ?: 'Неизвестно', 'css_class' => 'status-neutral'];
    }

    /**
     * Форматировать контакты заявки (уникальные, через разделитель |).
     */
    public function formatContacts(array $zayavka): array
    {
        $contacts = [];

        if (!empty($zayavka['kontakt_tel'])) {
            foreach (explode('|', $zayavka['kontakt_tel']) as $tel) {
                $tel = trim($tel);
                if ($tel !== '' && !in_array($tel, $contacts, true)) {
                    $contacts[] = $tel;
                }
            }
        }

        if (!empty($zayavka['tel_dopolnitelnyy'])) {
            foreach (explode('|', $zayavka['tel_dopolnitelnyy']) as $tel) {
                $tel = trim($tel);
                if ($tel !== '' && !in_array($tel, $contacts, true)) {
                    $contacts[] = $tel;
                }
            }
        }

        if (!empty($zayavka['kontakt_email'])) {
            foreach (explode('|', $zayavka['kontakt_email']) as $email) {
                $email = trim($email);
                if ($email !== '' && !in_array($email, $contacts, true)) {
                    $contacts[] = $email;
                }
            }
        }

        return $contacts;
    }

    /**
     * Договор для заявки: Ф-25 или Д-26 на основе price_per_kg.
     */
    public function getContractLabel(?float $pricePerKg): string
    {
        if ($pricePerKg === null) {
            return 'Д-26';
        }
        // 40 и 36 — Ф-25; остальное — Д-26
        if (abs($pricePerKg - 40.0) < 0.01 || abs($pricePerKg - 36.0) < 0.01) {
            return 'Ф-25';
        }
        return 'Д-26';
    }

    /**
     * Пропуск: prep_time из Google Sheets.
     * Если > 3 — high.
     */
    public function getPropiskaLabel(string $prepTime): array
    {
        if ($prepTime === '') {
            return ['label' => '—', 'class' => ''];
        }
        if (is_numeric($prepTime) && (float) $prepTime > 3) {
            return ['label' => $prepTime, 'class' => 'days-badge high'];
        }
        return ['label' => $prepTime, 'class' => 'days-badge'];
    }
}
