<?php

declare(strict_types=1);

namespace App\Modules\Feo\Repositories;

use App\Core\Database\Connection;
use PDO;

/**
 * Репозиторий для чтения данных из таблиц feo, status_blocks, flights, status.
 *
 * Логика полностью повторяет index22.php:
 *  - Связь feo ↔ status_blocks через FIND_IN_SET (zayavka_id в zayavki_ids через запятую)
 *  - Связь feo ↔ flights через FIND_IN_SET
 *  - Статусы рейсов из таблицы status
 *  - Фильтры: по номерам заявок, чекбоксы «Доступно»/«Маршрут»/«Рейс»
 */
class FeoRepository
{
    /**
     * Получить отфильтрованные строки заявок с offset/limit (бесконечная прокрутка).
     *
     * @param string[] $zayavkaIds       ID заявок из поля ввода (только цифры)
     * @param bool     $showOnlyAvailable  Чекбокс «Доступно»
     * @param bool     $showOnlyMarshrut   Чекбокс «Маршрут»
     * @param bool     $showOnlyFlight     Чекбокс «Рейс»
     * @param int      $offset
     * @param int      $limit
     * @return array{rows: array, total: int, foundZayavki: array, missingZayavki: array,
     *               statusMap: array, availableBlockMap: array, marshrutMap: array,
     *               flightMap: array, flightDetailsMap: array, priceMap: array, pricePerKgMap: array}
     */
    public function getFilteredRows(
        array $zayavkaIds,
        bool $showOnlyAvailable,
        bool $showOnlyMarshrut,
        bool $showOnlyFlight,
        int $offset = 0,
        int $limit = 50
    ): array {
        $pdo = Connection::get();

        if ($pdo === null) {
            return $this->emptyResult($zayavkaIds);
        }

        $limit  = min(100, max(10, $limit));
        $hasNums = !empty($zayavkaIds);

        // --- 1. Получаем ID заявок из status_blocks (dostupno) ---
        $availableZayavkiIds = [];
        if ($showOnlyAvailable) {
            $availableZayavkiIds = $this->getZayavkaIdsFromStatusBlocks($pdo, 'dostupno');
        }

        // --- 2. Получаем ID заявок из status_blocks (marshrut) ---
        $marshrutZayavkiIds = [];
        if ($showOnlyMarshrut) {
            $marshrutZayavkiIds = $this->getZayavkaIdsFromStatusBlocks($pdo, 'marshrut');
        }

        // --- 3. Получаем ID заявок из flights ---
        $flightZayavkiIds = [];
        if ($showOnlyFlight) {
            $flightZayavkiIds = $this->getZayavkaIdsFromFlights($pdo);
        }

        // Если чекбокс активен, но заявок нет — возвращаем пустой результат
        if ($showOnlyAvailable && empty($availableZayavkiIds)) {
            return $this->emptyCheckboxResult($zayavkaIds, $hasNums, true, false, false);
        }
        if ($showOnlyMarshrut && empty($marshrutZayavkiIds)) {
            return $this->emptyCheckboxResult($zayavkaIds, $hasNums, false, true, false);
        }
        if ($showOnlyFlight && empty($flightZayavkiIds)) {
            return $this->emptyCheckboxResult($zayavkaIds, $hasNums, false, false, true);
        }

        // --- 4. Строим WHERE для таблицы feo ---
        $where   = [];
        $params  = [];

        // Фильтр по номерам заявок (поле feo.zayavka_id)
        if ($hasNums) {
            $placeholders = implode(',', array_fill(0, count($zayavkaIds), '?'));
            $where[] = "zayavka_id IN ($placeholders)";
            $params = array_merge($params, $zayavkaIds);
        }

        // Фильтр «Доступно» — пересечение с availableZayavkiIds
        if ($showOnlyAvailable) {
            $placeholders = implode(',', array_fill(0, count($availableZayavkiIds), '?'));
            $where[] = "zayavka_id IN ($placeholders)";
            $params = array_merge($params, $availableZayavkiIds);
        }

        // Фильтр «Маршрут»
        if ($showOnlyMarshrut) {
            $placeholders = implode(',', array_fill(0, count($marshrutZayavkiIds), '?'));
            $where[] = "zayavka_id IN ($placeholders)";
            $params = array_merge($params, $marshrutZayavkiIds);
        }

        // Фильтр «Рейс»
        if ($showOnlyFlight) {
            $placeholders = implode(',', array_fill(0, count($flightZayavkiIds), '?'));
            $where[] = "zayavka_id IN ($placeholders)";
            $params = array_merge($params, $flightZayavkiIds);
        }

        $whereSQL = '';
        if (!empty($where)) {
            $whereSQL = 'WHERE ' . implode(' AND ', $where);
        }

        // --- 5. COUNT ---
        try {
            $countSQL = "SELECT COUNT(*) AS total FROM feo {$whereSQL}";
            $stmt = $pdo->prepare($countSQL);
            $stmt->execute($params);
            $total = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (\Exception $e) {
            error_log('FeoRepository COUNT error: ' . $e->getMessage());
            return $this->emptyResult($zayavkaIds);
        }

        // --- 6. Поиск существующих zayavka_id (если фильтр по номерам) ---
        $foundZayavki = [];
        if ($hasNums) {
            try {
                $foundSQL = "SELECT zayavka_id FROM feo {$whereSQL}";
                $stmt = $pdo->prepare($foundSQL);
                $stmt->execute($params);
                $foundZayavki = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'zayavka_id');
            } catch (\Exception $e) {
                error_log('FeoRepository FOUND error: ' . $e->getMessage());
            }
        }

        $missingZayavki = $hasNums ? array_values(array_diff($zayavkaIds, $foundZayavki)) : [];

        // --- 7. Данные (LIMIT/OFFSET) ---
        try {
            $dataSQL = "SELECT * FROM feo {$whereSQL} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
            $stmt = $pdo->prepare($dataSQL);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('FeoRepository DATA error: ' . $e->getMessage());
            $rows = [];
        }

        // --- 8. Строим актуальные маппинги для отображения (все записи, не только отфильтрованные) ---
        $availableBlockMap = $this->buildAvailableBlockMap($pdo);
        $marshrutMap       = $this->buildMarshrutMap($pdo);
        $flightMapData     = $this->buildFlightMaps($pdo);
        $statusMap         = $this->buildStatusMap($pdo);
        $priceData         = $this->loadPriceData();

        return [
            'rows'              => $rows,
            'total'             => $total,
            'foundZayavki'      => $foundZayavki,
            'missingZayavki'    => $missingZayavki,
            'statusMap'         => $statusMap,
            'availableBlockMap' => $availableBlockMap,
            'marshrutMap'       => $marshrutMap,
            'flightMap'         => $flightMapData['flightMap'],
            'flightDetailsMap'  => $flightMapData['flightDetailsMap'],
            'priceMap'          => $priceData['price'],
            'pricePerKgMap'     => $priceData['price_per_kg'],
        ];
    }

