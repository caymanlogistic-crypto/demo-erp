<?php

declare(strict_types=1);

namespace App\Modules\Reports\Services;

use App\Modules\Reports\Repositories\ReportsRepository;
use App\Modules\Reports\Support\FederalDistrictRegionMap;

/**
 * Сервис построения отчётов.
 *
 * Фильтры: period (week|month|custom), date_type (pickup|delivery), dimension (fo|region|status).
 * Старый API сохранён для обратной совместимости через resolveDimension().
 */
class ReportsService
{
    private ReportsRepository $repository;
    private FederalDistrictRegionMap $districtMap;

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

    /** @var string|null Запомненный dateFrom для buildByFO */
    private ?string $lastDateFrom = null;
    /** @var string|null Запомненный dateTo для buildByFO */
    private ?string $lastDateTo = null;

    public function __construct()
    {
        $this->repository = new ReportsRepository();
        $this->districtMap = new FederalDistrictRegionMap();
    }

    // ===================================================================
    //  Нормализация фильтров (как Statistics)
    // ===================================================================

    /**
     * @return array{period: string, date_type: string, dimension: string, chart_metric: string, date_from: string, date_to: string, warning: string|null}
     */
    public function normalizeFilters(): array
    {
        $period      = $this->normalizePeriod($_GET['period'] ?? 'week');
        $dateType    = $this->normalizeDateType($_GET['date_type'] ?? 'delivery');
        $dimension   = $this->resolveDimension();
        $chartMetric = $this->normalizeChartMetric($_GET['chart_metric'] ?? 'requests');
        $warning     = null;

        $defaultFrom = date('Y-m-d', strtotime('-12 weeks'));
        $defaultTo   = date('Y-m-d');

        if ($period === 'custom') {
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo   = $_GET['date_to'] ?? '';

            if ($dateFrom === '' || !$this->isValidDate($dateFrom)) {
                if ($dateFrom !== '') {
                    $warning = 'Период был скорректирован автоматически';
                }
                $dateFrom = $defaultFrom;
            }

            if ($dateTo === '' || !$this->isValidDate($dateTo)) {
                if ($dateTo !== '') {
                    $warning = 'Период был скорректирован автоматически';
                }
                $dateTo = $defaultTo;
            }
        } else {
            $dateFrom = $defaultFrom;
            $dateTo   = $defaultTo;
        }

        return [
            'period'       => $period,
            'date_type'    => $dateType,
            'dimension'    => $dimension,
            'chart_metric' => $chartMetric,
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
            'warning'      => $warning,
        ];
    }

    /**
     * Построить отчёт.
     *
     * @return array
     */
    public function buildReports(string $period, string $dateType, string $dimension, string $dateFrom, string $dateTo): array
    {
        $this->lastDateFrom = $dateFrom;
        $this->lastDateTo   = $dateTo;

        $flights = $this->repository->getFlightsForReports($dateType, $dateFrom, $dateTo);
        if (empty($flights)) {
            return $this->emptyResult($dimension);
        }

        $flightsWithDate = array_filter($flights, fn($f) => !empty($f['event_date']));
        [$zayavkaMap] = $this->collectZayavkiFromFlights($flightsWithDate);

        return match ($dimension) {
            'fo'     => $this->buildByFO($flightsWithDate, $zayavkaMap, $period),
            'region' => $this->buildByRegion($flightsWithDate, $zayavkaMap, $period),
            'status' => $this->buildByStatus($flightsWithDate, $zayavkaMap, $period),
            default  => $this->buildByFO($flightsWithDate, $zayavkaMap, $period),
        };
    }

