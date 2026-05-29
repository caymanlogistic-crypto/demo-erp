<?php

declare(strict_types=1);

namespace App\Modules\Reports\Services;

use App\Modules\Reports\Repositories\ReportsRepository;
use App\Modules\Reports\Support\FederalDistrictRegionMap;

/**
 * Сервис построения отчётов.
 *
 * Новый API:
 *   buildReports(period, dateType, dimension) -> view model с rows, summary, chart
 *
 * Старые методы (buildDeliveredByFO / buildDeliveredByRegions / buildStatusSummary)
 * сохранены для обратной совместимости.
 */
class ReportsService
{
    private ReportsRepository $repository;
    private FederalDistrictRegionMap $districtMap;

    /** @var string[] Месяцы (рус, кратко) */
    private const MONTH_NAMES = [
        1 => 'Янв', 2 => 'Фев', 3 => 'Мар', 4 => 'Апр',
        5 => 'Май', 6 => 'Июн', 7 => 'Июл', 8 => 'Авг',
        9 => 'Сен', 10 => 'Окт', 11 => 'Ноя', 12 => 'Дек',
    ];

    /** @var string[] Полные названия месяцев */
    private const MONTH_FULL = [
        1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
        5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
        9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
    ];

    private const STATUS_ORDER = ['started', 'completed', 'found', 'planned'];

    private const STATUS_LABELS = [
        'started'   => 'Вывоз начался',
        'completed' => 'Груз сдан',
        'found'     => 'Исполн. найден',
        'planned'   => 'Планируемый',
    ];

    private const STATUS_TITLES = [
        'started'   => 'Вывоз начался',
        'completed' => 'Груз сдан',
        'found'     => 'Исполнитель найден',
        'planned'   => 'Планируемый',
    ];

    public function __construct()
    {
        $this->repository = new ReportsRepository();
        $this->districtMap = new FederalDistrictRegionMap();
    }

    // ===================================================================
    //  Новый API: buildReports
    // ===================================================================

    /**
     * Построить отчёт с фильтрами period, dateType, dimension.
     *
     * @return array{period: string, date_type: string, dimension: string, chart_metric: string, summary: array, chart: array, rows: array, unmatched: int}
     */
    public function buildReports(string $period, string $dateType, string $dimension): array
    {
        $flights = $this->repository->getFlightsForReports($dateType);
        if (empty($flights)) {
            return [
                'period'       => $period,
                'date_type'    => $dateType,
                'dimension'    => $dimension,
                'chart_metric'  => 'requests',
                'summary'      => [
                    'requests_total'     => 0,
                    'weight_total_kg'    => 0,
                    'rows_total'         => 0,
                    'avg_request_weight_kg' => 0,
                ],
                'chart' => [
                    'enabled' => false,
                    'items'   => [],
                ],
                'rows'     => [],
                'unmatched' => 0,
            ];
        }

        // Оставляем только рейсы, у которых есть event_date
        $flightsWithDate = array_filter($flights, fn($f) => !empty($f['event_date']));

        [$zayavkaMap, $allZayavkaIds] = $this->collectZayavkiFromFlights($flightsWithDate);

        return match ($dimension) {
            'region' => $this->buildByRegion($flightsWithDate, $zayavkaMap, $period),
            'status' => $this->buildByStatus($flightsWithDate, $zayavkaMap, $period),
            default  => $this->buildByFO($flightsWithDate, $zayavkaMap, $period),
        };
    }

    /**
     * Построить chart data.
     *
     * @return array{enabled: bool, type: string, metric: string, items: array}
     */
    public function buildChartData(array $rows, string $dimension, string $chartMetric): array
    {
        $metric = in_array($chartMetric, ['weight', 'requests'], true) ? $chartMetric : 'requests';

        if (empty($rows)) {
            return ['enabled' => false, 'type' => 'bar', 'metric' => $metric, 'items' => []];
        }

        $items = [];
        foreach ($rows as $row) {
            $value = ($metric === 'weight')
                ? (int) ($row['weight_kg'] ?? 0)
                : (int) ($row['requests'] ?? 0);
            $label = $row['district_short'] ?? $row['region'] ?? $row['status_label'] ?? '';
            $fullLabel = $row['district_title'] ?? $row['region'] ?? $row['status_label'] ?? '';
            $items[] = [
                'label'       => $fullLabel,
                'short_label' => $label,
                'value'       => $value,
            ];
        }

        return ['enabled' => true, 'type' => 'bar', 'metric' => $metric, 'items' => $items];
    }

