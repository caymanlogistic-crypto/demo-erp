<?php

declare(strict_types=1);

namespace App\Modules\Flights\Services;

/**
 * Сервис для получения данных из Google Sheets через публичный CSV export.
 *
 * Полностью независим от legacy googleSheetsHelper.php.
 * Без Google API key, без service account, без секретов.
 *
 * Google Sheets mapping (из legacy timeline.php):
 *   row[0]  = zayavka_id
 *   row[8]  = route
 *   row[11] = comments
 *   row[12] = driver_info
 *   row[13] = prep_time
 *
 * Пропускается первая строка (заголовки).
 */
final class GoogleSheetsTimelineService
{
    private const DEFAULT_SPREADSHEET_ID = '1e-iA425O2mhvF12WDnH_lpd6Z3HbCgBOtYtWuF-F6Z4';
    private const DEFAULT_GID = 1200010467;
    private const FETCH_TIMEOUT = 8;

    /**
     * Получить данные из Google Sheets как двумерный массив (CSV rows).
     *
     * @param string|null $spreadsheetId
     * @param int|null    $gid
     * @return array<int, array<int, string>>
     */
    public function getRows(?string $spreadsheetId = null, ?int $gid = null): array
    {
        $spreadsheetId = $spreadsheetId ?? self::DEFAULT_SPREADSHEET_ID;
        $gid = $gid ?? self::DEFAULT_GID;

        $csvUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/export?format=csv&gid={$gid}";

        $context = stream_context_create([
            'http' => [
                'timeout'       => self::FETCH_TIMEOUT,
                'ignore_errors' => true,
                'header'        => "User-Agent: DemoERP/1.0\r\n",
            ],
        ]);

        $csvData = @file_get_contents($csvUrl, false, $context);

        if ($csvData === false || trim($csvData) === '') {
            error_log('GoogleSheetsTimelineService: unable to fetch CSV from Google Sheets');
            return [];
        }

        return $this->parseCsv($csvData);
    }

    /**
     * Получить данные с ассоциативными ключами (первая строка — заголовки).
     *
     * @param string|null $spreadsheetId
     * @param int|null    $gid
     * @return array<int, array<string, string>>
     */
    public function getAssocRows(?string $spreadsheetId = null, ?int $gid = null): array
    {
        $data = $this->getRows($spreadsheetId, $gid);

        if (empty($data)) {
            return [];
        }

        $headers = array_shift($data);
        $result  = [];

        foreach ($data as $row) {
            $assocRow = [];
            foreach ($headers as $index => $header) {
                $assocRow[$header] = $row[$index] ?? '';
            }
            $result[] = $assocRow;
        }

        return $result;
    }

    /**
     * Получить данные, проиндексированные по zayavka_id.
     *
     * Mapping совпадает с legacy timeline.php строка 50–54:
     *   $row[0]  = zayavka_id (ключ)
     *   $row[8]  = route
     *   $row[11] = comments
     *   $row[12] = driver_info
     *   $row[13] = prep_time
     *
     * @return array<string, array{route: string, comments: string, driver_info: string, prep_time: string}>
     */
    public function getIndexedByZayavkaId(): array
    {
        $rows   = $this->getRows();
        $result = [];

        // Пропускаем header (первую строку), если она не числовая
        $startIndex = 0;
        if (!empty($rows) && isset($rows[0][0]) && !is_numeric(trim($rows[0][0]))) {
            $startIndex = 1;
        }

        for ($i = $startIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            $zayavkaId = isset($row[0]) ? trim($row[0]) : '';
            if ($zayavkaId === '' || !is_numeric($zayavkaId)) {
                continue;
            }

            $result[$zayavkaId] = [
                'route'       => isset($row[8])  ? trim($row[8])  : '',
                'comments'    => isset($row[11]) ? trim($row[11]) : '',
                'driver_info' => isset($row[12]) ? trim($row[12]) : '',
                'prep_time'   => isset($row[13]) ? trim($row[13]) : '',
            ];
        }

        return $result;
    }

    /**
     * Простой парсер CSV (без внешних зависимостей).
     *
     * @param string $csvData
     * @return array<int, array<int, string>>
     */
    private function parseCsv(string $csvData): array
    {
        $rows        = [];
        $inQuote     = false;
        $currentRow  = [];
        $currentField = '';
        $length      = strlen($csvData);

        for ($i = 0; $i < $length; $i++) {
            $char     = $csvData[$i];
            $nextChar = ($i < $length - 1) ? $csvData[$i + 1] : '';

            if ($char === '"' && !$inQuote) {
                $inQuote = true;
            } elseif ($char === '"' && $inQuote && $nextChar === '"') {
                $currentField .= '"';
                $i++;
            } elseif ($char === '"' && $inQuote) {
                $inQuote = false;
            } elseif ($char === ',' && !$inQuote) {
                $currentRow[] = $currentField;
                $currentField = '';
            } elseif ($char === "\n" && !$inQuote) {
                $currentRow[] = $currentField;
                if (!empty(array_filter($currentRow))) {
                    $rows[] = $currentRow;
                }
                $currentRow  = [];
                $currentField = '';
            } else {
                $currentField .= $char;
            }
        }

        if (!empty($currentField) || !empty($currentRow)) {
            $currentRow[] = $currentField;
            if (!empty(array_filter($currentRow))) {
                $rows[] = $currentRow;
            }
        }

        // Очищаем поля от лишних пробелов
        foreach ($rows as &$row) {
            foreach ($row as &$field) {
                $field = trim($field);
            }
            unset($field);
        }
        unset($row);

        return $rows;
    }
}
