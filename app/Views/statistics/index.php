<?php
/**
 * Контент страницы «Статистика вывозов» (TransportERP shell).
 *
 * Переменные:
 *   $period    — week|month|custom
 *   $dateType  — delivery|pickup
 *   $dateFrom  — YYYY-MM-DD
 *   $dateTo    — YYYY-MM-DD
 *   $warning   — string|null
 *   $summary   — [periods_count, requests_total, flights_total, weight_total_kg, ...]
 *   $rows      — array of period rows
 *   $chartData — [enabled, period, date_type, items, ...]
 *   $service   — StatisticsService
 */

use App\Modules\Statistics\Services\StatisticsService;

/** @var string $period */
/** @var string $dateType */
/** @var string $dateFrom */
/** @var string $dateTo */
/** @var string|null $warning */
/** @var array $summary */
/** @var array $rows */
/** @var array $chartData */
/** @var StatisticsService $service */
?>
<!-- Page header -->
<div class="page-head">
    <div class="page-head-left">
        <div class="page-eyebrow">Статистика / Вывозы по периодам</div>
        <div class="page-title">Статистика вывозов</div>
        <div class="page-summary"><span>Количество заявок и масса по выбранной дате события</span></div>
    </div>
</div>

<!-- Filter bar -->
<form class="statistics-filters" data-statistics-filter-form method="get" action="">
    <input type="hidden" name="module" value="statistics">

    <div class="filter-field" style="width: 140px;">
        <label for="periodSelect">Группировка</label>
        <select id="periodSelect" name="period" style="height: 24px; padding: 0 6px; font-size: 11px; font-weight: 600; border: 1px solid var(--line-soft); background: var(--surface-field); border-radius: 2px;">
            <option value="week"   <?= $period === 'week'   ? 'selected' : '' ?>>По неделям</option>
            <option value="month"  <?= $period === 'month'  ? 'selected' : '' ?>>По месяцам</option>
            <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Произвольный период</option>
        </select>
    </div>

    <div class="filter-field" style="width: 130px;">
        <label for="dateTypeSelect">Дата события</label>
        <select id="dateTypeSelect" name="date_type" style="height: 24px; padding: 0 6px; font-size: 11px; font-weight: 600; border: 1px solid var(--line-soft); background: var(--surface-field); border-radius: 2px;">
            <option value="delivery" <?= $dateType === 'delivery' ? 'selected' : '' ?>>Доставка</option>
            <option value="pickup"   <?= $dateType === 'pickup'   ? 'selected' : '' ?>>Вывоз</option>
        </select>
    </div>

    <div class="filter-field statistics-date-range-field" data-custom-period-field<?= $period !== 'custom' ? ' hidden' : '' ?> style="width: 150px;">
        <label for="filterDateFrom">Дата с</label>
        <input type="date" id="filterDateFrom" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>"<?= $period !== 'custom' ? ' disabled' : '' ?>>
    </div>

    <div class="filter-field statistics-date-range-field" data-custom-period-field<?= $period !== 'custom' ? ' hidden' : '' ?> style="width: 150px;">
        <label for="filterDateTo">Дата по</label>
        <input type="date" id="filterDateTo" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>"<?= $period !== 'custom' ? ' disabled' : '' ?>>
    </div>

    <div class="filter-actions" data-custom-period-action<?= $period !== 'custom' ? ' hidden' : '' ?>>
        <button type="submit" class="btn btn-toolbar">Применить</button>
        <a class="btn btn-toolbar" href="?module=statistics" style="text-decoration: none;">Сбросить</a>
    </div>
</form>

