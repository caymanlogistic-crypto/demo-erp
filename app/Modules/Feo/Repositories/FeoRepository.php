<?php

declare(strict_types=1);

namespace App\Modules\Feo\Repositories;

use App\Core\Database\Connection;
use PDO;

/**
 * Репозиторий для чтения данных из таблиц feo, status_blocks, flights.
 *
 * Фильтры (логика из index22.php):
 *  - "available"   — JOIN status_blocks WHERE status_type = 'dostupno'
 *  - "routes"      — WHERE napravlenie IS NOT NULL AND napravlenie != ''
 *  - "flights"     — INNER JOIN flights (flight_id IS NOT NULL)
 */
class FeoRepository
{
    /**
     * Получить отфильтрованные строки заявок с пагинацией.
     *
     * @param string[] $zayavkaIds  ID заявок для фильтра «по номерам» (из поля feo.zayavka_id)
     * @param string   $filterType  all | available | routes | flights
     * @param int      $offset
     * @param int      $limit
     * @return array{rows: array, total: int, foundZayavki: array, missingZayavki: array}
     */
    public function getFilteredRows(
        array $zayavkaIds,
        string $filterType,
        int $offset = 0,
        int $limit = 50
    ): array {
        $pdo = Connection::get();

        if ($pdo === null) {
            return [
                'rows'          => [],
                'total'         => 0,
                'foundZayavki'   => [],
                'missingZayavki' => $zayavkaIds,
            ];
        }

        $limit   = min(100, max(10, $limit));
        $hasNums = !empty($zayavkaIds);

        // --- строим SQL по логике index22.php ---
        // Базовые поля + LEFT JOIN для отображения статуса и рейса
        $select = 'f.*, sb.наименование AS status_name, fl.comment AS flight_name';
        $from   = 'feo f'
                . ' LEFT JOIN status_blocks sb ON f.status_id = sb.id'
                . ' LEFT JOIN flights fl ON f.flight_id = fl.id';

        $where   = [];
        $params  = [];

        // 1) Фильтр по номерам заявок (поле zayavka_id)
        if ($hasNums) {
            $placeholders = implode(',', array_fill(0, count($zayavkaIds), '?'));
            $where[] = "f.zayavka_id IN ($placeholders)";
            $params = array_merge($params, $zayavkaIds);
        }

        // 2) Фильтр по типу (логика index22.php)
        switch ($filterType) {
            case 'available':
                // Доступные — status_blocks.status_type = 'dostupno'
                $where[] = 'sb.status_type = ?';
                $params[] = 'dostupno';
                break;

            case 'routes':
                // Маршруты — есть направление
                $where[] = "f.napravlenie IS NOT NULL AND f.napravlenie != ''";
                break;

            case 'flights':
                // Рейсы — привязаны к flights
                $where[] = 'f.flight_id IS NOT NULL';
                break;

            // 'all' — без дополнительных условий
        }

        $whereSQL = '';
        if (!empty($where)) {
            $whereSQL = 'WHERE ' . implode(' AND ', $where);
        }

        // --- COUNT ---
        try {
            $countSQL = "SELECT COUNT(*) AS total FROM {$from} {$whereSQL}";
            $stmt = $pdo->prepare($countSQL);
            $stmt->execute($params);
            $total = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (\Exception $e) {
            error_log('FeoRepository::getFilteredRows COUNT error: ' . $e->getMessage());
            return [
                'rows'          => [],
                'total'         => 0,
                'foundZayavki'   => [],
                'missingZayavki' => $zayavkaIds,
            ];
        }

        // --- Поиск существующих zayavka_id (если фильтр по номерам) ---
        $foundZayavki = [];
        if ($hasNums) {
            try {
                $foundSQL = "SELECT f.zayavka_id FROM {$from} {$whereSQL}";
                $stmt = $pdo->prepare($foundSQL);
                $stmt->execute($params);
                $foundZayavki = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'zayavka_id');
            } catch (\Exception $e) {
                error_log('FeoRepository::getFilteredRows FOUND error: ' . $e->getMessage());
            }
        }

        $missingZayavki = $hasNums ? array_values(array_diff($zayavkaIds, $foundZayavki)) : [];

        // --- Данные с пагинацией ---
        try {
            $dataSQL = "SELECT {$select} FROM {$from} {$whereSQL} ORDER BY f.id DESC LIMIT {$limit} OFFSET {$offset}";
            $stmt = $pdo->prepare($dataSQL);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('FeoRepository::getFilteredRows DATA error: ' . $e->getMessage());
            $rows = [];
        }

        return [
            'rows'          => $rows,
            'total'         => $total,
            'foundZayavki'   => $foundZayavki,
            'missingZayavki' => $missingZayavki,
        ];
    }

    /**
     * Парсим строку с номерами заявок в массив чистых ID.
     *
     * Из index22.php — cleanZayavkaNumbers().
     *
     * @return string[]
     */
    public static function parseZayavkaNumbers(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            return [];
        }

        $parts = preg_split('/[,;\s]+/', $input);
        $ids   = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            // Удаляем всё кроме цифр
            $clean = preg_replace('/[^0-9]/', '', $part);
            if ($clean !== '') {
                $ids[] = $clean;
            }
        }

        return array_values(array_unique($ids));
    }
}