    /**
     * Получить список zayavka_id из status_blocks по status_type.
     *
     * @return string[]
     */
    private function getZayavkaIdsFromStatusBlocks(PDO $pdo, string $statusType): array
    {
        try {
            $stmt = $pdo->query("SELECT zayavki_ids FROM status_blocks WHERE status_type = '{$statusType}'");
            $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $ids = [];
            foreach ($blocks as $block) {
                $parts = array_filter(explode(',', $block['zayavki_ids']));
                foreach ($parts as $id) {
                    $ids[] = trim($id);
                }
            }
            return array_values(array_unique($ids));
        } catch (\Exception $e) {
            error_log("FeoRepository getZayavkaIdsFromStatusBlocks({$statusType}) error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить список zayavka_id из flights.
     *
     * @return string[]
     */
    private function getZayavkaIdsFromFlights(PDO $pdo): array
    {
        try {
            $stmt = $pdo->query("SELECT zayavki_ids FROM flights");
            $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $ids = [];
            foreach ($flights as $flight) {
                $parts = array_filter(explode(',', $flight['zayavki_ids']));
                foreach ($parts as $id) {
                    $ids[] = trim($id);
                }
            }
            return array_values(array_unique($ids));
        } catch (\Exception $e) {
            error_log('FeoRepository getZayavkaIdsFromFlights error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Маппинг zayavka_id → block_id для «Доступно».
     *
     * @return array<string, string>
     */
    private function buildAvailableBlockMap(PDO $pdo): array
    {
        $map = [];
        try {
            $stmt = $pdo->query("SELECT id, zayavki_ids FROM status_blocks WHERE status_type = 'dostupno'");
            $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($blocks as $block) {
                $parts = array_filter(explode(',', $block['zayavki_ids']));
                foreach ($parts as $zayavkaId) {
                    $zayavkaId = trim($zayavkaId);
                    if (!isset($map[$zayavkaId])) {
                        $map[$zayavkaId] = $block['id'];
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('FeoRepository buildAvailableBlockMap error: ' . $e->getMessage());
        }
        return $map;
    }

    /**
     * Маппинг zayavka_id → block_id для «Маршрут».
     *
     * @return array<string, string>
     */
    private function buildMarshrutMap(PDO $pdo): array
    {
        $map = [];
        try {
            $stmt = $pdo->query("SELECT id, zayavki_ids FROM status_blocks WHERE status_type = 'marshrut'");
            $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($blocks as $block) {
                $parts = array_filter(explode(',', $block['zayavki_ids']));
                foreach ($parts as $zayavkaId) {
                    $zayavkaId = trim($zayavkaId);
                    if (!isset($map[$zayavkaId])) {
                        $map[$zayavkaId] = $block['id'];
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('FeoRepository buildMarshrutMap error: ' . $e->getMessage());
        }
        return $map;
    }

    /**
     * Маппинги для рейсов:
     *  - flightMap: zayavka_id → flight_id
     *  - flightDetailsMap: flight_id → [comment, status, planned_start_date, ...]
     *
     * @return array{flightMap: array<string, string>, flightDetailsMap: array<string, array>}
     */
    private function buildFlightMaps(PDO $pdo): array
    {
        $flightMap = [];
        $flightDetailsMap = [];
        try {
            $stmt = $pdo->query("SELECT id, zayavki_ids, comment, status,
                planned_start_date, planned_start_date_from, planned_start_date_to,
                actual_start_date, actual_end_date
                FROM flights");
            $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($flights as $flight) {
                $parts = array_filter(explode(',', $flight['zayavki_ids']));
                foreach ($parts as $zayavkaId) {
                    $zayavkaId = trim($zayavkaId);
                    if (!isset($flightMap[$zayavkaId])) {
                        $flightMap[$zayavkaId] = $flight['id'];
                    }
                }
                $flightDetailsMap[$flight['id']] = [
                    'comment'                => $flight['comment'],
                    'status'                 => $flight['status'],
                    'planned_start_date'     => $flight['planned_start_date'],
                    'planned_start_date_from' => $flight['planned_start_date_from'],
                    'planned_start_date_to'  => $flight['planned_start_date_to'],
                    'actual_start_date'      => $flight['actual_start_date'],
                    'actual_end_date'        => $flight['actual_end_date'],
                ];
            }
        } catch (\Exception $e) {
            error_log('FeoRepository buildFlightMaps error: ' . $e->getMessage());
        }
        return ['flightMap' => $flightMap, 'flightDetailsMap' => $flightDetailsMap];
    }

    /**
     * Маппинг статусов из таблицы status: статус → [наименование, style, ...]
     *
     * @return array<string, array>
     */
    private function buildStatusMap(PDO $pdo): array
    {
        $map = [];
        try {
            $stmt = $pdo->query("SELECT * FROM status ORDER BY id");
            $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($statuses as $s) {
                $map[$s['статус']] = $s;
            }
        } catch (\Exception $e) {
            error_log('FeoRepository buildStatusMap error: ' . $e->getMessage());
        }
        return $map;
    }

    /**
     * Загрузка данных о ценах (include/price.php из старого проекта).
     * Если файл недоступен — возвращаем пустые массивы.
     *
     * @return array{price: array<string, float>, price_per_kg: array<string, float>}
     */
    private function loadPriceData(): array
    {
        $price = [];
        $price_per_kg = [];

        // Пытаемся загрузить price.php из config/ (если скопирован)
        $priceFile = dirname(__DIR__, 3) . '/config/price.php';
        if (file_exists($priceFile)) {
            try {
                $result = require $priceFile;
                if (is_array($result)) {
                    $price = $result['price'] ?? $result;
                }
            } catch (\Throwable $e) {
                error_log('FeoRepository loadPriceData error: ' . $e->getMessage());
            }
        }

        return ['price' => $price, 'price_per_kg' => $price_per_kg];
    }

    /**
     * Парсим строку с номерами заявок в массив чистых ID (только цифры).
     *
     * Из index22.php — preg_split + preg_replace('/[^0-9]/').
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
            $clean = preg_replace('/[^0-9]/', '', $part);
            if ($clean !== '') {
                $ids[] = $clean;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Пустой результат (БД недоступна).
     *
     * @param string[] $zayavkaIds
     */
    private function emptyResult(array $zayavkaIds): array
    {
        return [
            'rows'              => [],
            'total'             => 0,
            'foundZayavki'      => [],
            'missingZayavki'    => $zayavkaIds,
            'statusMap'         => [],
            'availableBlockMap' => [],
            'marshrutMap'       => [],
            'flightMap'         => [],
            'flightDetailsMap'  => [],
            'priceMap'          => [],
            'pricePerKgMap'     => [],
        ];
    }

    /**
     * Пустой результат при активном чекбоксе без заявок.
     */
    private function emptyCheckboxResult(
        array $zayavkaIds,
        bool $hasFilter,
        bool $available,
        bool $marshrut,
        bool $flight
    ): array {
        return [
            'rows'              => [],
            'total'             => 0,
            'foundZayavki'      => [],
            'missingZayavki'    => $hasFilter ? $zayavkaIds : [],
            'statusMap'         => [],
            'availableBlockMap' => [],
            'marshrutMap'       => [],
            'flightMap'         => [],
            'flightDetailsMap'  => [],
            'priceMap'          => [],
            'pricePerKgMap'     => [],
            '_checkboxEmpty'    => true,
            '_showOnlyAvailable' => $available,
            '_showOnlyMarshrut'  => $marshrut,
            '_showOnlyFlight'    => $flight,
        ];
    }
}