    // ===================================================================
    //  Dimension builders
    // ===================================================================

    private function buildByFO(array $flights, array $zayavkaMap, string $period): array
    {
        // districtKey → periodKey → [zId => true]
        $matrix = [];
        $unmatched = 0;

        foreach ($flights as $flight) {
            $periodKey = $this->getPeriodKey($flight['event_date'], $period);
            $ids = $this->parseZayavkaIds($flight['zayavki_ids']);
            foreach ($ids as $zId) {
                $data = $zayavkaMap[$zId] ?? null;
                if ($data === null) continue;
                $region = $data['mno_region'];
                $districtKey = $this->districtMap->resolveShortName($region) ?? '_unmatched';
                if ($districtKey === '_unmatched') $unmatched++;
                $matrix[$districtKey][$periodKey][$zId] = true;
            }
        }

        $districtOrder = $this->districtMap->districtOrder();
        $districtTitles = $this->districtMap->districtTitles();
        $rows = [];
        $grRequests = 0;
        $grWeight = 0.0;

        foreach ($districtOrder as $dk) {
            if (!isset($matrix[$dk])) continue;
            $row = $this->aggregatePeriodRow($matrix[$dk], $zayavkaMap);
            $row['district_key'] = $dk;
            $row['district_short'] = $districtTitles[$dk] ?? $dk;
            $row['district_title'] = $this->districtMap->districtFullTitle($dk);
            $rows[] = $row;
            $grRequests += $row['requests'];
            $grWeight += $row['weight_kg'];
        }

        // Unmatched
        if (isset($matrix['_unmatched'])) {
            $row = $this->aggregatePeriodRow($matrix['_unmatched'], $zayavkaMap);
            $row['district_key'] = '_unmatched';
            $row['district_short'] = '—';
            $row['district_title'] = 'Не определено';
            $rows[] = $row;
            $grRequests += $row['requests'];
            $grWeight += $row['weight_kg'];
        }

        return $this->buildResult($rows, $grRequests, $grWeight, $unmatched, $period);
    }

    private function buildByRegion(array $flights, array $zayavkaMap, string $period): array
    {
        // regionNormalized → periodKey → [zId => true]
        $matrix = [];
        $regionNames = [];

        foreach ($flights as $flight) {
            $periodKey = $this->getPeriodKey($flight['event_date'], $period);
            $ids = $this->parseZayavkaIds($flight['zayavki_ids']);
            foreach ($ids as $zId) {
                $data = $zayavkaMap[$zId] ?? null;
                if ($data === null) continue;
                $rawRegion = $data['mno_region'] ?? '';
                $normalized = $this->districtMap->normalizeRegion($rawRegion);
                $display = $rawRegion ?: '—';
                if ($normalized === '') {
                    $normalized = '_empty';
                    $display = 'Не указано';
                }
                $regionNames[$normalized] = $display;
                $matrix[$normalized][$periodKey][$zId] = true;
            }
        }

        $rows = [];
        foreach ($matrix as $norm => $periodData) {
            $row = $this->aggregatePeriodRow($periodData, $zayavkaMap);
            $row['region'] = $regionNames[$norm] ?? $norm;
            $districtKey = $this->districtMap->resolveShortName($regionNames[$norm] ?? null);
            $row['district_key'] = $districtKey;
            $row['district_short'] = $districtKey ? ($this->districtMap->districtTitles()[$districtKey] ?? '') : '';
            $rows[] = $row;
        }

        usort($rows, fn($a, $b) =>
            ($b['requests'] ?? 0) <=> ($a['requests'] ?? 0)
            ?: ($a['region'] ?? '') <=> ($b['region'] ?? '')
        );

        $grRequests = 0;
        $grWeight = 0.0;
        foreach ($rows as $r) { $grRequests += $r['requests']; $grWeight += $r['weight_kg']; }

        return $this->buildResult($rows, $grRequests, $grWeight, 0, $period);
    }

