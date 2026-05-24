<?php
/**
 * Серверный рендеринг таблицы заявок (используется при начальной загрузке страницы).
 *
 * Переменные должны быть доступны из index.php:
 * @var array $rows
 * @var int $page
 * @var array $zayavkaIds
 * @var array $missingZayavki
 */
?>
<?php if (!empty($missingZayavki)): ?>
<div class="missing-info">
    <strong>Не найдены номера заявок:</strong> <?= htmlspecialchars(implode(', ', $missingZayavki), ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>№</th>
            <th>ID заявки</th>
            <th>Номер заявки</th>
            <th>Направление</th>
            <th>Статус</th>
            <th>Рейс</th>
            <th>Номер машины</th>
            <th>Дата</th>
            <th>Стоимость</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
        <tr>
            <td colspan="9" style="text-align: center; padding: 2rem; color: #6c757d;">
                <?= count($zayavkaIds) > 0 ? 'Заявки с указанными номерами не найдены.' : 'Нет данных для отображения.' ?>
            </td>
        </tr>
        <?php else: ?>
        <?php foreach ($rows as $i => $row): ?>
        <tr>
            <td><?= $i + 1 + ($page - 1) * 50 ?></td>
            <td><strong><?= htmlspecialchars($row['zayavka_id'], ENT_QUOTES, 'UTF-8') ?></strong></td>
            <td><?= htmlspecialchars($row['nomer_zayavki'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($row['napravlenie'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($row['status_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($row['flight_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($row['nomer_mashiny'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($row['data_zayavki'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['zakaz_s_op_stoimost'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>