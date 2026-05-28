<?php

declare(strict_types=1);

namespace App\Modules\Flights\Repositories;

use App\Core\Database\Connection;
use PDO;

/**
 * Репозиторий для чтения данных рейсов (flights) и связанных сущностей.
 *
 * Повторяет логику legacy timeline.php:
 *  - flights + contractors + drivers + users
 *  - статусы из таблицы status
 *  - заявки из feo по zayavki_ids
 *  - табы: planned, in_transit, unloaded
 *  - распределение по вкладкам ТОЛЬКО по status рейса
 */
class FlightsRepository
{
    private const LIMIT = 500;

    /**
     * Получить список статусов.
     *
     * @return array<string, array> статус → [статус, наименование, style, ...]
     */
    public function getStatuses(): array
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
            error_log('FlightsRepository getStatuses error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить рейсы с фильтром по табу.
     *
     * @param string $tab planned|in_transit|unloaded
     * @return array{flights: array, count: int}
     */
    public function getFlights(string $tab = 'planned'): array
    {
        $pdo = Connection::get();
        if ($pdo === null) {
            return ['flights' => [], 'count' => 0];
        }

        try {
            $baseSql = "
                SELECT f.*,
                    c.name AS contractor_name,
                    d.full_name AS driver_name,
                    d.vehicle_make_plate,
                    CONCAT(u.Фамилия, ' ', u.Имя) AS manager_name
                FROM flights f
                LEFT JOIN contractors c ON f.contractor_id = c.id
                LEFT JOIN drivers d ON f.driver_id = d.id
                LEFT JOIN users u ON f.assigned_manager_id = u.id
                WHERE 1=1
            ";

            $filterSQL = $this->buildFilterSQL($tab);
            $orderSQL  = $this->buildOrderSQL($tab);

            $sql = $baseSql . $filterSQL . $orderSQL . " LIMIT " . self::LIMIT;

            $stmt = $pdo->query($sql);
            $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Count — тот же запрос без LIMIT
            $countSQL = "SELECT COUNT(*) AS cnt FROM flights f WHERE 1=1" . $filterSQL;
            $countStmt = $pdo->query($countSQL);
            $count = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['cnt'];

            return ['flights' => $flights, 'count' => $count];
        } catch (\Exception $e) {
            error_log('FlightsRepository getFlights error: ' . $e->getMessage());
            return ['flights' => [], 'count' => 0];
        }
    }

    /**
     * Получить количество рейсов для каждого таба.
     *
     * @return array{planned: int, in_transit: int, unloaded: int}
     */
    public function getTabCounts(): array
    {
        $pdo = Connection::get();
        if ($pdo === null) {
            return ['planned' => 0, 'in_transit' => 0, 'unloaded' => 0];
        }

        $counts = [];
        $tabs = ['planned', 'in_transit', 'unloaded'];

        try {
            foreach ($tabs as $tab) {
                $filterSQL = $this->buildFilterSQL($tab);
                $sql = "SELECT COUNT(*) AS cnt FROM flights f WHERE 1=1" . $filterSQL;
                $stmt = $pdo->query($sql);
                $counts[$tab] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
            }
        } catch (\Exception $e) {
            error_log('FlightsRepository getTabCounts error: ' . $e->getMessage());
            return ['planned' => 0, 'in_transit' => 0, 'unloaded' => 0];
        }

        return $counts;
    }

    /**
     * Получить данные заявок по их ID.
     *
     * @param string[] $zayavkaIds
     * @return array<string, array> zayavka_id → данные заявки
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
                SELECT zayavka_id, mass_netto, naim_oo_gruzootpravitel,
                       mno_adres_pogruzki, kontakt_tel, tel_dopolnitelnyy, kontakt_email
                FROM feo
                WHERE zayavka_id IN ({$placeholders})
            ");
            $stmt->execute($zayavkaIds);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $map = [];
            foreach ($rows as $row) {
                $map[(string) $row['zayavka_id']] = $row;
            }
            return $map;
        } catch (\Exception $e) {
            error_log('FlightsRepository getZayavkiData error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить названия складов по их ID (одним запросом, без N+1).
     *
     * @param int[] $ids
     * @return array<int, string> warehouse id → name
     */
    public function getWarehouseNames(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $pdo = Connection::get();
        if ($pdo === null) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, name FROM warehouses WHERE id IN ({$placeholders})");
            $stmt->execute(array_values($ids));
            $map = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $map[(int) $row['id']] = $row['name'];
            }
            return $map;
        } catch (\Exception $e) {
            error_log('FlightsRepository getWarehouseNames error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить price_per_kg для заявок (логика из FeoRepository::calculatePrices).
     *
     * Использует status_blocks (marshrut) с cost для расчёта цены за кг.
     *
     * @param string[] $zayavkaIds
     * @return array<string, float> zayavka_id → price_per_kg
     */
    public function getPricePerKg(array $zayavkaIds): array
    {
        if (empty($zayavkaIds)) {
            return [];
        }

        $pdo = Connection::get();
        if ($pdo === null) {
            return [];
        }

        $pricePerKg = [];

        try {
            // Берём все блоки со стоимостью
            $stmt = $pdo->prepare("
                SELECT id, zayavki_ids, cost
                FROM status_blocks
                WHERE status_type = 'marshrut'
                  AND cost IS NOT NULL
                  AND cost > 0
                  AND zayavki_ids != ''
            ");
            $stmt->execute();
            $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($blocks)) {
                return [];
            }

            // Собираем все уникальные zayavka_id из блоков
            $allBlockIds = [];
            foreach ($blocks as $block) {
                $ids = array_filter(array_map('trim', explode(',', $block['zayavki_ids'])));
                foreach ($ids as $id) {
                    $allBlockIds[$id] = true;
                }
            }
            $uniqueBlockIds = array_keys($allBlockIds);

            // Получаем массу нетто
            $weightMap = [];
            if (!empty($uniqueBlockIds)) {
                $placeholders = implode(',', array_fill(0, count($uniqueBlockIds), '?'));
                $stmtFeo = $pdo->prepare("
                    SELECT zayavka_id, mass_netto
                    FROM feo
                    WHERE zayavka_id IN ({$placeholders})
                ");
                $stmtFeo->execute($uniqueBlockIds);
                foreach ($stmtFeo->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $weightKg = (float) $row['mass_netto'] * 1000;
                    if ($weightKg > 0) {
                        $weightMap[$row['zayavka_id']] = $weightKg;
                    }
                }
            }

            // Обрабатываем каждый блок
            foreach ($blocks as $block) {
                $blockCost = (float) $block['cost'];
                $ids = array_filter(array_map('trim', explode(',', $block['zayavki_ids'])));

                $totalWeight = 0.0;
                foreach ($ids as $zId) {
                    if (isset($weightMap[$zId])) {
                        $totalWeight += $weightMap[$zId];
                    }
                }

                if ($totalWeight <= 0) {
                    continue;
                }

                $ppk = $blockCost / $totalWeight;

                foreach ($ids as $zId) {
                    if (in_array($zId, $zayavkaIds, true) && isset($weightMap[$zId])) {
                        if (!isset($pricePerKg[$zId])) {
                            $pricePerKg[$zId] = $ppk;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('FlightsRepository getPricePerKg error: ' . $e->getMessage());
        }

        return $pricePerKg;
    }

    /**
     * Получить список статусов для таба (status-only).
     *
     * Планы на вывоз — планируемые и сформированные рейсы:
     *   search, planned_route, found
     *   (сформированные рейсы со статусом found попадают сюда,
     *    т.к. означают "Исполнитель найден, рейс сформирован")
     *
     * Рейсы в пути — физически начавшиеся:
     *   started
     *
     * Выгруженные рейсы — завершённые:
     *   completed
     *
     * @param string $tab
     * @return string[]
     */
    private function getStatusesForTab(string $tab): array
    {
        return match ($tab) {
            'in_transit' => ['started'],
            'unloaded'   => ['completed'],
            default      => ['search', 'planned_route', 'found'],
        };
    }

    /**
     * Построить SQL-условие фильтра для таба (status-only).
     *
     * Фильтрация только по f.status — даты и прочие поля не участвуют.
     */
    private function buildFilterSQL(string $tab): string
    {
        $statuses = $this->getStatusesForTab($tab);
        if (empty($statuses)) {
            return '';
        }
        $quoted = array_map(fn($s) => "'" . addslashes($s) . "'", $statuses);
        return " AND f.status IN (" . implode(',', $quoted) . ")";
    }

    /**
     * Построить SQL-сортировку для таба.
     *
     * Даты используются только для сортировки, но не для фильтрации вкладок.
     */
    private function buildOrderSQL(string $tab): string
    {
        return match ($tab) {
            'planned' => " ORDER BY
                COALESCE(f.planned_start_date, f.planned_start_date_from, f.planned_start_date_to) ASC,
                f.id ASC",
            'in_transit', 'unloaded' => " ORDER BY
                GREATEST(
                    COALESCE(f.actual_start_date, '1970-01-01'),
                    COALESCE(f.actual_end_date, '1970-01-01')
                ) DESC",
            default => " ORDER BY
                COALESCE(f.planned_start_date, f.planned_start_date_from, f.planned_start_date_to) ASC,
                f.id ASC",
        };
    }
}