    private function buildByStatus(array $flights, array $zayavkaMap, string $period): array
    {
        // statusKey → periodKey → [zId => true]
        $matrix = [];

        foreach ($flights as $flight) {
            $statusKey = $this->resolveFlightStatus($flight);
            $periodKey = $this->getPeriodKey($flight['event_date'], $period);
            $ids = $this->parseZayavkaIds($flight['zayavki_ids']);
            foreach ($ids as $zId) {
                $data = $zayavkaMap[$zId] ?? null;
                if ($data === null) continue;
                $matrix[$statusKey][$periodKey][$zId] = true;
            }
        }

        $rows = [];
        $grRequests = 0;
        $grWeight = 0.0;

        foreach (self::STATUS_ORDER as $sk) {
            if (!isset($matrix[$sk])) continue;
            $row = $this->aggregatePeriodRow($matrix[$sk], $zayavkaMap);
            $row['status_key'] = $sk;
            $row['status_label'] = self::STATUS_LABELS[$sk] ?? $sk;
            $rows[] = $row;
            $grRequests += $row['requests'];
            $grWeight += $row['weight_kg'];
        }

        return $this->buildResult($rows, $grRequests, $grWeight, 0, $period);
    }

    private function buildResult(array $rows, int $grRequests, float $grWeight, int $unmatched, string $period): array
    {
        $totalRows = count($rows);
        $avgReq = $grRequests > 0 ? (int) round($grWeight / $grRequests) : 0;

        $totalWeightShared = 0.0;
        $totalRequestsShared = 0;
        foreach ($rows as $r) { $totalWeightShared += $r['weight_kg']; $totalRequestsShared += $r['requests']; }

        return [
            'period'       => $period,
            'date_type'    => 'delivery',
            'dimension'    => 'fo',
            'chart_metric'  => 'requests',
            'summary'      => [
                'requests_total'       => $totalRequestsShared,
                'weight_total_kg'      => (int) round($totalWeightShared),
                'rows_total'           => $totalRows,
                'avg_request_weight_kg' => $avgReq,
            ],
            'chart' => ['enabled' => false, 'items' => []],
            'rows'     => $rows,
            'unmatched' => $unmatched,
        ];
    }

    // ===================================================================
    //  Aggregation helpers
    // ===================================================================

    /**
     * @param array<string, array<string, true>> $periodData periodKey → [zId => true]
     */
    private function aggregatePeriodRow(array $periodData, array $zayavkaMap): array
    {
        $allZIds = [];
        foreach ($periodData as $zIds) {
            foreach ($zIds as $zId => $v) {
                $allZIds[$zId] = true;
            }
        }
        $uniqueZIds = array_keys($allZIds);
        $totalWeight = 0.0;
        foreach ($uniqueZIds as $zId) {
            $totalWeight += ($zayavkaMap[$zId]['mass_netto'] ?? 0) * 1000;
        }
        $totalCount = count($uniqueZIds);

        return [
            'requests'  => $totalCount,
            'weight_kg' => (int) round($totalWeight),
            'avg_request_kg' => $totalCount > 0 ? (int) round($totalWeight / $totalCount) : 0,
        ];
    }

    private function getPeriodKey(?string $date, string $period): string
    {
        if (empty($date)) return 'unknown';
        $ts = strtotime($date);
        if ($period === 'month') return date('Y-m', $ts);
        if ($period === 'quarter') return date('Y', $ts) . '-Q' . ceil((int) date('n', $ts) / 3);
        return date('Y', $ts);
    }

    public function formatPeriodLabel(string $periodKey, string $period): string
    {
        if ($period === 'month') {
            $parts = explode('-', $periodKey);
            $y = (int) ($parts[0] ?? 0);
            $m = (int) ($parts[1] ?? 0);
            return (self::MONTH_FULL[$m] ?? (string) $m) . ' ' . $y;
        }
        if ($period === 'quarter') {
            $parts = explode('-Q', $periodKey);
            $y = (int) ($parts[0] ?? 0);
            $q = (int) ($parts[1] ?? 0);
            return $q . ' кв. ' . $y;
        }
        return $periodKey;
    }

    // ===================================================================
    //  Helpers (shared)
    // ===================================================================

    private function collectZayavkiFromFlights(array $flights): array
    {
        $allIds = [];
        foreach ($flights as $flight) {
            $ids = $this->parseZayavkaIds($flight['zayavki_ids']);
            foreach ($ids as $id) { $allIds[$id] = true; }
        }
        $uniqueIds = array_keys($allIds);
        $zayavkaMap = $this->repository->getZayavkiData($uniqueIds);
        return [$zayavkaMap, $uniqueIds];
    }