<?php if ($warning !== null): ?>
<div class="form-alert alert-warning" style="margin: 0;"><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Table card -->
<div class="table-card statistics-card" style="flex: 1; min-height: 0;">
    <div class="table-toolbar">
        <div class="summary-left">
            <span class="summary-main">
                <span class="summary-main-dot"></span>
                <span class="summary-main-label">Найдено:</span>
                <b><?= (int) ($summary['periods_count'] ?? 0) ?></b>
                <span class="summary-main-unit">периодов</span>
            </span>
        </div>
        <div class="summary-right">
            <span class="summary-chip summary-chip-requests">
                <span class="summary-chip-dot"></span>
                <span>Заявок</span>
                <b><?= number_format((int) ($summary['requests_total'] ?? 0), 0, '.', ' ') ?></b>
            </span>
            <span class="summary-chip summary-chip-flights">
                <span class="summary-chip-dot"></span>
                <span>Рейсов</span>
                <b><?= number_format((int) ($summary['flights_total'] ?? 0), 0, '.', ' ') ?></b>
            </span>
            <span class="summary-chip summary-chip-weight">
                <span class="summary-chip-dot"></span>
                <span>Масса</span>
                <b><?= number_format((int) ($summary['weight_total_kg'] ?? 0), 0, '.', ' ') ?> кг</b>
            </span>
            <span class="summary-chip summary-chip-avg">
                <span class="summary-chip-dot"></span>
                <span>Средняя заявка</span>
                <b><?= number_format((int) ($summary['avg_request_weight_kg'] ?? 0), 0, '.', ' ') ?> кг</b>
            </span>
        </div>
    </div>

    <!-- Chart panel -->
    <section class="statistics-chart-panel" data-statistics-chart>
        <div class="statistics-chart-head">
            <div class="statistics-chart-heading">
                <div class="statistics-chart-title">Динамика по периодам</div>
                <div class="statistics-chart-subtitle">Масса, заявки и рейсы по выбранной дате события</div>
            </div>

            <div class="statistics-chart-switch" role="group" aria-label="Метрика графика">
                <button type="button" class="chart-metric-btn active" data-chart-metric="weight" aria-pressed="true">Масса</button>
                <button type="button" class="chart-metric-btn" data-chart-metric="requests" aria-pressed="false">Заявки</button>
                <button type="button" class="chart-metric-btn" data-chart-metric="flights" aria-pressed="false">Рейсы</button>
            </div>
        </div>

        <div class="statistics-chart-body">
            <svg class="statistics-chart-svg" viewBox="0 0 900 180" role="img" aria-label="График статистики по периодам"<?= $chartData['enabled'] ? '' : ' hidden' ?>></svg>

            <div class="statistics-chart-tooltip" hidden></div>

            <div class="statistics-chart-empty"<?= $chartData['enabled'] ? ' hidden' : '' ?>>
                <?= $period === 'custom' ? 'Для графика выберите группировку по неделям или месяцам.' : 'Нет данных для графика.' ?>
            </div>
        </div>

        <script type="application/json" id="statisticsChartData"><?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
    </section>

    <div class="table-scroll">
        <?php if (!empty($rows)): ?>
        <table class="table statistics-table">
            <colgroup>
                <col class="col-stat-period">
                <col class="col-stat-requests">
                <col class="col-stat-flights">
                <col class="col-stat-weight">
                <col class="col-stat-avg-request">
                <col class="col-stat-avg-flight">
            </colgroup>
            <thead>
                <tr>
                    <th>ПЕРИОД</th>
                    <th style="text-align: right;">ЗАЯВОК</th>
                    <th style="text-align: right;">РЕЙСОВ</th>
                    <th style="text-align: right;">МАССА, КГ</th>
                    <th style="text-align: right;">СР. ЗАЯВКА, КГ</th>
                    <th style="text-align: right;">СР. РЕЙС, КГ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['period_label'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="cell-num"><?= number_format((int) $row['requests_count'], 0, '.', ' ') ?></td>
                    <td class="cell-num"><?= number_format((int) $row['flights_count'], 0, '.', ' ') ?></td>
                    <td class="cell-weight"><?= number_format((int) $row['total_weight_kg'], 0, '.', ' ') ?> кг</td>
                    <td class="cell-weight"><?= number_format((int) $row['avg_request_weight_kg'], 0, '.', ' ') ?> кг</td>
                    <td class="cell-weight"><?= number_format((int) $row['avg_flight_weight_kg'], 0, '.', ' ') ?> кг</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 13px;">
            Нет данных за выбранный период
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var form = document.querySelector('[data-statistics-filter-form]');
    var periodSelect = document.getElementById('periodSelect');
    var dateTypeSelect = document.getElementById('dateTypeSelect');
    var customFields = document.querySelectorAll('[data-custom-period-field]');
    var customActions = document.querySelectorAll('[data-custom-period-action]');

    function isCustom() {
        return periodSelect && periodSelect.value === 'custom';
    }

    function updateCustomVisibility() {
        var visible = isCustom();

        customFields.forEach(function (el) {
            el.hidden = !visible;
            el.classList.toggle('is-hidden', !visible);

            var input = el.querySelector('input, select, textarea');
            if (input) {
                input.disabled = !visible;
            }
        });

        customActions.forEach(function (el) {
            el.hidden = !visible;
            el.classList.toggle('is-hidden', !visible);
        });
    }

    function submitAuto() {
        if (!form || isCustom()) {
            return;
        }

        var dateFrom = form.querySelector('[name="date_from"]');
        var dateTo = form.querySelector('[name="date_to"]');

        if (dateFrom) {
            dateFrom.disabled = true;
        }

        if (dateTo) {
            dateTo.disabled = true;
        }

        form.submit();
    }

    if (periodSelect) {
        periodSelect.addEventListener('change', function () {
            updateCustomVisibility();

            if (!isCustom()) {
                submitAuto();
            }
        });
    }

    if (dateTypeSelect) {
        dateTypeSelect.addEventListener('change', function () {
            if (!isCustom()) {
                submitAuto();
            }
        });
    }

    // Initial visibility is already set by server-side hidden/disabled attributes.
    // But ensure JS state matches on first load:
    updateCustomVisibility();
})();
</script>