    /**
     * Построить chart data.
     *
     * @param array  $reportData  Полный результат buildReports()
     * @param string $dimension   fo|region|status
     * @param string $chartMetric requests|weight
     * @return array
     */
    public function buildChartData(array $reportData, string $dimension, string $chartMetric): array
    {
        $metric = $this->normalizeChartMetric($chartMetric);

        if ($dimension === 'fo') {
            return $this->buildChartDataFO($reportData, $metric);
        }

        // region / status — горизонтальный bar chart (ТОП-10)
        $rows = $reportData['rows'] ?? [];
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
                'requests'    => (int) ($row['requests'] ?? 0),
                'weight'      => (int) ($row['weight_kg'] ?? 0),
            ];
        }

        // ТОП-10 для region/status
        usort($items, fn($a, $b) => ($b[$metric] ?? 0) <=> ($a[$metric] ?? 0));
        $items = array_slice($items, 0, 10);

        return ['enabled' => true, 'type' => 'bar', 'metric' => $metric, 'items' => $items];
    }

    // ===================================================================
    //  Dimension builders
    // ===================================================================

    /**
     * Построить матрицу period × district для ФО.
     */
    private function buildByFO(array $flights, array $zayavkaMap, string $period): array
    {
        // matrix[periodKey][districtKey][zId] = true
        $matrix = [];
        $unmatched = 0;
        $allPeriodKeys = [];

        foreach ($flights as $flight) {
            $periodKey = $this->getPeriodKey($flight['event_date'], $period);
            $allPeriodKeys[$periodKey] = true;
            $ids = $this->parseZayavkaIds($flight['zayavki_ids']);
            foreach ($ids as $zId) {
                $data = $zayavkaMap[$zId] ?? null;
                if ($data === null) continue;
                $region = $data['mno_region'];
                $districtKey = $this->districtMap->resolveShortName($region) ?? '_unmatched';
                if ($districtKey === '_unmatched') $unmatched++;
                $matrix[$periodKey][$districtKey][$zId] = true;
            }
        }

        $districtOrder = $this->districtMap->districtOrder();
        $districtTitles = $this->districtMap->districtTitles();

        // Определяем, есть ли неопределённые ФО
        $hasUnmatched = false;
        foreach ($allPeriodKeys as $pk => $v) {
            if (isset($matrix[$pk]['_unmatched'])) {
                $hasUnmatched = true;
                break;
            }
        }

        $allDistricts = $districtOrder;
        if ($hasUnmatched) {
            $allDistricts[] = '_unmatched';
        }

        // Сортируем периоды
        $sortedPeriods = array_keys($allPeriodKeys);
        usort($sortedPeriods, function ($a, $b) {
            if ($a === 'all') return 1;
            if ($b === 'all') return -1;
            return strcmp($a, $b);
        });

        $rows = [];
        $totalsRequests = array_fill_keys($allDistricts, 0);
        $totalsWeight = array_fill_keys($allDistricts, 0);
        $grRequests = 0;
        $grWeight = 0.0;

        foreach ($sortedPeriods as $pk) {
            $row = [
                'period_key'            => $pk,
                'period_label'          => $this->formatPeriodLabel($pk, $period, $this->lastDateFrom ?? '', $this->lastDateTo ?? ''),
                'requests_by_district'  => [],
                'weight_by_district'    => [],
                'requests_total'        => 0,
                'weight_total'          => 0,
            ];

            foreach ($allDistricts as $dk) {
                $districtData = $matrix[$pk][$dk] ?? [];
                $zIds = array_keys($districtData);
                $count = count($zIds);
                $weight = 0;
                foreach ($zIds as $zId) {
                    $weight += ($zayavkaMap[$zId]['mass_netto'] ?? 0) * 1000;
                }
                $row['requests_by_district'][$dk] = $count;
                $row['weight_by_district'][$dk] = (int) round($weight);
                $totalsRequests[$dk] += $count;
                $totalsWeight[$dk] += (int) round($weight);
                $row['requests_total'] += $count;
                $row['weight_total'] += (int) round($weight);
            }

            $grRequests += $row['requests_total'];
            $grWeight += $row['weight_total'];
            $rows[] = $row;
        }

        $totals = [
            'requests_by_district' => $totalsRequests,
            'weight_by_district'   => $totalsWeight,
        ];

        return [
            'summary' => [
                'rows_total'      => count($rows),
                'requests_total'  => $grRequests,
                'weight_total_kg' => (int) round($grWeight),
                'avg_request_kg'  => $grRequests > 0 ? (int) round($grWeight / $grRequests) : 0,
            ],
            'rows'            => $rows,
            'unmatched'       => $unmatched,
            'districts'       => $allDistricts,
            'district_titles' => $districtTitles + ($hasUnmatched ? ['_unmatched' => 'Н/Д'] : []),
            'has_unmatched'   => $hasUnmatched,
            'totals'          => $totals,
        ];
    }

    /**
     * Построить chart data для ФО (multi-line chart).
     */
    private function buildChartDataFO(array $reportData, string $metric): array
    {
        $rows = $reportData['rows'] ?? [];
        $districts = $reportData['districts'] ?? [];
        $districtTitles = $reportData['district_titles'] ?? [];

        if (empty($rows) || empty($districts)) {
            return ['enabled' => false, 'type' => 'line-multi', 'metric' => $metric, 'periods' => [], 'series' => []];
        }

        $periods = [];
        foreach ($rows as $row) {
            $periods[] = ['key' => $row['period_key'], 'label' => $row['period_label']];
        }

        $series = [];
        foreach ($districts as $dk) {
            $fullTitle = $this->districtMap->districtFullTitle($dk);
            $values = [];
            foreach ($rows as $row) {
                $values[$row['period_key']] = [
                    'requests' => $row['requests_by_district'][$dk] ?? 0,
                    'weight'   => $row['weight_by_district'][$dk] ?? 0,
                ];
            }
            // Проверяем, есть ли хоть одно ненулевое значение
            $hasData = false;
            foreach ($values as $v) {
                if (($v[$metric] ?? 0) > 0) {
                    $hasData = true;
                    break;
                }
            }
            if (!$hasData) continue;

            $series[] = [
                'key'    => $dk,
                'label'  => $districtTitles[$dk] ?? $dk,
                'title'  => $dk === '_unmatched' ? 'Не определено' : $fullTitle,
                'values' => $values,
            ];
        }

        // maxValue for Y scale
        $maxVal = 0;
        foreach ($series as $s) {
            foreach ($s['values'] as $v) {
                $mv = (int) ($v[$metric] ?? 0);
                if ($mv > $maxVal) $maxVal = $mv;
            }
        }

        return [
            'enabled'       => true,
            'type'          => 'line-multi',
            'metric'        => $metric,
            'periods'       => $periods,
            'series'        => $series,
            'maxValue'      => $maxVal,
            'districtTitles' => $districtTitles,
        ];
    }

    private function buildByRegion(array $flights, array $zayavkaMap, string $period): array
    {
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

        return $this->buildResult($rows, $grRequests, $grWeight, 0);
    }

    private function buildByStatus(array $flights, array $zayavkaMap, string $period): array
    {
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

        return $this->buildResult($rows, $grRequests, $grWeight, 0);
    }

    private function buildResult(array $rows, int $grRequests, float $grWeight, int $unmatched): array
    {
        $totalRows = count($rows);
        $avgReq = $grRequests > 0 ? (int) round($grWeight / $grRequests) : 0;

        return [
            'summary' => [
                'rows_total'      => $totalRows,
                'requests_total'  => $grRequests,
                'weight_total_kg' => (int) round($grWeight),
                'avg_request_kg'  => $avgReq,
            ],
            'rows'      => $rows,
            'unmatched' => $unmatched,
        ];
    }

    /**
     * Пустой результат для dimension.
     */
    private function emptyResult(string $dimension): array
    {
        $result = [
            'summary' => [
                'rows_total'      => 0,
                'requests_total'  => 0,
                'weight_total_kg' => 0,
                'avg_request_kg'  => 0,
            ],
            'rows'      => [],
            'unmatched' => 0,
        ];

        if ($dimension === 'fo') {
            $districtOrder = $this->districtMap->districtOrder();
            $districtTitles = $this->districtMap->districtTitles();
            $allDistricts = $districtOrder;
            $result['districts'] = $allDistricts;
            $result['district_titles'] = $districtTitles;
            $result['has_unmatched'] = false;
            $result['totals'] = [
                'requests_by_district' => array_fill_keys($allDistricts, 0),
                'weight_by_district'   => array_fill_keys($allDistricts, 0),
            ];
        }

        return $result;
    }

    // ===================================================================
    //  Helpers
    // ===================================================================

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
            'requests'       => $totalCount,
            'weight_kg'      => (int) round($totalWeight),
            'avg_request_kg' => $totalCount > 0 ? (int) round($totalWeight / $totalCount) : 0,
        ];
    }

    private function getPeriodKey(?string $date, string $period): string
    {
        if (empty($date)) return 'unknown';
        $ts = strtotime($date);
        if ($period === 'month') return date('Y-m', $ts);
        if ($period === 'week') return date('o-\WW', $ts);
        return 'all';
    }

    public function formatPeriodLabel(string $periodKey, string $period, string $dateFrom = '', string $dateTo = ''): string
    {
        if ($period === 'custom') {
            if (!$this->isValidDate($dateFrom) || !$this->isValidDate($dateTo)) {
                return 'Произвольный период';
            }
            $dFrom = new \DateTime($dateFrom);
            $dTo   = new \DateTime($dateTo);
            if ($dateFrom === $dateTo) {
                return $dFrom->format('d.m.Y');
            }
            return $dFrom->format('d.m.Y') . '–' . $dTo->format('d.m.Y');
        }

        if ($period === 'month') {
            $parts = explode('-', $periodKey);
            $y = (int) ($parts[0] ?? 0);
            $m = (int) ($parts[1] ?? 0);
            return (self::MONTH_FULL[$m] ?? (string) $m) . ' ' . $y;
        }

        if ($period === 'week' && preg_match('/^(\d{4})-W(\d{2})$/', $periodKey, $m)) {
            $year = (int) $m[1];
            $week = (int) $m[2];
            $dto  = new \DateTime();
            $dto->setISODate($year, $week);
            $monday = clone $dto;
            $sunday = clone $dto;
            $sunday->modify('+6 days');
            return $monday->format('d.m') . '–' . $sunday->format('d.m.Y');
        }

        return $periodKey;
    }

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

    /**
     * Полное название ФО по ключу (проброс в view).
     */
    public function districtFullTitle(string $key): string
    {
        return $this->districtMap->districtFullTitle($key);
    }

    // ===================================================================
    //  Normalization / validation
    // ===================================================================

    public function normalizePeriod(string $period): string
    {
        return in_array($period, ['week', 'month', 'custom'], true) ? $period : 'week';
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

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }

    private function resolveDimension(): string
    {
        if (isset($_GET['dimension'])) {
            return $this->normalizeDimension($_GET['dimension']);
        }
        $report = $_GET['report'] ?? '';
        return match ($report) {
            'delivered_regions' => 'region',
            'status_summary'    => 'status',
            default             => 'fo',
        };
    }

    // ===================================================================
    //  Статичные форматтеры
    // ===================================================================

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
