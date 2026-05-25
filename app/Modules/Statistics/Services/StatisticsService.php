<?php

declare(strict_types=1);

namespace App\Modules\Statistics\Services;

use App\Modules\Statistics\Repositories\StatisticsRepository;

/**
 * Сервис для группировки статистики вывозов по периодам.
 *
 * Группировка: week (ISO), month.
 * Основная дата: actual_end_date.
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
     * @return array{period: string, date_from: string, date_to: string, warning: string|null}
     */
    public function normalizeFilters(): array
    {
        $period   = $this->normalizePeriod($_GET['period'] ?? 'week');
        $dateType = $this->normalizeDateType($_GET['date_type'] ?? 'delivery');
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to'] ?? '';
        $warning  = null;

        $defaultFrom = date('Y-m-d', strtotime('-12 weeks'));
        $defaultTo   = date('Y-m-d');

        // Validate date_from
        if ($dateFrom === '' || !$this->isValidDate($dateFrom)) {
            if ($dateFrom !== '') {
                $warning = 'Период был скорректирован автоматически';
            }
            $dateFrom = $defaultFrom;
        }

        // Validate date_to
        if ($dateTo === '' || !$this->isValidDate($dateTo)) {
            if ($dateTo !== '') {
                $warning = 'Период был скорректирован автоматически';
            }
            $dateTo = $defaultTo;
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
            return [
                'filters' => [
                    'period'    => $period,
                    'date_type' => $dateType,
                    'date_from' => $dateFrom,
                    'date_to'   => $dateTo,
                ],
                'summary' => [
                    'periods_count'         => 0,
                    'requests_total'        => 0,
                    'flights_total'         => 0,
                    'weight_total_kg'       => 0,
                    'avg_request_weight_kg' => 0,
                    'avg_flight_weight_kg'  => 0,
                ],
                'rows' => [],
            ];
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

            $rows[] = [
                'period_key'          => $periodKey,
                'period_label'        => $label,
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
     */
    public function formatPeriodLabel(string $periodKey, string $period): string
    {
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
        return in_array($period, ['week', 'month'], true) ? $period : 'week';
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
}
