<?php

declare(strict_types=1);

namespace App\Modules\Feo\Services;

use App\Modules\Feo\Repositories\FeoRepository;

/**
 * Сервис фильтрации заявок (оркестратор).
 *
 * Повторяет логику index22.php:
 *  - offset/limit вместо page-based пагинации
 *  - чекбоксы: showOnlyAvailable, showOnlyMarshrut, showOnlyFlight
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
     * @param string $numbers            Строка номеров заявок (через запятую/пробел/;)
     * @param bool   $showOnlyAvailable  Чекбокс «Доступно»
     * @param bool   $showOnlyMarshrut   Чекбокс «Маршрут»
     * @param bool   $showOnlyFlight     Чекбокс «Рейс»
     * @param int    $offset             Смещение для бесконечной прокрутки
     * @param int    $limit              Элементов на странице
     * @return array
     */
    public function execute(
        string $numbers,
        bool $showOnlyAvailable = false,
        bool $showOnlyMarshrut = false,
        bool $showOnlyFlight = false,
        int $offset = 0,
        int $limit = 50
    ): array {
        $zayavkaIds = FeoRepository::parseZayavkaNumbers($numbers);
        $limit      = min(100, max(10, $limit));
        $offset     = max(0, $offset);

        $result = $this->repository->getFilteredRows(
            $zayavkaIds,
            $showOnlyAvailable,
            $showOnlyMarshrut,
            $showOnlyFlight,
            $offset,
            $limit
        );

        $hasMore = ($offset + $limit) < $result['total'];

        return [
            'rows'              => $result['rows'],
            'total'             => $result['total'],
            'foundZayavki'      => $result['foundZayavki'],
            'missingZayavki'    => $result['missingZayavki'],
            'statusMap'         => $result['statusMap'],
            'availableBlockMap' => $result['availableBlockMap'],
            'marshrutMap'       => $result['marshrutMap'],
            'flightMap'         => $result['flightMap'],
            'flightDetailsMap'  => $result['flightDetailsMap'],
            'priceMap'          => $result['priceMap'],
            'pricePerKgMap'     => $result['pricePerKgMap'],
            'statusCounts'      => $result['statusCounts'],
            'offset'            => $offset,
            'limit'             => $limit,
            'hasMore'           => $hasMore,
            'zayavkaIds'        => $zayavkaIds,
            'showOnlyAvailable' => $showOnlyAvailable,
            'showOnlyMarshrut'  => $showOnlyMarshrut,
            'showOnlyFlight'    => $showOnlyFlight,
            '_checkboxEmpty'    => $result['_checkboxEmpty'] ?? false,
        ];
    }
}
