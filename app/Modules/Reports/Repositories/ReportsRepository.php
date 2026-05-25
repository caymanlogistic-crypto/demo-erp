<?php

declare(strict_types=1);

namespace App\Modules\Reports\Repositories;

use App\Core\Database\Connection;
use PDO;

/**
 * Репозиторий для модуля «Отчётность».
 *
 * Строит агрегированные данные из flights + feo без копирования
 * бизнес-логики Statistics.
 *
 * Использует ту же цепочку:
 *   flights.zayavki_ids → feo-заявки → mass_netto / mno_region / статус / даты.
 */
class ReportsRepository
{
    /**
     * Получить все завершённые рейсы (actual_end_date IS NOT NULL) с заявками.
     *
     * @return array<int, array{id: int|string, zayavki_ids: string, actual_end_date: string, actual_start_date: string|null, status: string|null}>
     */
    public function getCompletedFlights(): array
    {
        $pdo = Connection::get();
        if ($pdo === null) {
            return [];
        }

        try {
            $stmt = $pdo->query("
                SELECT id, zayavki_ids, actual_end_date, actual_start_date, status
                FROM flights
                WHERE actual_end_date IS NOT NULL
                ORDER BY actual_end_date ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('ReportsRepository getCompletedFlights error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить все рейсы (включая незавершённые) для статусной сводки.
     *
     * @return array<int, array{id: int|string, zayavki_ids: string, actual_end_date: string|null, actual_start_date: string|null, status: string|null}>
     */
    public function getAllFlights(): array
    {
        $pdo = Connection::get();
        if ($pdo === null) {
            return [];
        }

        try {
            $stmt = $pdo->query("
                SELECT id, zayavki_ids, actual_end_date, actual_start_date, status
                FROM flights
                ORDER BY id ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('ReportsRepository getAllFlights error: ' . $e->getMessage());
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
