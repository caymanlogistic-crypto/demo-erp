<?php
/**
 * Контент страницы «Загрузка данных FEO» (TransportERP shell).
 * Вставляется в app/Views/layouts/main.php.
 *
 * СОХРАНЕНЫ ВСЕ JS-хуки: filterZayavki, searchText, showOnlyAvailable,
 * showOnlyMarshrut, showOnlyFlight, clearFilterBtn, clearSearchBtn,
 * filterStats, tableWrapper, dataTable, tableBody, loadingIndicator, noMoreData.
 */
?>
<!-- Page header -->
<div class="page-head">
    <div class="page-head-left">
        <div class="page-title">Загрузка данных FEO</div>
        <div class="page-summary">
            <span>Рабочая таблица заявок</span>
            <span class="sep">·</span>
            <span>автозагрузка</span>
        </div>
    </div>
</div>

<!-- Filter bar -->
<div class="filters-bar">
    <div class="filter-field" style="width: 280px;">
        <label for="filterZayavki">Фильтр по заявкам</label>
        <input type="text" id="filterZayavki" placeholder="Введите номера заявок через запятую, пробел" value="">
    </div>
    <div class="filter-field" style="width: 300px;">
        <label for="searchText">Поиск по содержимому</label>
        <input type="text" id="searchText" placeholder="Регион, МО, Отправитель, Адрес, Доступно, Маршрут, Рейс, Статус..." value="">
    </div>
    <div class="form-toggle">
        <label class="toggle-box"><input type="checkbox" id="showOnlyAvailable"> Доступно</label>
        <label class="toggle-box"><input type="checkbox" id="showOnlyMarshrut"> Маршрут</label>
        <label class="toggle-box"><input type="checkbox" id="showOnlyFlight"> Рейс</label>
    </div>
    <div class="filter-actions">
        <button id="clearFilterBtn" class="btn btn-toolbar">Очистить</button>
        <button id="clearSearchBtn" class="btn btn-toolbar">✕</button>
    </div>
</div>
<div id="filterInfo" style="padding: 2px 14px; font-size: 11px; color: var(--text-muted);"><span id="filterStats"></span></div>

<!-- Table card -->
<div class="table-card">
    <div class="table-toolbar">
        <div class="found-label">Заявки ФЭО</div>
        <div class="toolbar-right">
            <span id="loadingIndicator" style="display: none; font-size: 11px; color: var(--text-faint);">Загрузка...</span>
        </div>
    </div>
    <div class="table-scroll" id="tableWrapper">
        <table class="table" id="dataTable">
            <colgroup>
                <col class="col-feo-id">
                <col class="col-feo-mass">
                <col class="col-feo-region">
                <col class="col-feo-mo">
                <col class="col-feo-sender">
                <col class="col-feo-address">
                <col class="col-feo-available">
                <col class="col-feo-route">
                <col class="col-feo-flight">
                <col class="col-feo-status">
                <col class="col-feo-dates">
                <col class="col-feo-price">
                <col class="col-feo-pricekg">
            </colgroup>
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
        <div id="noMoreData" class="no-more-data" style="display: none; text-align: center; padding: 1rem; color: var(--text-faint); font-size: 11px;">Все заявки загружены</div>
    </div>
</div>

<script>
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

function loadData(reset) {
    if (reset === undefined) reset = true;
    if (isLoading) return;
    if (reset) { currentOffset = 0; hasMore = true; tableBody.innerHTML = ''; noMoreData.style.display = 'none'; }
    if (!hasMore) return;
    isLoading = true;
    loadingIndicator.style.display = 'inline';

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
                // Update toolbar found label
                var fl = document.querySelector('.found-label');
                if (fl) fl.innerHTML = '<b>' + data.total + '</b> заявок';

                parseRowsForSearch();
                applyTextFilter();
            } else {
                tableBody.innerHTML = '<tr><td colspan="13" style="text-align: center; padding: 40px; color: var(--danger);">Ошибка: ' + escapeHtml(data.message) + '</td></tr>';
            }
        })
        .catch(function(error) {
            console.error('Ошибка загрузки:', error);
            tableBody.innerHTML = '<tr><td colspan="13" style="text-align: center; padding: 40px; color: var(--danger);">Ошибка загрузки данных</td></tr>';
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
            var message = 'Найдено заявок' + availableText + ': ' + data.total + ' из ' + data.filter_count + ' введенных';
            if (data.missing_zayavki && data.missing_zayavki.length > 0) {
                message += ' <span style="color: var(--danger);">: ' + escapeHtml(data.missing_zayavki.join(', ')) + '</span>';
            }
            filterStats.innerHTML = message;
        } else {
            var message2 = 'По введенным номерам заявок' + availableText + ' (' + data.filter_count + ') ничего не найдено';
            if (data.missing_zayavki && data.missing_zayavki.length > 0) {
                message2 += ' <span style="color: var(--danger);">: ' + escapeHtml(data.missing_zayavki.join(', ')) + '</span>';
            }
            filterStats.innerHTML = message2;
        }
    } else if (data.has_filter && data.filter_count === 0) {
        filterStats.innerHTML = 'Не найдено корректных номеров заявок';
    } else {
        filterStats.innerHTML = 'Всего заявок в базе' + availableText + ': <b>' + data.total + '</b>';
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
        var html = filterStats.innerHTML.replace(/ Найдено по тексту: \d+/, '');
        html += ' Найдено по тексту: ' + visibleCount;
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
