<?php

declare(strict_types=1);

namespace App\Modules\Feo\Services;

use App\Modules\Feo\Repositories\FeoRepository;

/**
 * Сервис фильтрации заявок.
 *
 * Оркестрирует получение данных: парсинг номеров → запрос в репозиторий.
 */
class FeoFilterService
{
    private FeoRepository $repository;

    public function __construct(FeoRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Выполнить фильтрацию.
     *
     * @param string $numbers     Строка номеров заявок (через запятую/пробел/;)
     * @param string $filterType  all | available | routes | flights
     * @param int    $page        Номер страницы (начиная с 1)
     * @param int    $limit       Элементов на странице
     * @return array
     */
    public function execute(
        string $numbers,
        string $filterType = 'all',
        int $page = 1,
        int $limit = 50
    ): array {
        $zayavkaIds = FeoRepository::parseZayavkaNumbers($numbers);
        $page       = max(1, $page);
        $limit      = min(100, max(10, $limit));
        $offset     = ($page - 1) * $limit;

        $result     = $this->repository->getFilteredRows($zayavkaIds, $filterType, $offset, $limit);
        $totalPages = (int) ceil($result['total'] / $limit);

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