    private function parseZayavkaIds(string $zayavkiIds): array
    {
        $parts = array_filter(array_map('trim', explode(',', $zayavkiIds)));
        return array_values($parts);
    }

    private function resolveFlightStatus(array $flight): string
    {
        $actualEnd = $flight['actual_end_date'] ?? null;
        $actualStart = $flight['actual_start_date'] ?? null;
        $status = $flight['status'] ?? null;
        if ($actualEnd !== null && $actualEnd !== '') return 'completed';
        if ($actualStart !== null && $actualStart !== '') return 'started';
        if ($status === 'found') return 'found';
        return 'planned';
    }

    // ===================================================================
    //  Normalization / validation
    // ===================================================================

    public function normalizePeriod(string $period): string
    {
        return in_array($period, ['month', 'quarter', 'year'], true) ? $period : 'month';
    }

    public function normalizeDateType(string $dateType): string
    {
        return in_array($dateType, ['delivery', 'pickup'], true) ? $dateType : 'delivery';
    }

    public function normalizeDimension(string $dimension): string
    {
        return in_array($dimension, ['fo', 'region', 'status'], true) ? $dimension : 'fo';
    }

    public function normalizeChartMetric(string $metric): string
    {
        return in_array($metric, ['weight', 'requests'], true) ? $metric : 'requests';
    }

    // ===================================================================
    //  Старый API (для обратной совместимости)
    // ===================================================================

    public function buildDeliveredByFO(): array
    {
        $flights = $this->repository->getCompletedFlights();
        if (empty($flights)) return ['months' => [], 'rows' => [], 'totals' => [], 'unmatched' => 0];
        [$zayavkaMap] = $this->collectZayavkiFromFlights($flights);
        $months = $this->extractMonths($flights);
        $matrix = [];
        $unmatched = 0;
        foreach ($flights as $flight) {
            $monthKey = $this->monthKey($flight['actual_end_date']);
            $ids = $this->parseZayavkaIds($flight['zayavki_ids']);
            foreach ($ids as $zId) {
                $data = $zayavkaMap[$zId] ?? null;
                if ($data === null) continue;
                $region = $data['mno_region'];
                $districtKey = $this->districtMap->resolveShortName($region) ?? '_unmatched';
                if ($districtKey === '_unmatched') $unmatched++;
                $matrix[$districtKey][$monthKey][$zId] = true;
            }
        }
        $rows = [];
        $districtOrder = $this->districtMap->districtOrder();
        $districtTitles = $this->districtMap->districtTitles();
        $grandTotalWeight = 0.0;
        $grandTotalCount = 0;
        foreach ($districtOrder as $dk) {
            if (!isset($matrix[$dk])) continue;
            $row = $this->buildMonthRow($matrix[$dk], $months, $zayavkaMap);
            $row['district_key'] = $dk;
            $row['district_title'] = $districtTitles[$dk] ?? $dk;
            $row['district_full'] = $this->districtMap->districtFullTitle($dk);
            $rows[] = $row;
            $grandTotalWeight += $row['total_weight'];
            $grandTotalCount += $row['total_count'];
        }
        if (isset($matrix['_unmatched'])) {
            $row = $this->buildMonthRow($matrix['_unmatched'], $months, $zayavkaMap);
            $row['district_key'] = '_unmatched';
            $row['district_title'] = 'Не определено';
            $row['district_full'] = 'Регион не определён';
            $rows[] = $row;
            $grandTotalWeight += $row['total_weight'];
            $grandTotalCount += $row['total_count'];
        }
        $grandRow = [
            'district_key' => '_grand', 'district_title' => 'Общий итог', 'district_full' => 'Общий итог',
            'total_weight' => $grandTotalWeight, 'total_count' => $grandTotalCount, 'cells' => [], 'is_total' => true,
        ];
        foreach ($months as $mk) {
            $w = 0.0; $c = 0;
            foreach ($rows as $r) { $w += $r['cells'][$mk]['weight'] ?? 0; $c += $r['cells'][$mk]['count'] ?? 0; }
            $grandRow['cells'][$mk] = ['weight' => $w, 'count' => $c];
        }
        return ['months' => $months, 'rows' => $rows, 'totals' => $grandRow, 'unmatched' => $unmatched];
    }

