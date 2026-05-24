<?php
/**
 * Шаблон страницы «Загрузка данных FEO».
 *
 * Полностью повторяет структуру и поведение index22.php:
 *  - Заголовок «Загрузка данных FEO»
 *  - Кнопка «Выбрать файл» (визуально, без реальной загрузки)
 *  - Фильтр по номерам заявок
 *  - Поиск по содержимому
 *  - Чекбоксы: Доступно, Маршрут, Рейс
 *  - Счётчик «Всего заявок в базе: N»
 *  - Таблица 14 колонок (как в index22.php)
 *  - Бесконечная прокрутка
 *
 * Переменные (доступны из FeoController::index):
 *   (нет — страница отдаётся пустой, данные загружаются AJAX'ом)
 */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Загрузка данных FEO — Demo ERP</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            color: #333;
        }
        .app {
            max-width: 100%;
            margin: 0;
            padding: 1rem;
        }
        .toolbar {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            background: #fff;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .toolbar h1 {
            margin: 0;
            font-size: 1.25rem;
            flex: 1;
        }
        .toolbar a {
            color: #007bff;
            text-decoration: none;
            font-size: 0.875rem;
        }
        .toolbar a:hover {
            text-decoration: underline;
        }
        .btn {
            padding: 0.4rem 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
            cursor: pointer;
            font-size: 0.875rem;
            white-space: nowrap;
        }
        .btn:hover {
            background: #e9ecef;
        }
        .btn-primary {
            background: #007bff;
            color: #fff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background: #0069d9;
        }
        .btn-sm {
            padding: 0.15rem 0.4rem;
            font-size: 0.75rem;
        }
        .btn-delete {
            color: #dc3545;
            border-color: #dc3545;
        }
        .btn-delete:hover {
            background: #dc3545;
            color: #fff;
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Фильтры */
        .filter-container {
            background: #fff;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .filter-row {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .filter-input-group {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-label {
            font-size: 14px;
            white-space: nowrap;
            font-weight: 500;
        }
        .filter-input {
            padding: 0.4rem 0.6rem;
            border: 1px solid #ccc;
            border-radius: 3px;
            flex: 1;
            min-width: 200px;
        }
        .filter-checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        .filter-checkbox-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .filter-checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .filter-stats {
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }
        .filter-stats-error {
            color: #dc3545;
            background: #f8d7da;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }

        /* Таблица */
        .table-wrapper {
            overflow: auto;
            max-height: calc(100vh - 320px);
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }
        .data-table thead {
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            padding: 0.5rem 0.75rem;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }
        .data-table td {
            padding: 0.4rem 0.5rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        .data-table tbody tr:hover {
            background: #f1f3f5;
        }

        /* Статусы */
        .status-available {
            color: #28a745;
            font-weight: bold;
            background: #d4edda;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            display: inline-block;
            min-width: 50px;
        }
        .status-marshrut {
            color: #0066cc;
            font-weight: bold;
            background: #e7f3ff;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            display: inline-block;
            min-width: 50px;
        }
        .status-flight {
            color: #856404;
            font-weight: bold;
            background: #fff3cd;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            display: inline-block;
            min-width: 50px;
        }
        .status-badge {
            display: inline-block;
            width: 120px;
            text-align: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            color: #ffffff;
        }
        .status-search        { background: #FFA459; }
        .status-found         { background: #17a2b8; }
        .status-started       { background: #28a745; }
        .status-completed     { background: #6c757d; }
        .status-attention     { background: #dc3545; }
        .status-planned-route { background: #9c27b0; }

        .loading-indicator {
            text-align: center;
            padding: 1rem;
            color: #6c757d;
        }
        .no-more-data {
            text-align: center;
            padding: 1rem;
            color: #adb5bd;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="toolbar">
            <h1>Загрузка данных FEO</h1>
            <button class="btn btn-primary" onclick="document.getElementById('fileInput').click()">Выбрать файл</button>
            <input type="file" id="fileInput" accept=".xlsx" style="display: none;" disabled>
            <a href="/demoERP/public/">← На главную</a>
        </div>

        <div class="filter-container">
            <div class="filter-row">
                <div class="filter-input-group">
                    <label for="filterZayavki" class="filter-label">Фильтр по заявкам:</label>
                    <input type="text" id="filterZayavki" placeholder="Введите номера заявок через запятую, пробел" class="filter-input" value="">
                    <button id="clearFilterBtn" class="btn">Очистить</button>
                </div>
                <div class="filter-input-group">
                    <label for="searchText" class="filter-label">Поиск по содержимому:</label>
                    <input type="text" id="searchText" placeholder="Регион, МО, Отправитель, Адрес, Доступно, Маршрут, Рейс, Статус..." class="filter-input" value="">
                    <button id="clearSearchBtn" class="btn">Очистить</button>
                </div>
            </div>
            <div class="filter-checkbox-group">
                <label for="showOnlyAvailable"><input type="checkbox" id="showOnlyAvailable"> Доступно</label>
                <label for="showOnlyMarshrut"><input type="checkbox" id="showOnlyMarshrut"> Маршрут</label>
                <label for="showOnlyFlight"><input type="checkbox" id="showOnlyFlight"> Рейс</label>
            </div>
            <div id="filterInfo" class="filter-stats"><span id="filterStats"></span></div>
        </div>

        <div class="table-wrapper" id="tableWrapper">
            <table class="data-table" id="dataTable">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID ЗАЯВКИ</th>
                        <th style="width: 90px; text-align: right;">МАССА, КГ</th>
                        <th style="width: 150px;">МНО, РЕГИОН</th>
                        <th style="width: 150px;">МНО, МО</th>
                        <th style="width: 200px;">ГРУЗООТПРАВИТЕЛЬ</th>
                        <th style="text-align: left;">АДРЕС ПОГРУЗКИ</th>
                        <th style="width: 100px; text-align: center;">ДОСТУПНО</th>
                        <th style="width: 100px; text-align: center;">МАРШРУТ</th>
                        <th style="width: 150px; text-align: center;">РЕЙС</th>
                        <th style="width: 140px; text-align: center;">СТАТУС</th>
                        <th style="width: 180px; text-align: center;">ДАТЫ</th>
                        <th style="width: 100px; text-align: right;">СТОИМОСТЬ</th>
                        <th style="width: 80px; text-align: right;">₽/КГ</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
            <div id="loadingIndicator" class="loading-indicator" style="display: none;">Загрузка...</div>
            <div id="noMoreData" class="no-more-data" style="display: none;">Все заявки загружены</div>
        </div>
    </div>

    <script>
    var fileInput = document.getElementById('fileInput');
    var tableBody = document.getElementById('tableBody');
    var tableWrapper = document.getElementById('tableWrapper');
    var filterInput = document.getElementById('filterZayavki');
    var clearFilterBtn = document.getElementById('clearFilterBtn');
    var filterStats = document.getElementById('filterStats');
    var loadingIndicator = document.getElementById('loadingIndicator');
    var noMoreData = document.getElementById('noMoreData');
    var showOnlyAvailableCheckbox = document.getElementById('showOnlyAvailable');
    var showOnlyMarshrutCheckbox = document.getElementById('showOnlyMarshrut');
    var showOnlyFlightCheckbox = document.getElementById('showOnlyFlight');

    // Поиск по содержимому
    var searchInput = document.getElementById('searchText');
    var clearSearchBtn = document.getElementById('clearSearchBtn');
    var currentSearch = '';
    var allRowsData = [];
    var lastAjaxData = null;

    var currentOffset = 0;
    var currentLimit = 50;
    var currentFilter = '';
    var currentTotal = 0;
    var isLoading = false;
    var hasMore = true;
    var loadTimeout = null;
    var scrollTimeout = null;

    // Предупреждение о том, что загрузка файла отключена
    fileInput.addEventListener('click', function(e) {
        e.preventDefault();
        alert('Загрузка файла .xlsx временно отключена в demoERP.\nИспользуйте оригинальный инструмент для импорта данных.');
        return false;
    });

    function loadData(reset) {
        if (reset === undefined) reset = true;
        if (isLoading) return;
        if (reset) { currentOffset = 0; hasMore = true; tableBody.innerHTML = ''; noMoreData.style.display = 'none'; }
        if (!hasMore) return;
        isLoading = true;
        loadingIndicator.style.display = 'block';

        var filterValue = filterInput.value.trim();
        var showOnlyAvailable = showOnlyAvailableCheckbox.checked ? '1' : '0';
        var showOnlyMarshrut = showOnlyMarshrutCheckbox.checked ? '1' : '0';
        var showOnlyFlight = showOnlyFlightCheckbox.checked ? '1' : '0';

        var url = '?module=feo&action=list&ajax=get_zayavki'
            + '&offset=' + currentOffset
            + '&limit=' + currentLimit
            + '&filter_zayavki=' + encodeURIComponent(filterValue)
            + '&show_only_available=' + showOnlyAvailable
            + '&show_only_marshrut=' + showOnlyMarshrut
            + '&show_only_flight=' + showOnlyFlight;

        fetch(url)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    if (reset) {
                        tableBody.innerHTML = data.html;
                    } else {
                        tableBody.insertAdjacentHTML('beforeend', data.html);
                    }
                    currentTotal = data.total;
                    currentFilter = filterValue;
                    hasMore = data.has_more;
                    lastAjaxData = data;

                    var rowsCount = tableBody.querySelectorAll('tr').length;
                    currentOffset = rowsCount;

                    if (!hasMore && currentOffset > 0) {
                        noMoreData.style.display = 'block';
                    } else {
                        noMoreData.style.display = 'none';
                    }

                    updateFilterInfo(data);
                    parseRowsForSearch();
                    applyTextFilter();
                } else {
                    tableBody.innerHTML = '<tr><td colspan="13" style="text-align: center; padding: 40px; color: #dc3545;">Ошибка: ' + escapeHtml(data.message) + '</td></tr>';
                }
            })
            .catch(function(error) {
                console.error('Ошибка загрузки:', error);
                tableBody.innerHTML = '<tr><td colspan="13" style="text-align: center; padding: 40px; color: #dc3545;">Ошибка загрузки данных</td></tr>';
            })
            .finally(function() {
                isLoading = false;
                loadingIndicator.style.display = 'none';
            });
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>]/g, function(m) {
            if (m === '&') return '&';
            if (m === '<') return '<';
            if (m === '>') return '>';
            return m;
        });
    }

    function updateFilterInfo(data) {
        var filterText = [];
        if (data.show_only_available) filterText.push('доступные');
        if (data.show_only_marshrut) filterText.push('маршруты');
        if (data.show_only_flight) filterText.push('рейсы');
        var availableText = filterText.length > 0 ? ' (только ' + filterText.join(', ') + ')' : '';

        if (data.has_filter && data.filter_count > 0) {
            if (data.total > 0) {
                var message = '🔍 Найдено заявок' + availableText + ': ' + data.total + ' из ' + data.filter_count + ' введенных';
                if (data.missing_zayavki && data.missing_zayavki.length > 0) {
                    message += ' <span class="filter-stats-error">: ' + escapeHtml(data.missing_zayavki.join(', ')) + '</span>';
                }
                filterStats.innerHTML = message;
            } else {
                var message2 = '⚠️ По введенным номерам заявок' + availableText + ' (' + data.filter_count + ') ничего не найдено';
                if (data.missing_zayavki && data.missing_zayavki.length > 0) {
                    message2 += ' <span class="filter-stats-error">: ' + escapeHtml(data.missing_zayavki.join(', ')) + '</span>';
                }
                filterStats.innerHTML = message2;
            }
        } else if (data.has_filter && data.filter_count === 0) {
            filterStats.innerHTML = '⚠️ Не найдено корректных номеров заявок';
        } else {
            filterStats.innerHTML = '📋 Всего заявок в базе' + availableText + ': ' + data.total;
        }
    }

    // Логика поиска по содержимому (из index22.php)
    function parseRowsForSearch() {
        allRowsData = [];
        tableBody.querySelectorAll('tr').forEach(function(row) {
            var cells = row.querySelectorAll('td');
            if (cells.length >= 10) {
                allRowsData.push({
                    element: row,
                    searchText: [
                        cells[2] ? cells[2].textContent : '',
                        cells[3] ? cells[3].textContent : '',
                        cells[4] ? cells[4].textContent : '',
                        cells[5] ? cells[5].textContent : '',
                        cells[6] ? cells[6].textContent : '',
                        cells[7] ? cells[7].textContent : '',
                        cells[8] ? cells[8].textContent : '',
                        cells[9] ? cells[9].textContent : ''
                    ].join(' ').toLowerCase()
                });
            }
        });
    }

    function applyTextFilter() {
        var searchValue = currentSearch.trim().toLowerCase();
        if (!searchValue) {
            allRowsData.forEach(function(item) { item.element.style.display = ''; });
            if (lastAjaxData) updateFilterInfo(lastAjaxData);
            return;
        }
        var visibleCount = 0;
        allRowsData.forEach(function(item) {
            if (item.searchText.indexOf(searchValue) !== -1) {
                item.element.style.display = '';
                visibleCount++;
            } else {
                item.element.style.display = 'none';
            }
        });
        if (lastAjaxData) {
            var html = filterStats.innerHTML.replace(/ 🔍 Найдено по тексту: \d+/, '');
            html += ' 🔍 Найдено по тексту: ' + visibleCount;
            filterStats.innerHTML = html;
        }
    }

    function handleScroll() {
        if (!tableWrapper) return;
        if (scrollTimeout) clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(function() {
            var scrollTop = tableWrapper.scrollTop;
            var scrollHeight = tableWrapper.scrollHeight;
            var clientHeight = tableWrapper.clientHeight;
            if (scrollTop + clientHeight >= scrollHeight - 100) {
                if (!isLoading && hasMore) loadData(false);
            }
        }, 100);
    }

    function handleFilterInput() {
        if (loadTimeout) clearTimeout(loadTimeout);
        loadTimeout = setTimeout(function() {
            loadData(true);
            if (tableWrapper) tableWrapper.scrollTop = 0;
        }, 500);
    }

    function handleCheckboxChange() {
        if (loadTimeout) clearTimeout(loadTimeout);
        loadTimeout = setTimeout(function() {
            loadData(true);
            if (tableWrapper) tableWrapper.scrollTop = 0;
        }, 300);
    }

    function clearFilter() {
        filterInput.value = '';
        showOnlyAvailableCheckbox.checked = false;
        showOnlyMarshrutCheckbox.checked = false;
        showOnlyFlightCheckbox.checked = false;
        loadData(true);
        if (tableWrapper) tableWrapper.scrollTop = 0;
    }

    function handleSearchInput() {
        currentSearch = searchInput.value.trim();
        if (allRowsData.length > 0) {
            applyTextFilter();
        } else {
            // Если данных ещё нет (первый ввод поиска до загрузки) — инициируем загрузку
            loadData(true);
        }
    }

    function clearSearch() {
        searchInput.value = '';
        currentSearch = '';
        if (allRowsData.length > 0) applyTextFilter();
    }

    // Event listeners
    if (filterInput) filterInput.addEventListener('input', handleFilterInput);
    if (clearFilterBtn) clearFilterBtn.addEventListener('click', clearFilter);
    if (showOnlyAvailableCheckbox) showOnlyAvailableCheckbox.addEventListener('change', handleCheckboxChange);
    if (showOnlyMarshrutCheckbox) showOnlyMarshrutCheckbox.addEventListener('change', handleCheckboxChange);
    if (showOnlyFlightCheckbox) showOnlyFlightCheckbox.addEventListener('change', handleCheckboxChange);
    if (searchInput) searchInput.addEventListener('input', handleSearchInput);
    if (clearSearchBtn) clearSearchBtn.addEventListener('click', clearSearch);

    // Бесконечная прокрутка
    if (tableWrapper) {
        tableWrapper.addEventListener('scroll', handleScroll);
        var observer = new MutationObserver(function() {
            setTimeout(function() {
                if (tableWrapper.scrollHeight <= tableWrapper.clientHeight && hasMore && !isLoading) {
                    loadData(false);
                }
            }, 100);
        });
        observer.observe(tableBody, { childList: true, subtree: true });
    }

    // Первая загрузка
    loadData(true);
    </script>
</body>
</html>
