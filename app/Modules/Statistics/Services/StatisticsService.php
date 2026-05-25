<?php

declare(strict_types=1);

namespace App\Modules\Statistics\Services;

use App\Modules\Statistics\Repositories\StatisticsRepository;

/**
 * Сервис для группировки статистики вывозов по периодам.
 *
 * Группировка: week (ISO), month, custom.
 * Основная дата: actual_end_date (delivery) или actual_start_date (pickup).
 */
class StatisticsService
{
    private StatisticsRepository $repository;

    public function __construct()
    {
        $this->repository = new StatisticsRepository();
    }

    /**
     * Нормализовать фильтры и вернуть безопасные значения.
     *
     * @return array{period: string, date_type: string, date_from: string, date_to: string, warning: string|null}
     */
    public function normalizeFilters(): array
    {
        $period   = $this->normalizePeriod($_GET['period'] ?? 'week');
        $dateType = $this->normalizeDateType($_GET['date_type'] ?? 'pickup');
        $warning  = null;

        $defaultFrom = date('Y-m-d', strtotime('-12 weeks'));
        $defaultTo   = date('Y-m-d');

        if ($period === 'custom') {
            // Для custom используем date_from/date_to из GET
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
            // Для week/month используем системный default, игнорируем GET date_from/date_to
            $dateFrom = $defaultFrom;
            $dateTo   = $defaultTo;
        }

        return [
            'period'    => $period,
            'date_type' => $dateType,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'warning'   => $warning,
        ];
    }

    /**
     * Построить статистику за период.
     *
     * @return array{filters: array, summary: array, rows: array}
     */
    public function buildStatistics(string $period, string $dateType, string $dateFrom, string $dateTo): array
    {
        $flights = $this->repository->getFlightsByEventDate($dateFrom, $dateTo, $dateType);

        if (empty($flights)) {
            $periodsCount = 0;
            return [
                'filters' => [
                    'period'    => $period,
                    'date_type' => $dateType,
                    'date_from' => $dateFrom,
                    'date_to'   => $dateTo,
                ],
                'summary' => [
                    'periods_count'         => $periodsCount,
                    'requests_total'        => 0,
                    'flights_total'         => 0,
                    'weight_total_kg'       => 0,
                    'avg_request_weight_kg' => 0,
                    'avg_flight_weight_kg'  => 0,
                ],
                'rows' => [],
            ];
        }

        // custom: одна строка, не группируем
        if ($period === 'custom') {
            return $this->buildCustomPeriodRow($flights, $period, $dateType, $dateFrom, $dateTo);
        }

        // Собираем все zayavka_id из рейсов
        $allZayavkaIds = [];
        foreach ($flights as $flight) {
            $ids = array_filter(explode(',', $flight['zayavki_ids']));
            foreach ($ids as $id) {
                $id = trim($id);
                if ($id !== '') {
                    $allZayavkaIds[$id] = true;
                }
            }
        }
        $uniqueZIds = array_keys($allZayavkaIds);

        // Получаем массы заявок одним запросом
        $massMap = $this->repository->getZayavkiMass($uniqueZIds);

        // Группируем рейсы по периоду
        $grouped = [];
        foreach ($flights as $flight) {
            $key = $this->getPeriodKey($flight['event_date'], $period);
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'flights'   => [],
                    'zayavka_ids' => [], // уникальные внутри периода
                ];
            }
            $grouped[$key]['flights'][] = $flight;

