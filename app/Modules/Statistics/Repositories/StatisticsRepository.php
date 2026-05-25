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
    /**
     * Получить завершённые рейсы за диапазон actual_end_date.
     *
     * @return array<int, array{id: int, zayavki_ids: string, actual_end_date: string}>
     */
    public function getCompletedFlights(string $dateFrom, string $dateTo): array
    {
        $pdo = Connection::get();
        if ($pdo === null) {
            return [];
        }

        try {
            $stmt = $pdo->prepare("
                SELECT id, zayavki_ids, actual_end_date
                FROM flights
                WHERE actual_end_date IS NOT NULL
                  AND actual_start_date IS NOT NULL
                  AND actual_end_date BETWEEN :date_from AND :date_to
                ORDER BY actual_end_date ASC
            ");
            $stmt->execute([
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('StatisticsRepository getCompletedFlights error: ' . $e->getMessage());
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
