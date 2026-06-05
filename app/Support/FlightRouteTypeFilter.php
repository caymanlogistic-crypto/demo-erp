<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Фильтр складских рейсов для модулей Statistics / Reports.
 *
 * Основная логика — по полю flights.route_type.
 * Fallback по unload_type НЕ используется, так как:
 *   - unload_type сам по себе описывает точку выгрузки (SKLAD/OO), а не источник рейса;
 *   - по unload_type невозможно надёжно отличить generator_to_warehouse от warehouse_to_warehouse;
 *   - нельзя исключать все unload_type=SKLAD, потому что generator_to_warehouse — допустимая поставка.
 */
final class FlightRouteTypeFilter
{
    private const PICKUP_EXCLUDED = ['warehouse_to_warehouse', 'warehouse_to_utilizer'];
    private const DELIVERY_EXCLUDED = ['warehouse_to_warehouse'];

    /**
     * Должен ли рейс учитываться для указанного типа даты события.
     *
     * @param array<string, mixed> $flight   Массив с ключом route_type
     * @param string               $dateType 'pickup' или 'delivery'
     */
    public static function shouldIncludeForDateType(array $flight, string $dateType): bool
    {
        $routeType = trim((string) ($flight['route_type'] ?? ''));

        if ($routeType === '') {
            return true;
        }

        if ($dateType === 'pickup') {
            return !in_array($routeType, self::PICKUP_EXCLUDED, true);
        }

        if ($dateType === 'delivery') {
            return !in_array($routeType, self::DELIVERY_EXCLUDED, true);
        }

        return true;
    }

    /**
     * Отфильтровать массив рейсов и вернуть статистику фильтрации.
     *
     * @param array<int, array<string, mixed>> $flights
     * @param string $dateType
     * @return array{flights: array<int, array<string, mixed>>, stats: array<string, int>}
     */
    public static function filterFlights(array $flights, string $dateType): array
    {
        $totalBefore = count($flights);
        $excludedWW = 0;
        $excludedWU = 0;

        $filtered = [];
        foreach ($flights as $flight) {
            if (self::shouldIncludeForDateType($flight, $dateType)) {
                $filtered[] = $flight;
                continue;
            }

            $routeType = trim((string) ($flight['route_type'] ?? ''));
            if ($routeType === 'warehouse_to_warehouse') {
                $excludedWW++;
            } elseif ($routeType === 'warehouse_to_utilizer') {
                $excludedWU++;
            }
        }

        return [
            'flights' => $filtered,
            'stats'   => [
                'total_before'                 => $totalBefore,
                'excluded_warehouse_to_warehouse' => $excludedWW,
                'excluded_warehouse_to_utilizer'  => $excludedWU,
                'total_after'                  => count($filtered),
            ],
        ];
    }
}
