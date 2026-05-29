<?php

declare(strict_types=1);

namespace App\Modules\Statistics\Repositories;

use App\Core\Database\Connection;
use PDO;

/**
 * Репозиторий для чтения данных статистики вывозов.
 *
 * Источник: завершённые рейсы (actual_end_date IS NOT NULL) + заявки из feo.
 */
class StatisticsRepository
{
    /** @var string[] Безопасный allowlist колонок дат */
    private const ALLOWED_DATE_COLUMNS = ['actual_end_date', 'actual_start_date'];

    /**
     * Получить рейсы за диапазон по выбранной дате события.
     *
     * @param string $dateType 'delivery' → actual_end_date, 'pickup' → actual_start_date
     * @return array<int, array{id: int, zayavki_ids: string, event_date: string}>
     */
    public function getFlightsByEventDate(string $dateFrom, string $dateTo, string $dateType = 'delivery'): array
    {
        $pdo = Connection::get();
        if ($pdo === null) {
            return [];
        }

        $dateColumn = ($dateType === 'pickup') ? 'actual_start_date' : 'actual_end_date';

        if (!in_array($dateColumn, self::ALLOWED_DATE_COLUMNS, true)) {
            $dateColumn = 'actual_end_date';
        }

        try {
            $stmt = $pdo->prepare("
            SELECT id, zayavki_ids, {$dateColumn} AS event_date
            FROM flights
            WHERE {$dateColumn} IS NOT NULL
              AND DATE({$dateColumn}) BETWEEN :date_from AND :date_to
            ORDER BY {$dateColumn} ASC
        ");
            $stmt->execute([
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('StatisticsRepository getFlightsByEventDate error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить заявки (zayavka_id, mass_netto) для списка ID.
     *
     * @param string[] $zayavkaIds
     * @return array<string, float> zayavka_id → mass_netto (в тоннах)
     */
    public function getZayavkiMass(array $zayavkaIds): array
    {
        if (empty($zayavkaIds)) {
            return [];
        }

        $pdo = Connection::get();
        if ($pdo === null) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($zayavkaIds), '?'));
            $stmt = $pdo->prepare("
                SELECT zayavka_id, mass_netto
                FROM feo
                WHERE zayavka_id IN ({$placeholders})
            ");
            $stmt->execute($zayavkaIds);
            $map = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $map[(string) $row['zayavka_id']] = (float) ($row['mass_netto'] ?? 0);
            }
            return $map;
        } catch (\Exception $e) {
            error_log('StatisticsRepository getZayavkiMass error: ' . $e->getMessage());
            return [];
        }
    }
}
