<?php

declare(strict_types=1);

namespace App\Modules\Feo\Services;

use App\Modules\Feo\Repositories\FeoRepository;

/**
 * Сервис фильтрации заявок.
 *
 * Определяет:
 *  - какие заявки имеются в таблице feo;
 *  - какие отсутствуют;
 *  - готовит данные для вывода с учётом пагинации.
 */
class FeoFilterService
{
    private FeoRepository $repository;

    public function __construct(FeoRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Выполнить фильтрацию и вернуть готовый набор данных.
     *
     * @param string $numbers     Строка с номерами заявок (через запятую/пробел/точку с запятой)
     * @param string $filterType  all | available | routes | flights
     * @param int    $page        Номер страницы (начиная с 1)
     * @param int    $limit       Элементов на странице
     * @return array{rows: array, total: int, foundZayavki: array, missingZayavki: array, page: int, limit: int, zayavkaIds: array, filterType: string, totalPages: int}
     */
    public function execute(
        string $numbers,
        string $filterType = 'all',
        int $page = 1,
        int $limit = 50
    ): array {
        $zayavkaIds = FeoRepository::parseZayavkaNumbers($numbers);
        $page = max(1, $page);
        $limit = min(100, max(10, $limit));
        $offset = ($page - 1) * $limit;

        $result = $this->repository->getFilteredRows($zayavkaIds, $filterType, $offset, $limit);

        $totalPages = (int)ceil($result['total'] / $limit);

        return [
            'rows'          => $result['rows'],
            'total'         => $result['total'],
            'foundZayavki'   => $result['foundZayavki'],
            'missingZayavki' => $result['missingZayavki'],
            'page'          => $page,
            'limit'         => $limit,
            'zayavkaIds'    => $zayavkaIds,
            'filterType'    => $filterType,
            'totalPages'    => $totalPages,
        ];
    }
}