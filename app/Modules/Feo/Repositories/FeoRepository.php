<?php

declare(strict_types=1);

namespace App\Modules\Feo\Repositories;

use App\Core\Database\Connection;
use PDO;

class FeoRepository
{
    /**
     * Получить статусы (из таблицы status) для отображения.
     *
     * @return array<string, array>
     */
    public function getStatusMap(): array
    {
        $pdo = Connection::get();
        if ($pdo === null) {
            return [];
        }

        try {
            $stmt = $pdo->query("SELECT * FROM status ORDER BY id");
            $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $map = [];
            foreach ($statuses as $s) {
                $map[$s['статус']] = $s;
            }
            return $map;
        } catch (\Exception $e) {
            error_log('FeoRepository::getStatusMap error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить ID заявок из status_blocks с типом "dostupno".
     *
     * Результат: ['zayavka_id' => 'block_id', ...]
     */
    public function getAvailableZayavkiMap(): array
    {
        return $this->getZayavkiIdsFromStatusBlocks('dostupno');
    }

    /**
     * Получить ID заявок из status_blocks с типом "marshrut".
     *
     * Результат: ['zayavka_id' => 'block_id', ...]
     */
    public function getMarshrutZayavkiMap(): array
    {
        return $this->getZayavkiIdsFromStatusBlocks('marshrut');
    }

    /**
     * Получить ID заявок из таблицы flights + детали рейсов.
     *
     * @return array{zayavkaMap: array, flightDetails: array}
     */
    public function getFlightZayavkiData(): array
    {
        $pdo = Connection::get();
        $zayavkaMap = [];
        $flightDetails = [];

        if ($pdo === null) {
            return ['zayavkaMap' => $zayavkaMap, 'flightDetails' => $flightDetails];
        }

        try {
            $stmt = $pdo->query("
                SELECT id, zayavki_ids, comment, status,
                    planned_start_date, planned_start_date_from, planned_start_date_to,
                    actual_start_date, actual_end_date
                FROM flights
            ");
            $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($flights as $flight) {
                $zayavkiIds = array_filter(explode(',', $flight['zayavki_ids']));
                foreach ($zayavkiIds as $zayavkaId) {
                    if (!isset($zayavkaMap[$zayavkaId])) {
                        $zayavkaMap[$zayavkaId] = $flight['id'];
                    }
                }
                $flightDetails[$flight['id']] = [
                    'comment'               => $flight['comment'],
                    'status'                => $flight['status'],
                    'planned_start_date'     => $flight['planned_start_date'],
                    'planned_start_date_from' => $flight['planned_start_date_from'],
                    'planned_start_date_to'  => $flight['planned_start_date_to'],
                    'actual_start_date'      => $flight['actual_start_date'],
                    'actual_end_date'        => $flight['actual_end_date'],
                ];
            }
        } catch (\Exception $e) {
            error_log('FeoRepository::getFlightZayavkiData error: ' . $e->getMessage());
        }

        return ['zayavkaMap' => $zayavkaMap, 'flightDetails' => $flightDetails];
    }

    /**
     * Получить полный маппинг status_blocks для отображения.
     *
     * @return array{available: array, marshrut: array}
     */
    public function getAllStatusBlockMaps(): array
    {
        $pdo = Connection::get();
        $available = [];
        $marshrut = [];

        if ($pdo === null) {
            return ['available' => $available, 'marshrut' => $marshrut];
        }

        try {
            $stmt = $pdo->query("
                SELECT id, zayavki_ids, status_type
                FROM status_blocks
                WHERE status_type IN ('dostupno', 'marshrut')
            ");
            $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($blocks as $block) {
                $zayavkiIds = array_filter(explode(',', $block['zayavki_ids']));
                foreach ($zayavkiIds as $zayavkaId) {
                    if ($block['status_type'] === 'dostupno' && !isset($available[$zayavkaId])) {
                        $available[$zayavkaId] = $block['id'];
                    }
                    if ($block['status_type'] === 'marshrut' && !isset($marshrut[$zayavkaId])) {
                        $marshrut[$zayavkaId] = $block['id'];
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('FeoRepository::getAllStatusBlockMaps error: ' . $e->getMessage());
        }

        return ['available' => $available, 'marshrut' => $marshrut];
    }

    /**
     * ПОЛУЧИТЬ ДАННЫЕ ЗАЯВОК С ПАГИНАЦИЕЙ.
     *
     * @param array $zayavkaIds    ID заявок для фильтра "по номерам"
     * @param string $filterType   Тип фильтра: all | available | routes | flights
     * @param int $offset
     * @param int $limit
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
                'rows'           => [],
                'total'          => 0,
                'foundZayavki'    => [],
                'missingZayavki'  => $zayavkaIds,
            ];
        }

        $limit = min(100, max(10, $limit));
        $hasFilter = !empty($zayavkaIds);
        $where = [];
        $params = [];

        // Собираем zayavka_id по фильтру "по номерам"
        if ($hasFilter) {
            $placeholders = implode(',', array_fill(0, count($zayavkaIds), '?'));
            $where[] = "zayavka_id IN ($placeholders)";
            $params = array_merge($params, $zayavkaIds);
        }

        // Собираем zayavka_id по типу фильтра
        $filterIds = [];
        if ($filterType === 'available') {
            $map = $this->getAvailableZayavkiMap();
            $filterIds = array_keys($map);
        } elseif ($filterType === 'routes') {
            $map = $this->getMarshrutZayavkiMap();
            $filterIds = array_keys($map);
        } elseif ($filterType === 'flights') {
            $data = $this->getFlightZayavkiData();
            $filterIds = array_keys($data['zayavkaMap']);
        }

        if ($filterType !== 'all') {
            if (empty($filterIds)) {
                return [
                    'rows'           => [],
                    'total'          => 0,
                    'foundZayavki'    => [],
                    'missingZayavki'  => $hasFilter ? $zayavkaIds : [],
                ];
            }
            $placeholders = implode(',', array_fill(0, count($filterIds), '?'));
            $where[] = "zayavka_id IN ($placeholders)";
            $params = array_merge($params, $filterIds);
        }

        $whereSQL = '';
        if (!empty($where)) {
            $whereSQL = 'WHERE ' . implode(' AND ', $where);
        }

        // Считаем общее количество
        try {
            $countSQL = "SELECT COUNT(*) as total FROM feo $whereSQL";
            $stmt = $pdo->prepare($countSQL);
            $stmt->execute($params);
            $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (\Exception $e) {
            error_log('FeoRepository::getFilteredRows count error: ' . $e->getMessage());
            return [
                'rows'           => [],
                'total'          => 0,
                'foundZayavki'    => [],
                'missingZayavki'  => $zayavkaIds,
            ];
        }

        // Находим найденные zayavka_id
        $foundZayavki = [];
        if ($hasFilter) {
            try {
                $foundSQL = "SELECT zayavka_id FROM feo $whereSQL";
                $stmt = $pdo->prepare($foundSQL);
                $stmt->execute($params);
                $foundRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $foundZayavki = array_column($foundRows, 'zayavka_id');
            } catch (\Exception $e) {
                error_log('FeoRepository::getFilteredRows found error: ' . $e->getMessage());
            }
        }

        // Получаем строки данных
        $missingZayavki = $hasFilter ? array_values(array_diff($zayavkaIds, $foundZayavki)) : [];

        try {
            $dataSQL = "SELECT * FROM feo $whereSQL ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
            $stmt = $pdo->prepare($dataSQL);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('FeoRepository::getFilteredRows data error: ' . $e->getMessage());
            $rows = [];
        }

        return [
            'rows'           => $rows,
            'total'          => $total,
            'foundZayavki'    => $foundZayavki,
            'missingZayavki'  => $missingZayavki,
        ];
    }

    /**
     * Парсим строку с номерами заявок в массив чистых ID.
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
        $ids = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $clean = preg_replace('/[^0-9]/', '', $part);
            if ($clean !== '') {
                $ids[] = $clean;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Получить ID заявок из status_blocks по типу.
     */
    private function getZayavkiIdsFromStatusBlocks(string $statusType): array
    {
        $pdo = Connection::get();
        $map = [];

        if ($pdo === null) {
            return $map;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT id, zayavki_ids
                FROM status_blocks
                WHERE status_type = :type
            ");
            $stmt->execute(['type' => $statusType]);
            $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($blocks as $block) {
                $zayavkiIds = array_filter(explode(',', $block['zayavki_ids']));
                foreach ($zayavkiIds as $zayavkaId) {
                    if (!isset($map[$zayavkaId])) {
                        $map[$zayavkaId] = $block['id'];
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("FeoRepository::getZayavkiIdsFromStatusBlocks({$statusType}) error: " . $e->getMessage());
        }

        return $map;
    }
}