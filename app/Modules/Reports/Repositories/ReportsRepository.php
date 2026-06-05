<?php

declare(strict_types=1);

namespace App\Modules\Reports\Repositories;

use App\Core\Database\Connection;
use PDO;

/**
 * Репозиторий для модуля «Отчётность».
 */
class ReportsRepository
{
    /**
     * Получить все рейсы с zayavki_ids и датами, отфильтрованными по диапазону.
     *
     * @param string $dateType 'delivery' -> actual_end_date, 'pickup' -> actual_start_date
     * @param string $dateFrom YYYY-MM-DD
     * @param string $dateTo   YYYY-MM-DD
     * @return array<int, array{id: int|string, zayavki_ids: string, actual_start_date: string|null, actual_end_date: string|null, status: string|null, event_date: string|null}>
     */
    public function getFlightsForReports(string $dateType, string $dateFrom, string $dateTo): array
    {
        $pdo = Connection::get();
        if ($pdo === null) {
            return [];
        }

        $dateColumn = ($dateType === 'pickup') ? 'actual_start_date' : 'actual_end_date';

        try {
            $stmt = $pdo->prepare("
                SELECT id, zayavki_ids, route_type, unload_type,
                       actual_start_date, actual_end_date, status,
                       {$dateColumn} AS event_date
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
            error_log('ReportsRepository getFlightsForReports error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить данные заявок по списку ID.
     *
     * @param string[] $zayavkaIds
     * @return array<string, array{zayavka_id: string, mass_netto: float, mno_region: string|null}>
     */
    public function getZayavkiData(array $zayavkaIds): array
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
                SELECT zayavka_id, mass_netto, mno_region
                FROM feo
                WHERE zayavka_id IN ({$placeholders})
            ");
            $stmt->execute($zayavkaIds);
            $map = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $map[(string) $row['zayavka_id']] = [
                    'zayavka_id' => (string) $row['zayavka_id'],
                    'mass_netto' => (float) ($row['mass_netto'] ?? 0),
                    'mno_region' => $row['mno_region'] ?? null,
                ];
            }
            return $map;
        } catch (\Exception $e) {
            error_log('ReportsRepository getZayavkiData error: ' . $e->getMessage());
            return [];
        }
    }
}