    public function buildDeliveredByRegions(): array
    {
        $flights = $this->repository->getCompletedFlights();
        if (empty($flights)) return ['months' => [], 'rows' => [], 'totals' => [], 'unmatched' => 0];
        [$zayavkaMap] = $this->collectZayavkiFromFlights($flights);
        $months = $this->extractMonths($flights);
        $matrix = [];
        $regionNames = [];
        foreach ($flights as $flight) {
            $monthKey = $this->monthKey($flight['actual_end_date']);
            $ids = $this->parseZayavkaIds($flight['zayavki_ids']);
            foreach ($ids as $zId) {
                $data = $zayavkaMap[$zId] ?? null;
                if ($data === null) continue;
                $rawRegion = $data['mno_region'] ?? '';
                $normalized = $this->districtMap->normalizeRegion($rawRegion);
                $display = $rawRegion ?: '—';
                if ($normalized === '') { $normalized = '_empty'; $display = 'Не указано'; }
                $regionNames[$normalized] = $display;
                $matrix[$normalized][$monthKey][$zId] = true;
            }
        }
        $rows = [];
        foreach ($matrix as $norm => $monthData) {
            $row = $this->buildMonthRow($monthData, $months, $zayavkaMap);
            $row['region_normalized'] = $norm;
            $row['region_display'] = $regionNames[$norm] ?? $norm;
            $districtKey = $this->districtMap->resolveShortName($regionNames[$norm] ?? null);
            $row['district_key'] = $districtKey;
            $row['district_short'] = $districtKey ? ($this->districtMap->districtTitles()[$districtKey] ?? '') : '';
            $rows[] = $row;
        }
        usort($rows, fn($a, $b) =>
            ($b['total_count'] ?? 0) <=> ($a['total_count'] ?? 0)
            ?: ($a['region_display'] ?? '') <=> ($b['region_display'] ?? '')
        );
        $grandTotalWeight = 0.0; $grandTotalCount = 0;
        foreach ($rows as $r) { $grandTotalWeight += $r['total_weight']; $grandTotalCount += $r['total_count']; }
        $grandRow = [
            'region_normalized' => '_grand', 'region_display' => 'Общий итог',
            'district_key' => '', 'district_short' => '',
            'total_weight' => $grandTotalWeight, 'total_count' => $grandTotalCount, 'cells' => [], 'is_total' => true,
        ];
        foreach ($months as $mk) {
            $w = 0.0; $c = 0;
            foreach ($rows as $r) { $w += $r['cells'][$mk]['weight'] ?? 0; $c += $r['cells'][$mk]['count'] ?? 0; }
            $grandRow['cells'][$mk] = ['weight' => $w, 'count' => $c];
        }
        return ['months' => $months, 'rows' => $rows, 'totals' => $grandRow, 'unmatched' => 0];
    }

    public function buildStatusSummary(): array
    {
        $flights = $this->repository->getAllFlights();
        if (empty($flights)) return ['statuses' => [], 'rows' => [], 'totals' => [], 'unmatched' => 0];
        [$zayavkaMap] = $this->collectZayavkiFromFlights($flights);
        $matrix = [];
        $unmatched = 0;
        foreach ($flights as $flight) {
            $statusKey = $this->resolveFlightStatus($flight);
            $ids = $this->parseZayavkaIds($flight['zayavki_ids']);
            foreach ($ids as $zId) {
                $data = $zayavkaMap[$zId] ?? null;
                if ($data === null) continue;
                $region = $data['mno_region'];
                $districtKey = $this->districtMap->resolveShortName($region) ?? '_unmatched';
                if ($districtKey === '_unmatched') $unmatched++;
                $matrix[$districtKey][$statusKey][$zId] = true;
            }
        }
        $rows = [];
        $districtOrder = $this->districtMap->districtOrder();
        $districtTitles = $this->districtMap->districtTitles();
        $statuses = self::STATUS_ORDER;
        $grandTotalWeight = 0.0; $grandTotalCount = 0;
        foreach ($districtOrder as $dk) {
            if (!isset($matrix[$dk])) continue;
            $row = $this->buildStatusRow($matrix[$dk], $statuses, $zayavkaMap);
            $row['district_key'] = $dk;
            $row['district_title'] = $districtTitles[$dk] ?? $dk;
            $row['district_full'] = $this->districtMap->districtFullTitle($dk);
            $rows[] = $row;
            $grandTotalWeight += $row['total_weight'];
            $grandTotalCount += $row['total_count'];
        }
        if (isset($matrix['_unmatched'])) {
            $row = $this->buildStatusRow($matrix['_unmatched'], $statuses, $zayavkaMap);
            $row['district_key'] = '_unmatched';
            $row['district_title'] = 'Не определено';
            $row['district_full'] = 'Регион не определён';
            $rows[] = $row;
            $grandTotalWeight += $row['total_weight'];
            $grandTotalCount += $row['total_count'];
        }
        $grandRow = [
            'district_key' => '_grand', 'district_title' => 'Общий итог', 'district_full' => 'Общий итог',
            'total_weight' => $grandTotalWeight, 'total_count' => $grandTotalCount, 'cells' => [], 'is_total' => true,
        ];
        foreach ($statuses as $sk) {
            $w = 0.0; $c = 0;
            foreach ($rows as $r) { $w += $r['cells'][$sk]['weight'] ?? 0; $c += $r['cells'][$sk]['count'] ?? 0; }
            $grandRow['cells'][$sk] = ['weight' => $w, 'count' => $c];
        }
        return ['statuses' => $statuses, 'rows' => $rows, 'totals' => $grandRow, 'unmatched' => $unmatched];
    }

