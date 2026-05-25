<?php

declare(strict_types=1);

namespace App\Modules\Reports\Services;

use App\Modules\Reports\Repositories\ReportsRepository;
use App\Modules\Reports\Support\FederalDistrictRegionMap;

/**
 * Сервис построения отчётов.
 *
 * Три отчёта:
 *  - delivered_fo:     «Сдано по ФО» (только Груз сдан, группировка по ФО × месяцы)
 *  - delivered_regions: «Сдано по регионам» (только Груз сдан, группировка по регионам × месяцы)
 *  - status_summary:    «Сводка по статусам» (все рейсы, группировка по ФО × статусы)
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

    public function __construct()
    {
        $this->repository = new ReportsRepository();
        $this->districtMap = new FederalDistrictRegionMap();
    }

    // ===================================================================
    //  Отчёт 1: Сдано по ФО
    // ===================================================================

    /**
     * @return array{months: array, rows: array, totals: array, unmatched: int}
     */
    public function buildDeliveredByFO(): array
    {
        $flights = $this->repository->getCompletedFlights();
        if (empty($flights)) {
            return ['months' => [], 'rows' => [], 'totals' => [], 'unmatched' => 0];
        }

        // Собираем все zayavka_id → данные заявки
        [$zayavkaMap, $allZayavkaIds] = $this->collectZayavkiFromFlights($flights);

        // Определяем месяцы из actual_end_date
        $months = $this->extractMonths($flights);

        // Строим: districtKey → monthKey → zayavka_ids[]
        $matrix = [];
        $unmatched = 0;
        $unmatchedRegions = [];

        foreach ($flights as $flight) {
            $monthKey = $this->monthKey($flight['actual_end_date']);
            $ids = $this->parseZayavkaIds($flight['zayavki_ids']);

            foreach ($ids as $zId) {
                $data = $zayavkaMap[$zId] ?? null;
                if ($data === null) {
                    continue;
                }
                $region = $data['mno_region'];
                $districtKey = $this->districtMap->resolveShortName($region) ?? '_unmatched';
                if ($districtKey === '_unmatched') {
                    $unmatched++;
                    $normalized = $this->districtMap->normalizeRegion($region);
                    if ($normalized !== '') {
                        $unmatchedRegions[$normalized] = true;
                    }
                }
                $matrix[$districtKey][$monthKey][$zId] = true;
            }
        }

        // Строим строки
        $rows = [];
        $districtOrder = $this->districtMap->districtOrder();
        $districtTitles = $this->districtMap->districtTitles();

        $grandTotalWeight = 0.0;
        $grandTotalCount = 0;

        foreach ($districtOrder as $dk) {
            if (!isset($matrix[$dk])) {
                continue;
            }
            $row = $this->buildMonthRow($matrix[$dk], $months, $zayavkaMap);
            $row['district_key'] = $dk;
            $row['district_title'] = $districtTitles[$dk] ?? $dk;
            $row['district_full'] = $this->districtMap->districtFullTitle($dk);
            $rows[] = $row;
            $grandTotalWeight += $row['total_weight'];
            $grandTotalCount += $row['total_count'];
        }

        // Строка «Не определено»
        if (isset($matrix['_unmatched'])) {
            $row = $this->buildMonthRow($matrix['_unmatched'], $months, $zayavkaMap);
            $row['district_key'] = '_unmatched';
            $row['district_title'] = 'Не определено';
            $row['district_full'] = 'Регион не определён';
            $rows[] = $row;
            $grandTotalWeight += $row['total_weight'];
            $grandTotalCount += $row['total_count'];
        }

        // Общий итог
        $grandRow = [
            'district_key' => '_grand',
            'district_title' => 'Общий итог',
            'district_full' => 'Общий итог',
            'total_weight' => $grandTotalWeight,
            'total_count' => $grandTotalCount,
            'cells' => [],
            'is_total' => true,
        ];
        foreach ($months as $mk) {
            $w = 0.0;
            $c = 0;
            foreach ($rows as $r) {
                $w += $r['cells'][$mk]['weight'] ?? 0;
                $c += $r['cells'][$mk]['count'] ?? 0;
            }
            $grandRow['cells'][$mk] = ['weight' => $w, 'count' => $c];
        }

        return [
            'months' => $months,
            'rows' => $rows,
            'totals' => $grandRow,
            'unmatched' => $unmatched,
        ];
    }

    // ===================================================================
    //  Отчёт 2: Сдано по регионам
    // ===================================================================

    /**
     * @return array{months: array, rows: array, totals: array, unmatched: int}
     */
    public function buildDeliveredByRegions(): array
    {
        $flights = $this->repository->getCompletedFlights();
        if (empty($flights)) {
            return ['months' => [], 'rows' => [], 'totals' => [], 'unmatched' => 0];
        }

        [$zayavkaMap, $allZayavkaIds] = $this->collectZayavkiFromFlights($flights);
        $months = $this->extractMonths($flights);

        // regionNormalized → monthKey → zayavka_ids[]
        $matrix = [];
        $regionNames = []; // normalized → original display name

        foreach ($flights as $flight) {
            $monthKey = $this->monthKey($flight['actual_end_date']);
            $ids = $this->parseZayavkaIds($flight['zayavki_ids']);

            foreach ($ids as $zId) {
                $data = $zayavkaMap[$zId] ?? null;
                if ($data === null) {
                    continue;
                }
                $rawRegion = $data['mno_region'] ?? '';
                $normalized = $this->districtMap->normalizeRegion($rawRegion);
                $display = $rawRegion ?: '—';
                if ($normalized === '') {
                    $normalized = '_empty';
                    $display = 'Не указано';
                }
                $regionNames[$normalized] = $display;
                $matrix[$normalized][$monthKey][$zId] = true;
            }
        }

        // Строим строки
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

        // Сортируем: по total_count DESC, затем region asc
        usort($rows, function (array $a, array $b): int {
            $cmp = ($b['total_count'] ?? 0) <=> ($a['total_count'] ?? 0);
            if ($cmp !== 0) {
                return $cmp;
            }
            return ($a['region_display'] ?? '') <=> ($b['region_display'] ?? '');
        });

        // Общий итог
        $grandTotalWeight = 0.0;
        $grandTotalCount = 0;
        foreach ($rows as $r) {
            $grandTotalWeight += $r['total_weight'];
            $grandTotalCount += $r['total_count'];
        }

        $grandRow = [
            'region_normalized' => '_grand',
            'region_display' => 'Общий итог',
            'district_key' => '',
            'district_short' => '',
            'total_weight' => $grandTotalWeight,
            'total_count' => $grandTotalCount,
            'cells' => [],
            'is_total' => true,
        ];
        foreach ($months as $mk) {
            $w = 0.0;
            $c = 0;
            foreach ($rows as $r) {
                $w += $r['cells'][$mk]['weight'] ?? 0;
                $c += $r['cells'][$mk]['count'] ?? 0;
            }
            $grandRow['cells'][$mk] = ['weight' => $w, 'count' => $c];
        }

        return [
            'months' => $months,
            'rows' => $rows,
            'totals' => $grandRow,
            'unmatched' => 0,
        ];
    }

    // ===================================================================
    //  Отчёт 3: Сводка по статусам
    // ===================================================================

    /**
     * Статусы в порядке отображения.
     */
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

    /**
     * @return array{statuses: array, rows: array, totals: array, unmatched: int}
     */
    public function buildStatusSummary(): array
    {
        $flights = $this->repository->getAllFlights();
        if (empty($flights)) {
            return ['statuses' => [], 'rows' => [], 'totals' => [], 'unmatched' => 0];
        }

        [$zayavkaMap, $allZayavkaIds] = $this->collectZayavkiFromFlights($flights);

        // districtKey → statusKey → zayavka_ids[]
        $matrix = [];
        $unmatched = 0;

        foreach ($flights as $flight) {
            $statusKey = $this->resolveFlightStatus($flight);
            $ids = $this->parseZayavkaIds($flight['zayavki_ids']);

            foreach ($ids as $zId) {
                $data = $zayavkaMap[$zId] ?? null;
                if ($data === null) {
                    continue;
                }
                $region = $data['mno_region'];
                $districtKey = $this->districtMap->resolveShortName($region) ?? '_unmatched';
                if ($districtKey === '_unmatched') {
                    $unmatched++;
                }
                $matrix[$districtKey][$statusKey][$zId] = true;
            }
        }

        // Строим строки
        $rows = [];
        $districtOrder = $this->districtMap->districtOrder();
        $districtTitles = $this->districtMap->districtTitles();

        $statuses = self::STATUS_ORDER;

        $grandTotalWeight = 0.0;
        $grandTotalCount = 0;

        foreach ($districtOrder as $dk) {
            if (!isset($matrix[$dk])) {
                continue;
            }
            $row = $this->buildStatusRow($matrix[$dk], $statuses, $zayavkaMap);
            $row['district_key'] = $dk;
            $row['district_title'] = $districtTitles[$dk] ?? $dk;
            $row['district_full'] = $this->districtMap->districtFullTitle($dk);
            $rows[] = $row;
            $grandTotalWeight += $row['total_weight'];
            $grandTotalCount += $row['total_count'];
        }

        // «Не определено»
        if (isset($matrix['_unmatched'])) {
            $row = $this->buildStatusRow($matrix['_unmatched'], $statuses, $zayavkaMap);
            $row['district_key'] = '_unmatched';
            $row['district_title'] = 'Не определено';
            $row['district_full'] = 'Регион не определён';
            $rows[] = $row;
            $grandTotalWeight += $row['total_weight'];
            $grandTotalCount += $row['total_count'];
        }

        // Общий итог
        $grandRow = [
            'district_key' => '_grand',
            'district_title' => 'Общий итог',
            'district_full' => 'Общий итог',
            'total_weight' => $grandTotalWeight,
            'total_count' => $grandTotalCount,
            'cells' => [],
            'is_total' => true,
        ];
        foreach ($statuses as $sk) {
            $w = 0.0;
            $c = 0;
            foreach ($rows as $r) {
                $w += $r['cells'][$sk]['weight'] ?? 0;
                $c += $r['cells'][$sk]['count'] ?? 0;
            }
            $grandRow['cells'][$sk] = ['weight' => $w, 'count' => $c];
        }

        return [
            'statuses' => $statuses,
            'rows' => $rows,
            'totals' => $grandRow,
            'unmatched' => $unmatched,
        ];
    }

    // ===================================================================
    //  Helpers
    // ===================================================================

    /**
     * Собрать все zayavka_id из рейсов и получить данные заявок.
     *
     * @return array{array, string[]} [zayavkaMap, allIds]
     */
    private function collectZayavkiFromFlights(array $flights): array
    {
        $allIds = [];
        foreach ($flights as $flight) {
            $ids = $this->parseZayavkaIds($flight['zayavki_ids']);
            foreach ($ids as $id) {
                $allIds[$id] = true;
            }
        }
        $uniqueIds = array_keys($allIds);
        $zayavkaMap = $this->repository->getZayavkiData($uniqueIds);
        return [$zayavkaMap, $uniqueIds];
    }

    /**
     * Разобрать zayavki_ids в массив строковых ID.
     *
     * @return string[]
     */
    private function parseZayavkaIds(string $zayavkiIds): array
    {
        $parts = array_filter(array_map('trim', explode(',', $zayavkiIds)));
        return array_values($parts);
    }

    /**
     * Извлечь отсортированные ключи месяцев из completed flights.
     *
     * @return string[] e.g. ['2026-03', '2026-04', ...]
     */
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

    /**
     * Ключ месяца: '2026-03'.
     */
    private function monthKey(string $date): string
    {
        $ts = strtotime($date);
        return date('Y-m', $ts);
    }

    /**
     * Формат месяца для отображения.
     *
     * Если все месяцы одного года: «Мар», «Апр».
     * Если несколько лет: «Мар 2026», «Апр 2026».
     *
     * @param string[] $monthKeys все ключи месяцев
     * @param string   $monthKey  конкретный ключ
     */
    public function formatMonthLabel(array $monthKeys, string $monthKey): string
    {
        $parts = explode('-', $monthKey);
        $year = (int) ($parts[0] ?? 0);
        $month = (int) ($parts[1] ?? 0);
        $short = self::MONTH_NAMES[$month] ?? (string) $month;

        // Проверяем, есть ли несколько лет
        $years = [];
        foreach ($monthKeys as $mk) {
            $yp = explode('-', $mk);
            $years[(int) ($yp[0] ?? 0)] = true;
        }
        if (count($years) > 1) {
            return $short . ' ' . $year;
        }
        return $short;
    }

    /**
     * Построить строку для месячной матрицы.
     *
     * @param array<string, array<string, true>> $monthData monthKey → [zId => true]
     * @param string[] $months
     * @param array $zayavkaMap
     * @return array
     */
    private function buildMonthRow(array $monthData, array $months, array $zayavkaMap): array
    {
        $cells = [];
        $totalWeight = 0.0;
        $totalCount = 0;

        foreach ($months as $mk) {
            $zIds = array_keys($monthData[$mk] ?? []);
            $weight = 0.0;
            foreach ($zIds as $zId) {
                $weight += ($zayavkaMap[$zId]['mass_netto'] ?? 0) * 1000;
            }
            $count = count($zIds);
            $cells[$mk] = ['weight' => $weight, 'count' => $count];
            $totalWeight += $weight;
            $totalCount += $count;
        }

        return [
            'cells' => $cells,
            'total_weight' => $totalWeight,
            'total_count' => $totalCount,
        ];
    }

    /**
     * Построить строку для статусной матрицы.
     *
     * @param array<string, array<string, true>> $statusData statusKey → [zId => true]
     * @param string[] $statuses
     * @param array $zayavkaMap
     * @return array
     */
    private function buildStatusRow(array $statusData, array $statuses, array $zayavkaMap): array
    {
        $cells = [];
        $totalWeight = 0.0;
        $totalCount = 0;

        foreach ($statuses as $sk) {
            $zIds = array_keys($statusData[$sk] ?? []);
            $weight = 0.0;
            foreach ($zIds as $zId) {
                $weight += ($zayavkaMap[$zId]['mass_netto'] ?? 0) * 1000;
            }
            $count = count($zIds);
            $cells[$sk] = ['weight' => $weight, 'count' => $count];
            $totalWeight += $weight;
            $totalCount += $count;
        }

        return [
            'cells' => $cells,
            'total_weight' => $totalWeight,
            'total_count' => $totalCount,
        ];
    }

    /**
     * Определить статус рейса для отчёта.
     *
     * Логика:
     *  - Груз сдан:       actual_end_date IS NOT NULL
     *  - Вывоз начался:    actual_start_date IS NOT NULL AND actual_end_date IS NULL
     *  - Исполнитель найден: flight.status = 'found' AND actual_start_date IS NULL
     *  - Планируемый:      всё остальное
     *
     * @param array $flight
     * @return string 'completed'|'started'|'found'|'planned'
     */
    private function resolveFlightStatus(array $flight): string
    {
        $actualEnd = $flight['actual_end_date'] ?? null;
        $actualStart = $flight['actual_start_date'] ?? null;
        $status = $flight['status'] ?? null;

        if ($actualEnd !== null && $actualEnd !== '') {
            return 'completed';
        }
        if ($actualStart !== null && $actualStart !== '') {
            return 'started';
        }
        if ($status === 'found') {
            return 'found';
        }
        return 'planned';
    }

    /**
     * Получить label статуса для заголовка таблицы.
     */
    public function statusLabel(string $statusKey): string
    {
        return self::STATUS_LABELS[$statusKey] ?? $statusKey;
    }

    /**
     * Получить полный title статуса.
     */
    public function statusTitle(string $statusKey): string
    {
        return self::STATUS_TITLES[$statusKey] ?? $statusKey;
    }

    /**
     * Форматирование веса с разделением тысяч.
     */
    public static function formatWeight(float $kg): string
    {
        if ($kg == 0) {
            return '—';
        }
        return number_format((int) round($kg), 0, '.', ' ') . ' кг';
    }

    /**
     * Форматирование количества.
     */
    public static function formatCount(int $count): string
    {
        if ($count === 0) {
            return '—';
        }
        return (string) $count;
    }
}
