<?php
/**
 * Шаблон страницы "Заявки ФЭО".
 *
 * Переменные (уже определены в FeoController::renderIndex):
 * @var array $data
 * @var array $statusMap
 * @var array $statusBlocks
 * @var array $flightZayavkaData
 */

use App\Modules\Feo\Support\FeoStatusResolver;

$rows           = $data['rows'] ?? [];
$total          = $data['total'] ?? 0;
$page           = $data['page'] ?? 1;
$totalPages     = $data['totalPages'] ?? 0;
$zayavkaIds     = $data['zayavkaIds'] ?? [];
$filterType     = $data['filterType'] ?? 'all';
$foundZayavki   = $data['foundZayavki'] ?? [];
$missingZayavki = $data['missingZayavki'] ?? [];

$statusColors = FeoStatusResolver::statusColors();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявки ФЭО — Demo ERP</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 1rem; background: #f5f5f5; color: #333; }
        h1 { font-size: 1.5rem; margin: 0 0 1rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        .filter-panel { background: #fff; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .filter-panel form { display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; }
        .filter-panel input[type="text"] { padding: 0.4rem 0.6rem; border: 1px solid #ccc; border-radius: 3px; min-width: 250px; }
        .filter-panel select { padding: 0.4rem 0.6rem; border: 1px solid #ccc; border-radius: 3px; }
        .filter-panel button { padding: 0.4rem 1rem; background: #007bff; color: #fff; border: none; border-radius: 3px; cursor: pointer; }
        .filter-panel button:hover { background: #0069d9; }
        table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); font-size: 0.875rem; }
        th, td { padding: 0.5rem 0.75rem; border: 1px solid #dee2e6; text-align: left; }
        th { background: #f8f9fa; font-weight: 600; position: sticky; top: 0; }
        tr:hover { background: #f1f3f5; }
        .status-badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 3px; font-size: 0.75rem; font-weight: 600; color: #fff; }
        .badge-yes { background: #28a745; color: #fff; padding: 0.15rem 0.5rem; border-radius: 3px; font-size: 0.75rem; }
        .badge-no { color: #6c757d; }
        .empty-cell { color: #adb5bd; }
        .missing-info { background: #fff3cd; border: 1px solid #ffc107; padding: 0.5rem 1rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.875rem; }
        .pagination { margin-top: 1rem; display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .pagination a { text-decoration: none; padding: 0.3rem 0.7rem; border: 1px solid #dee2e6; border-radius: 3px; color: #007bff; background: #fff; font-size: 0.875rem; }
        .pagination a:hover { background: #e9ecef; }
        .pagination strong { padding: 0.3rem 0.7rem; background: #007bff; color: #fff; border-radius: 3px; font-size: 0.875rem; }
        .pagination span { font-size: 0.875rem; color: #6c757d; }
        <?php foreach ($statusColors as $class => $color): ?>
        .<?= $class ?> { background: <?= $color ?> !important; }
        <?php endforeach; ?>
    </style>
</head>
<body>
    <div class="container">
        <h1>Заявки ФЭО</h1>
        <p><a href="/">← На главную</a></p>

        <div class="filter-panel">
            <form method="GET" action="">
                <input type="hidden" name="module" value="feo">
                <label style="font-size:0.875rem;">
                    Номера заявок:
                    <input type="text" name="numbers" value="<?= htmlspecialchars(implode(', ', $zayavkaIds), ENT_QUOTES, 'UTF-8') ?>" placeholder="Например: 123, 456, 789">
                </label>
                <label style="font-size:0.875rem;">
                    Фильтр:
                    <select name="filter">
                        <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>Все</option>
                        <option value="available" <?= $filterType === 'available' ? 'selected' : '' ?>>Доступные</option>
                        <option value="routes" <?= $filterType === 'routes' ? 'selected' : '' ?>>Маршруты</option>
                        <option value="flights" <?= $filterType === 'flights' ? 'selected' : '' ?>>Рейсы</option>
                    </select>
                </label>
                <button type="submit">Показать</button>
            </form>
        </div>

        <?php if (!empty($missingZayavki)): ?>
        <div class="missing-info">
            <strong>Не найдены номера заявок:</strong> <?= htmlspecialchars(implode(', ', $missingZayavki), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID заявки</th>
                    <th>Перевозчик</th>
                    <th>Пункт погрузки</th>
                    <th>Пункт выгрузки</th>
                    <th>Масса нетто, т</th>
                    <th>Дата поручения</th>
                    <th>Доступные</th>
                    <th>Маршруты</th>
                    <th>Рейс</th>
                    <th>Статус рейса</th>
                    <th>Даты рейса</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="12" style="text-align: center; padding: 2rem; color: #6c757d;">
                        <?= count($zayavkaIds) > 0 ? 'Заявки с указанными номерами не найдены.' : 'Нет данных для отображения.' ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $i => $row): ?>
                    <?php
                    $zId = $row['zayavka_id'];
                    $flightId = $flightZayavkaData['zayavkaMap'][$zId] ?? null;
                    $flightDetail = ($flightId !== null && isset($flightZayavkaData['flightDetails'][$flightId]))
                        ? $flightZayavkaData['flightDetails'][$flightId]
                        : null;

                    $flightStatusInfo = FeoStatusResolver::buildFlightStatus(
                        $flightDetail['status'] ?? null,
                        $statusMap
                    );
                    ?>
                <tr>
                    <td><?= $i + 1 + ($page - 1) * 50 ?></td>
                    <td><strong><?= htmlspecialchars($zId, ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($row['kompaniya_perevozchik'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['punkt_pogruzki'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['punkt_vygruzki'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($row['mass_netto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['data_sozdaniya_porucheniya'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if (isset($statusBlocks['available'][$zId])): ?>
                            <span class="badge-yes">✓</span>
                        <?php else: ?>
                            <span class="badge-no">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($statusBlocks['marshrut'][$zId])): ?>
                            <span class="badge-yes">✓</span>
                        <?php else: ?>
                            <span class="badge-no">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($flightId !== null): ?>
                            <span title="<?= htmlspecialchars($flightDetail['comment'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string)$flightId, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php else: ?>
                            <span class="empty-cell">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($flightStatusInfo['class'] !== ''): ?>
                            <span class="status-badge <?= $flightStatusInfo['class'] ?>"><?= $flightStatusInfo['html'] ?></span>
                        <?php else: ?>
                            <span class="empty-cell">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($flightDetail !== null ? FeoStatusResolver::formatFlightDates($flightDetail) : '—', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <span>Всего: <strong><?= $total ?></strong></span>
            <?php if ($totalPages > 1): ?>
                <span>Страница <?= $page ?> из <?= $totalPages ?></span>
                <?php
                $queryParams = [
                    'module' => 'feo',
                    'numbers' => implode(',', $zayavkaIds),
                    'filter' => $filterType,
                ];
                ?>
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $page - 1])) ?>">← Назад</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $page + 1])) ?>">Вперёд →</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>