    private function extractMonths(array $flights): array
    {
        $months = [];
        foreach ($flights as $flight) {
            $key = $this->monthKey($flight['actual_end_date']);
            $months[$key] = true;
        }
        $sorted = array_keys($months);
        sort($sorted);
        return $sorted;
    }

    private function monthKey(string $date): string
    {
        $ts = strtotime($date);
        return date('Y-m', $ts);
    }

    public function formatMonthLabel(array $monthKeys, string $monthKey): string
    {
        $parts = explode('-', $monthKey);
        $year = (int) ($parts[0] ?? 0);
        $month = (int) ($parts[1] ?? 0);
        $short = self::MONTH_NAMES[$month] ?? (string) $month;
        $years = [];
        foreach ($monthKeys as $mk) { $yp = explode('-', $mk); $years[(int) ($yp[0] ?? 0)] = true; }
        return count($years) > 1 ? $short . ' ' . $year : $short;
    }

    private function buildMonthRow(array $monthData, array $months, array $zayavkaMap): array
    {
        $cells = [];
        $totalWeight = 0.0; $totalCount = 0;
        foreach ($months as $mk) {
            $zIds = array_keys($monthData[$mk] ?? []);
            $weight = 0.0;
            foreach ($zIds as $zId) { $weight += ($zayavkaMap[$zId]['mass_netto'] ?? 0) * 1000; }
            $count = count($zIds);
            $cells[$mk] = ['weight' => $weight, 'count' => $count];
            $totalWeight += $weight; $totalCount += $count;
        }
        return ['cells' => $cells, 'total_weight' => $totalWeight, 'total_count' => $totalCount];
    }

    private function buildStatusRow(array $statusData, array $statuses, array $zayavkaMap): array
    {
        $cells = [];
        $totalWeight = 0.0; $totalCount = 0;
        foreach ($statuses as $sk) {
            $zIds = array_keys($statusData[$sk] ?? []);
            $weight = 0.0;
            foreach ($zIds as $zId) { $weight += ($zayavkaMap[$zId]['mass_netto'] ?? 0) * 1000; }
            $count = count($zIds);
            $cells[$sk] = ['weight' => $weight, 'count' => $count];
            $totalWeight += $weight; $totalCount += $count;
        }
        return ['cells' => $cells, 'total_weight' => $totalWeight, 'total_count' => $totalCount];
    }

    public function statusLabel(string $statusKey): string
    {
        return self::STATUS_LABELS[$statusKey] ?? $statusKey;
    }

    public function statusTitle(string $statusKey): string
    {
        return self::STATUS_TITLES[$statusKey] ?? $statusKey;
    }

    public static function formatWeight(float $kg): string
    {
        if ($kg == 0) return '—';
        return number_format((int) round($kg), 0, '.', ' ') . ' кг';
    }

    public static function formatCount(int $count): string
    {
        return $count === 0 ? '—' : (string) $count;
    }
}