            $ids = array_filter(explode(',', $flight['zayavki_ids']));
            foreach ($ids as $id) {
                $id = trim($id);
                if ($id !== '') {
                    $grouped[$key]['zayavka_ids'][$id] = true;
                }
            }
        }

        // Строим rows
        $rows = [];
        $totalRequests = 0;
        $totalWeight   = 0.0;
        $totalFlights  = 0;

        foreach ($grouped as $periodKey => $data) {
            $periodFlights   = count($data['flights']);
            $periodZayavkaIds = array_keys($data['zayavka_ids']);
            $periodRequests   = count($periodZayavkaIds);
            $periodWeight     = 0.0;

            foreach ($periodZayavkaIds as $zId) {
                $periodWeight += ($massMap[$zId] ?? 0) * 1000;
            }

            $avgRequestWeight = $periodRequests > 0
                ? (int) round($periodWeight / $periodRequests)
                : 0;

            $avgFlightWeight = $periodFlights > 0
                ? (int) round($periodWeight / $periodFlights)
                : 0;

            $label = $this->formatPeriodLabel($periodKey, $period);

            $weekNumber = null;
            if ($period === 'week' && preg_match('/-W(\d{2})$/', $periodKey, $wm)) {
                $weekNumber = (int) $wm[1];
            }

            $rows[] = [
                'period_key'          => $periodKey,
                'period_label'        => $label,
                'week_number'         => $weekNumber,
                'requests_count'      => $periodRequests,
                'flights_count'       => $periodFlights,
                'total_weight_kg'     => (int) round($periodWeight),
                'avg_request_weight_kg' => $avgRequestWeight,
                'avg_flight_weight_kg'  => $avgFlightWeight,
            ];

            $totalRequests += $periodRequests;
            $totalWeight   += $periodWeight;
            $totalFlights  += $periodFlights;
        }

        // Сортируем по ключу периода
        ksort($rows);

        $totalAvgRequest = $totalRequests > 0 ? (int) round($totalWeight / $totalRequests) : 0;
        $totalAvgFlight  = $totalFlights > 0 ? (int) round($totalWeight / $totalFlights) : 0;

        return [
            'filters' => [
                'period'    => $period,
                'date_type' => $dateType,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ],
            'summary' => [
                'periods_count'         => count($rows),
                'requests_total'        => $totalRequests,
                'flights_total'         => $totalFlights,
                'weight_total_kg'       => (int) round($totalWeight),
                'avg_request_weight_kg' => $totalAvgRequest,
                'avg_flight_weight_kg'  => $totalAvgFlight,
            ],
            'rows' => $rows,
        ];
    }

    /**
     * Построить одну строку для произвольного периода (custom).
     */
    private function buildCustomPeriodRow(array $flights, string $period, string $dateType, string $dateFrom, string $dateTo): array
    {
        // Собираем уникальные zayavka_id
        $zayavkaIds = [];
        foreach ($flights as $flight) {
            $ids = array_filter(explode(',', $flight['zayavki_ids']));
            foreach ($ids as $id) {
                $id = trim($id);
                if ($id !== '') {
                    $zayavkaIds[$id] = true;
                }
            }
        }
        $uniqueZIds = array_keys($zayavkaIds);

        $massMap = $this->repository->getZayavkiMass($uniqueZIds);

        $periodFlights  = count($flights);
        $periodRequests = count($uniqueZIds);
        $periodWeight   = 0.0;

        foreach ($uniqueZIds as $zId) {
            $periodWeight += ($massMap[$zId] ?? 0) * 1000;
        }

        $avgRequestWeight = $periodRequests > 0 ? (int) round($periodWeight / $periodRequests) : 0;
        $avgFlightWeight  = $periodFlights > 0 ? (int) round($periodWeight / $periodFlights) : 0;

        $label = $this->formatPeriodLabel('custom', $period, $dateFrom, $dateTo);

        $row = [
            'period_key'           => 'custom',
            'period_label'         => $label,
            'week_number'          => null,
            'requests_count'       => $periodRequests,
            'flights_count'        => $periodFlights,
            'total_weight_kg'      => (int) round($periodWeight),
            'avg_request_weight_kg' => $avgRequestWeight,
            'avg_flight_weight_kg'  => $avgFlightWeight,
        ];

        return [
            'filters' => [
                'period'    => $period,
                'date_type' => $dateType,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ],
            'summary' => [
                'periods_count'         => 1,
                'requests_total'        => $periodRequests,
                'flights_total'         => $periodFlights,
                'weight_total_kg'       => (int) round($periodWeight),
                'avg_request_weight_kg' => $avgRequestWeight,
                'avg_flight_weight_kg'  => $avgFlightWeight,
            ],
            'rows' => [$row],
        ];
    }

    /**
     * Ключ периода: "2026-W21" или "2026-05".
     */
    private function getPeriodKey(string $date, string $period): string
    {
        $ts = strtotime($date);
        if ($period === 'month') {
            return date('Y-m', $ts);
        }
        // week: ISO-8601
        return date('o-\WW', $ts);
    }

    /**
     * Человекочитаемый label периода.
     *
     * @param string $dateFrom Используется только для custom
     * @param string $dateTo   Используется только для custom
     */
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
            $year  = (int) ($parts[0] ?? 0);
            $month = (int) ($parts[1] ?? 0);
            $months = [
                1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
                5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
                9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
            ];
            return ($months[$month] ?? $month) . ' ' . $year;
        }

        // week: "2026-W21" → "19.05–25.05.2026"
        if (preg_match('/^(\d{4})-W(\d{2})$/', $periodKey, $m)) {
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

    private function normalizePeriod(string $period): string
    {
        return in_array($period, ['week', 'month', 'custom'], true) ? $period : 'week';
    }

    private function normalizeDateType(string $dateType): string
    {
        return in_array($dateType, ['delivery', 'pickup'], true) ? $dateType : 'delivery';
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }

    /**
     * Подготовить данные для SVG-графика.
     *
     * @param array $rows    Строки статистики из buildStatistics()
     * @param string $period week|month|custom
     * @return array{enabled: bool, period: string, date_type: string, date_type_label: string, period_label: string, items: array}
     */
    public function buildChartData(array $rows, string $period, string $dateType): array
    {
        if ($period === 'custom' || empty($rows)) {
            return [
                'enabled'         => false,
                'period'          => $period,
                'date_type'       => $dateType,
                'date_type_label' => '',
                'period_label'    => '',
                'items'           => [],
            ];
        }

        $dateTypeLabel = $dateType === 'pickup' ? 'по дате вывоза' : 'по дате доставки';
        $periodLabel   = $period === 'month' ? 'по месяцам' : 'по неделям';

        $items = [];
        foreach ($rows as $row) {
            $shortLabel = $row['period_key'];
            if ($period === 'week' && preg_match('/-W(\d{2})$/', $row['period_key'], $m)) {
                $shortLabel = (string) (int) $m[1];
            } elseif ($period === 'month') {
                $parts = explode('-', $row['period_key']);
                $month = (int) ($parts[1] ?? 0);
                $monNames = ['', 'Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
                $shortLabel = $monNames[$month] ?? $row['period_key'];
            }

            $items[] = [
                'label'       => $row['period_label'],
                'short_label' => $shortLabel,
                'week_number' => $row['week_number'] ?? null,
                'requests'    => (int) $row['requests_count'],
                'flights'     => (int) $row['flights_count'],
                'weight'      => (int) $row['total_weight_kg'],
            ];
        }

        return [
            'enabled'         => true,
            'period'          => $period,
            'date_type'       => $dateType,
            'date_type_label' => $dateTypeLabel,
            'period_label'    => $periodLabel,
            'items'           => $items,
        ];
    }
}
