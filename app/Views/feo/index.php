<?php
/**
 * Шаблон страницы "Заявки ФЭО".
 *
 * Реальные колонки из index22.php:
 *   № | ID заявки | Номер заявки | Направление | Статус | Рейс | Номер машины | Дата | Стоимость
 *
 * Переменные:
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
            <form id="filterForm" method="GET" action="">
                <input type="hidden" name="module" value="feo">
                <label style="font-size:0.875rem;">
                    Номера заявок:
                    <input type="text" id="numbersInput" name="numbers" value="<?= htmlspecialchars(implode(', ', $zayavkaIds), ENT_QUOTES, 'UTF-8') ?>" placeholder="Например: 123, 456, 789" autocomplete="off">
                </label>
                <label style="font-size:0.875rem;">
                    Фильтр:
                    <select name="filter" id="filterSelect">
                        <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>Все</option>
                        <option value="available" <?= $filterType === 'available' ? 'selected' : '' ?>>Доступные</option>
                        <option value="routes" <?= $filterType === 'routes' ? 'selected' : '' ?>>Маршруты</option>
                        <option value="flights" <?= $filterType === 'flights' ? 'selected' : '' ?>>Рейсы</option>
                    </select>
                </label>
                <button type="submit">Показать</button>
            </form>
        </div>

        <div id="searchInfo"></div>

        <div id="tableContainer">
            <?php require __DIR__ . '/_table.php'; ?>
        </div>

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

    <script>
    (function() {
        var numbersInput = document.getElementById('numbersInput');
        var filterSelect = document.getElementById('filterSelect');
        var tableContainer = document.getElementById('tableContainer');
        var searchInfo = document.getElementById('searchInfo');
        var debounceTimer = null;
        var currentRequest = null;
        var currentPage = <?= (int)$page ?>;

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/[&<>]/g, function(m) {
                if (m === '&') return '&';
                if (m === '<') return '<';
                if (m === '>') return '>';
                return m;
            });
        }

        function renderTable(data) {
            if (!data || !data.rows) {
                tableContainer.innerHTML = '<table><tr><td colspan="9" style="text-align:center;padding:2rem;color:#6c757d;">Нет данных для отображения.</td></tr></table>';
                return;
            }

            var html = '<table><thead><tr>';
            html += '<th>№</th>';
            html += '<th>ID заявки</th>';
            html += '<th>Номер заявки</th>';
            html += '<th>Направление</th>';
            html += '<th>Статус</th>';
            html += '<th>Рейс</th>';
            html += '<th>Номер машины</th>';
            html += '<th>Дата</th>';
            html += '<th>Стоимость</th>';
            html += '</tr></thead><tbody>';

            if (data.rows.length === 0) {
                var msg = (data.missingZayavki && data.missingZayavki.length > 0)
                    ? 'Заявки с указанными номерами не найдены.'
                    : 'Нет данных для отображения.';
                html += '<tr><td colspan="9" style="text-align:center;padding:2rem;color:#6c757d;">' + msg + '</td></tr>';
            } else {
                for (var i = 0; i < data.rows.length; i++) {
                    var row = data.rows[i];
                    var rowNum = (data.page - 1) * 50 + i + 1;
                    html += '<tr>';
                    html += '<td>' + rowNum + '</td>';
                    html += '<td><strong>' + escapeHtml(row.zayavka_id) + '</strong></td>';
                    html += '<td>' + escapeHtml(row.nomer_zayavki) + '</td>';
                    html += '<td>' + escapeHtml(row.napravlenie) + '</td>';
                    html += '<td>' + escapeHtml(row.status_name) + '</td>';
                    html += '<td>' + escapeHtml(row.flight_name) + '</td>';
                    html += '<td>' + escapeHtml(row.nomer_mashiny) + '</td>';
                    html += '<td>' + escapeHtml(row.data_zayavki) + '</td>';
                    html += '<td>' + escapeHtml(row.zakaz_s_op_stoimost) + '</td>';
                    html += '</tr>';
                }
            }

            html += '</tbody></table>';
            tableContainer.innerHTML = html;
        }

        function renderMissingInfo(missingZayavki) {
            if (missingZayavki && missingZayavki.length > 0) {
                searchInfo.innerHTML = '<div class="missing-info"><strong>Не найдены номера заявок:</strong> ' + escapeHtml(missingZayavki.join(', ')) + '</div>';
            } else {
                searchInfo.innerHTML = '';
            }
        }

        function doSearch(page) {
            page = page || 1;
            currentPage = page;

            var numbers = numbersInput.value.trim();
            var filter = filterSelect.value;

            var params = 'module=feo&action=list&ajax=1';
            if (numbers) params += '&numbers=' + encodeURIComponent(numbers);
            if (filter) params += '&filter=' + encodeURIComponent(filter);
            if (page > 1) params += '&page=' + page;

            if (currentRequest) {
                currentRequest.abort();
            }

            var xhr = new XMLHttpRequest();
            currentRequest = xhr;

            xhr.open('GET', '?' + params, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data.error) {
                            tableContainer.innerHTML = '<div class="missing-info">' + escapeHtml(data.error) + '</div>';
                            searchInfo.innerHTML = '';
                        } else {
                            renderTable(data);
                            renderMissingInfo(data.missingZayavki);
                            // Update pagination links in URL without reload
                            updateUrl(page, numbers, filter);
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        tableContainer.innerHTML = '<div class="missing-info">Ошибка обработки ответа сервера.</div>';
                    }
                } else {
                    tableContainer.innerHTML = '<div class="missing-info">Ошибка сервера (код ' + xhr.status + ').</div>';
                }
                currentRequest = null;
            };

            xhr.onerror = function() {
                tableContainer.innerHTML = '<div class="missing-info">Ошибка соединения с сервером.</div>';
                currentRequest = null;
            };

            xhr.send();
        }

        function updateUrl(page, numbers, filter) {
            var url = '?module=feo';
            if (numbers) url += '&numbers=' + encodeURIComponent(numbers);
            if (filter && filter !== 'all') url += '&filter=' + encodeURIComponent(filter);
            if (page > 1) url += '&page=' + page;
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', url);
            }
        }

        // Debounced input handler (400ms, same as index22.php)
        numbersInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                doSearch(1);
            }, 400);
        });

        // Filter select change triggers search immediately
        filterSelect.addEventListener('change', function() {
            clearTimeout(debounceTimer);
            doSearch(1);
        });

        // Form submit as fallback (regular GET reload, shown as button)
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            // Only use form submit if JS is disabled; AJAX handles the rest
            // Keep as fallback: do nothing special - form submits naturally
        });

        // Initial missing info
        <?php if (!empty($missingZayavki)): ?>
        renderMissingInfo(<?= json_encode($missingZayavki, JSON_UNESCAPED_UNICODE) ?>);
        <?php endif; ?>
    })();
    </script>
</body>
</html>