<script>
(function () {
    var CHART_SVG_VIEWBOX = { w: 900, h: 180 };
    var PADDING = { left: 42, right: 18, top: 8, bottom: 26 };
    var BAR_RX = 5;
    var BAR_RY = 5;
    var MIN_BAR_HEIGHT = 3;
    var MAX_BAR_WIDTH = 32;
    var MIN_BAR_WIDTH = 8;
    var GRID_LINES = 4;

    var metricConfigs = {
        weight: {
            label: 'Масса',
            key: 'weight',
            unit: 'кг',
            color: '#A1622A',
            hover: '#7A421C',
            soft: 'rgba(161, 98, 42, 0.14)'
        },
        requests: {
            label: 'Заявки',
            key: 'requests',
            unit: '',
            color: '#4E6F86',
            hover: '#35566B',
            soft: 'rgba(78, 111, 134, 0.14)'
        },
        flights: {
            label: 'Рейсы',
            key: 'flights',
            unit: '',
            color: '#6F7458',
            hover: '#54593F',
            soft: 'rgba(111, 116, 88, 0.14)'
        }
    };

    var NS = 'http://www.w3.org/2000/svg';

    var chartPanel = document.querySelector('[data-statistics-chart]');
    var jsonEl = document.getElementById('statisticsChartData');
    var chartData = null;

    if (jsonEl) {
        try {
            chartData = JSON.parse(jsonEl.textContent);
        } catch (e) {
            chartData = null;
        }
    }

    if (!chartPanel || !chartData || !chartData.enabled) {
        return;
    }

    var svg = chartPanel.querySelector('.statistics-chart-svg');
    var tooltip = chartPanel.querySelector('.statistics-chart-tooltip');
    var emptyEl = chartPanel.querySelector('.statistics-chart-empty');
    var switchBtns = chartPanel.querySelectorAll('.chart-metric-btn');

    var currentMetric = 'weight';

    function formatNumber(value) {
        if (value >= 1000000) {
            return (value / 1000000).toFixed(1).replace('.0', '') + ' млн';
        }
        if (value >= 1000) {
            return (value / 1000).toFixed(0) + ' тыс';
        }
        return String(value);
    }

    function formatMetricValue(metric, value) {
        var cfg = metricConfigs[metric] || metricConfigs.weight;
        if (cfg.unit) {
            return value.toLocaleString('ru-RU') + ' ' + cfg.unit;
        }
        return value.toLocaleString('ru-RU');
    }

    function findNiceMax(items, metric) {
        var max = 0;
        for (var i = 0; i < items.length; i++) {
            var v = items[i][metricConfigs[metric].key];
            if (v > max) max = v;
        }
        if (max <= 0) return 0;

        var magnitude = Math.pow(10, Math.floor(Math.log10(max)));
        var nice = Math.ceil(max / magnitude) * magnitude;
        return nice;
    }

    function svgEl(name, attrs) {
        var el = document.createElementNS(NS, name);
        for (var key in attrs) {
            if (attrs.hasOwnProperty(key)) {
                el.setAttribute(key, attrs[key]);
            }
        }
        return el;
    }

    function renderChart(metric) {
        if (!svg) return;
        // Clear
        while (svg.firstChild) {
            svg.removeChild(svg.firstChild);
        }

        var items = chartData.items;
        if (!items || items.length === 0) {
            svg.setAttribute('hidden', '');
            if (emptyEl) {
                emptyEl.removeAttribute('hidden');
                emptyEl.textContent = 'Нет данных для графика.';
            }
            return;
        }

        svg.removeAttribute('hidden');
        if (emptyEl) emptyEl.setAttribute('hidden', '');

        var cfg = metricConfigs[metric];
        var pw = CHART_SVG_VIEWBOX.w - PADDING.left - PADDING.right;
        var ph = CHART_SVG_VIEWBOX.h - PADDING.top - PADDING.bottom;
        var n = items.length;

        var maxVal = findNiceMax(items, metric);
        if (maxVal <= 0) {
            svg.setAttribute('hidden', '');
            if (emptyEl) {
                emptyEl.removeAttribute('hidden');
                emptyEl.textContent = 'Нет данных для графика.';
            }
            return;
        }

        // Bar dimensions
        var gap = Math.max(5, pw / (n * 2.0));
        var barW = Math.min(MAX_BAR_WIDTH, Math.max(MIN_BAR_WIDTH, (pw - gap * (n + 1)) / n));
        var totalW = barW * n + gap * (n + 1);
        var offsetX = PADDING.left + (pw - totalW) / 2 + gap;

        // Grid
        var gridStep = Math.ceil(maxVal / GRID_LINES);
        for (var gi = 0; gi <= GRID_LINES; gi++) {
            var gv = gridStep * gi;
            var gy = PADDING.top + ph - (gv / maxVal) * ph;
            var gridLine = svgEl('line', {
                'x1': String(PADDING.left),
                'y1': String(Math.round(gy)),
                'x2': String(CHART_SVG_VIEWBOX.w - PADDING.right),
                'y2': String(Math.round(gy)),
                'stroke': 'rgba(76, 70, 62, 0.12)',
                'stroke-width': '1',
                'stroke-linecap': 'round',
                'class': 'chart-grid-line'
            });
            svg.appendChild(gridLine);

            // Y label
            var yLabel = svgEl('text', {
                'x': String(PADDING.left - 6),
                'y': String(Math.round(gy) + 3),
                'text-anchor': 'end',
                'fill': 'rgba(74, 68, 61, 0.58)',
                'font-size': '9',
                'font-weight': '700',
                'class': 'chart-axis-label'
            });
            yLabel.textContent = cfg.unit ? formatNumber(gv) + ' ' + cfg.unit : formatNumber(gv);
            svg.appendChild(yLabel);
        }

        // Bars
        for (var i = 0; i < n; i++) {
            var item = items[i];
            var val = item[cfg.key];
            var barH = Math.max(MIN_BAR_HEIGHT, (val / maxVal) * ph);
            var bx = Math.round(offsetX + i * (barW + gap));
            var by = Math.round(PADDING.top + ph - barH);

            var bar = svgEl('rect', {
                'x': String(bx),
                'y': String(by),
                'width': String(Math.round(barW)),
                'height': String(Math.round(barH)),
                'rx': String(BAR_RX),
                'ry': String(BAR_RY),
                'fill': cfg.color,
                'opacity': '0.82',
                'class': 'chart-bar',
                'data-index': String(i)
            });

            bar.addEventListener('mouseenter', function(e) {
                var idx = parseInt(this.getAttribute('data-index'));
                if (isNaN(idx) || !items[idx]) return;
                this.setAttribute('fill', cfg.hover);
                this.setAttribute('opacity', '0.96');
                showTooltip(e, items[idx]);
            });

            bar.addEventListener('mouseleave', function() {
                this.setAttribute('fill', cfg.color);
                this.setAttribute('opacity', '0.82');
                hideTooltip();
            });

            bar.addEventListener('mousemove', function(e) {
                updateTooltipPosition(e);
            });

            svg.appendChild(bar);
        }

        // X labels
        var showEvery = n <= 12 ? 1 : Math.ceil(n / 12);
        for (var j = 0; j < n; j++) {
            if (j % showEvery !== 0 && j !== n - 1) continue;
            var itemX = items[j];
            var cx = Math.round(offsetX + j * (barW + gap) + barW / 2);
            var xLabel = svgEl('text', {
                'x': String(cx),
                'y': String(CHART_SVG_VIEWBOX.h - PADDING.bottom + 14),
                'text-anchor': 'middle',
                'fill': 'rgba(74, 68, 61, 0.58)',
                'font-size': '9',
                'font-weight': '700',
                'class': 'chart-x-label'
            });
            xLabel.textContent = itemX.short_label || itemX.label;
            svg.appendChild(xLabel);
        }
    }

    function showTooltip(e, item) {
        if (!tooltip) return;
        tooltip.removeAttribute('hidden');
        var cfg = metricConfigs[currentMetric];

        tooltip.innerHTML =
            '<div style="font-weight:800;margin-bottom:3px;">' + item.label + '</div>' +
            '<div style="display:flex;align-items:center;gap:5px;margin-bottom:1px;">' +
            '<span style="display:inline-block;width:8px;height:8px;border-radius:1px;background:' + metricConfigs.weight.color + ';flex-shrink:0;"></span>' +
            'Масса: <b>' + formatMetricValue('weight', item.weight) + '</b>' +
            '</div>' +
            '<div style="display:flex;align-items:center;gap:5px;margin-bottom:1px;">' +
            '<span style="display:inline-block;width:8px;height:8px;border-radius:1px;background:' + metricConfigs.requests.color + ';flex-shrink:0;"></span>' +
            'Заявок: <b>' + item.requests.toLocaleString('ru-RU') + '</b>' +
            '</div>' +
            '<div style="display:flex;align-items:center;gap:5px;">' +
            '<span style="display:inline-block;width:8px;height:8px;border-radius:1px;background:' + metricConfigs.flights.color + ';flex-shrink:0;"></span>' +
            'Рейсов: <b>' + item.flights.toLocaleString('ru-RU') + '</b>' +
            '</div>';

        updateTooltipPosition(e);
    }

    function updateTooltipPosition(e) {
        if (!tooltip || tooltip.hasAttribute('hidden')) return;
        var bodyRect = chartPanel.querySelector('.statistics-chart-body').getBoundingClientRect();
        var x = e.clientX - bodyRect.left + 12;
        var y = e.clientY - bodyRect.top - 10;

        // Clamp to panel
        var tw = tooltip.offsetWidth;
        var th = tooltip.offsetHeight;
        var maxX = bodyRect.width - tw - 4;
        var maxY = bodyRect.height - th - 4;

        if (x > maxX) x = e.clientX - bodyRect.left - tw - 12;
        if (y > maxY) y = maxY;
        if (x < 4) x = 4;
        if (y < 4) y = 4;

        tooltip.style.left = x + 'px';
        tooltip.style.top = y + 'px';
    }

    function hideTooltip() {
        if (tooltip) tooltip.setAttribute('hidden', '');
    }

    function setActiveMetric(metric) {
        currentMetric = metric;
        switchBtns.forEach(function (btn) {
            var isActive = btn.getAttribute('data-chart-metric') === metric;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        renderChart(metric);
    }

    switchBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setActiveMetric(this.getAttribute('data-chart-metric'));
        });
    });

    // Initial render
    renderChart(currentMetric);
})();
</script>
