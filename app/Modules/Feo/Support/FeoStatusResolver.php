<?php

declare(strict_types=1);

namespace App\Modules\Feo\Support;

/**
 * Форматирует статусные метки для строк таблицы заявок.
 *
 * Содержит только вспомогательные методы для отображения.
 */
class FeoStatusResolver
{
    /**
     * Формат дат рейса.
     *
     * Взят из index22.php, функция formatFlightDates().
     */
    public static function formatFlightDates(array $flightData): string
    {
        if (!empty($flightData['actual_start_date']) || !empty($flightData['actual_end_date'])) {
            $parts = [];
            if (!empty($flightData['actual_start_date'])) {
                $parts[] = 'с ' . date('d.m.Y', strtotime($flightData['actual_start_date']));
            }
            if (!empty($flightData['actual_end_date'])) {
                $parts[] = 'по ' . date('d.m.Y', strtotime($flightData['actual_end_date']));
            }
            return implode(' ', $parts);
        }
        if (!empty($flightData['planned_start_date'])) {
            return date('d.m.Y', strtotime($flightData['planned_start_date']));
        }
        if (!empty($flightData['planned_start_date_from']) && !empty($flightData['planned_start_date_to'])) {
            return date('d.m.Y', strtotime($flightData['planned_start_date_from']))
                . ' - '
                . date('d.m.Y', strtotime($flightData['planned_start_date_to']));
        }
        if (!empty($flightData['planned_start_date_from'])) {
            return 'с ' . date('d.m.Y', strtotime($flightData['planned_start_date_from']));
        }
        if (!empty($flightData['planned_start_date_to'])) {
            return 'по ' . date('d.m.Y', strtotime($flightData['planned_start_date_to']));
        }

        return '—';
    }

    /**
     * Подготовить статус рейса с классами для отображения.
     *
     * @param string|null $flightStatus Статус из таблицы flights
     * @param array<string, array> $statusMap Маппинг статусов из таблицы status
     * @return array{html: string, class: string}
     */
    public static function buildFlightStatus(?string $flightStatus, array $statusMap): array
    {
        if ($flightStatus === null || $flightStatus === '') {
            return ['html' => '—', 'class' => ''];
        }

        if (isset($statusMap[$flightStatus])) {
            $statusData = $statusMap[$flightStatus];
            $styleClass = ltrim($statusData['style'] ?? '', '.');
            return [
                'html' => htmlspecialchars($statusData['наименование'] ?? $flightStatus, ENT_QUOTES, 'UTF-8'),
                'class' => $styleClass,
            ];
        }

        return [
            'html' => htmlspecialchars($flightStatus, ENT_QUOTES, 'UTF-8'),
            'class' => 'status-default',
        ];
    }

    /**
     * Стили статусов для inline-подстановки (из index22.php).
     */
    public static function statusColors(): array
    {
        return [
            'status-search'        => '#FFA459',
            'status-found'         => '#17a2b8',
            'status-started'       => '#28a745',
            'status-completed'     => '#6c757d',
            'status-attention'     => '#dc3545',
            'status-planned-route' => '#9c27b0',
            'status-default'       => '#6c757d',
        ];
    }
}