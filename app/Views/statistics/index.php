<?php
/**
 * Контент страницы «Статистика вывозов» (TransportERP shell).
 *
 * Переменные:
 *   $period      — week|month|custom
 *   $dateType    — delivery|pickup
 *   $dateFrom    — YYYY-MM-DD
 *   $dateTo      — YYYY-MM-DD
 *   $warning     — string|null
 *   $summary     — [periods_count, requests_total, flights_total, weight_total_kg, ...]
 *   $rows        — array of period rows
 *   $chartData   — [enabled, period, date_type, items, ...]
 *   $chartMetric — weight|requests|flights
 *   $service     — StatisticsService
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
/** @var string $chartMetric */
/** @var StatisticsService $service */
/** @var array $filterStats */
$statsRequestsTotal = (int) ($summary['requests_total'] ?? 0);
$statsWeightTotal   = (int) ($summary['weight_total_kg'] ?? 0);
$statsRowsCount     = count($rows);
$filterTotalBefore  = (int) ($filterStats['total_before'] ?? 0);
$filterExcludedWW   = (int) ($filterStats['excluded_warehouse_to_warehouse'] ?? 0);
$filterExcludedWU   = (int) ($filterStats['excluded_warehouse_to_utilizer'] ?? 0);
$filterTotalAfter   = (int) ($filterStats['total_after'] ?? 0);
?>
<!-- stats audit: period=<?= htmlspecialchars($period, ENT_QUOTES, 'UTF-8') ?> dateType=<?= htmlspecialchars($dateType, ENT_QUOTES, 'UTF-8') ?> from=<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?> to=<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?> rows=<?= $statsRowsCount ?> requests=<?= $statsRequestsTotal ?> weight=<?= $statsWeightTotal ?> -->
<!-- statistics route-filter: dateType=<?= htmlspecialchars($dateType, ENT_QUOTES, 'UTF-8') ?> excludedRouteTypes=<?= $dateType === 'pickup' ? 'warehouse_to_warehouse,warehouse_to_utilizer' : 'warehouse_to_warehouse' ?> flightsBefore=<?= $filterTotalBefore ?> excludedWW=<?= $filterExcludedWW ?> excludedWU=<?= $filterExcludedWU ?> flightsAfter=<?= $filterTotalAfter ?> -->
<!-- Page header -->
<div class="page-head">
    <div class="page-head-left">
        <div class="page-title">Статистика вывозов</div>
        <div class="page-summary"><span>Количество заявок и масса по выбранному основанию</span></div>
    </div>
</div>

<!-- Filter bar -->
<form class="statistics-filters" data-statistics-filter-form method="get" action="">
    <input type="hidden" name="module" value="statistics">
    <input type="hidden" name="chart_metric" id="chartMetricInput" value="<?= htmlspecialchars($chartMetric, ENT_QUOTES, 'UTF-8') ?>">

    <div class="filter-field" style="width: 140px;">
        <label for="periodSelect">Группировка</label>
        <select id="periodSelect" name="period" style="height: 24px; padding: 0 6px; font-size: 11px; font-weight: 600; border: 1px solid var(--line-soft); background: var(--surface-field); border-radius: 2px;">
            <option value="week"   <?= $period === 'week'   ? 'selected' : '' ?>>По неделям</option>
            <option value="month"  <?= $period === 'month'  ? 'selected' : '' ?>>По месяцам</option>
            <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Произвольный период</option>
        </select>
    </div>

    <div class="filter-field" style="width: 130px;">
        <label for="dateTypeSelect">Строить по</label>
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
        <div class="statistics-chart-inner">
            <div class="statistics-chart-head">
                <div class="statistics-chart-heading">
                    <div class="statistics-chart-title">Динамика по периодам</div>
                    <div class="statistics-chart-subtitle">Масса, заявки и рейсы по выбранному основанию</div>
                </div>

                <div class="statistics-chart-switch" role="group" aria-label="Метрика графика">
                    <button type="button" class="chart-metric-btn<?= $chartMetric === 'weight' ? ' active' : '' ?>" data-chart-metric="weight" aria-pressed="<?= $chartMetric === 'weight' ? 'true' : 'false' ?>">Масса</button>
                    <button type="button" class="chart-metric-btn<?= $chartMetric === 'requests' ? ' active' : '' ?>" data-chart-metric="requests" aria-pressed="<?= $chartMetric === 'requests' ? 'true' : 'false' ?>">Заявки</button>
                    <button type="button" class="chart-metric-btn<?= $chartMetric === 'flights' ? ' active' : '' ?>" data-chart-metric="flights" aria-pressed="<?= $chartMetric === 'flights' ? 'true' : 'false' ?>">Рейсы</button>
                </div>
            </div>

            <div class="statistics-chart-body">
                <!-- statistics chart: viewBox=1200x210 innerMax=1200 currentMetric=<?= htmlspecialchars($chartMetric, ENT_QUOTES, 'UTF-8') ?><?= ($chartData['enabled'] && !empty($chartData['scale_max'])) ? ' scaleMax=enabled' : '' ?> -->
                <svg class="statistics-chart-svg" viewBox="0 0 1200 210" preserveAspectRatio="none" role="img" aria-label="График статистики по периодам"<?= $chartData['enabled'] ? '' : ' hidden' ?>></svg>

                <div class="statistics-chart-tooltip" hidden></div>

                <div class="statistics-chart-empty"<?= $chartData['enabled'] ? ' hidden' : '' ?>>
                    <?= $period === 'custom' ? 'Для графика выберите группировку по неделям или месяцам.' : 'Нет данных для графика.' ?>
                </div>

                <div class="statistics-chart-micro-summary" data-chart-micro-summary<?= $chartData['enabled'] ? '' : ' hidden' ?>>
                    <span class="micro-stat"><span class="micro-label">Мин.</span> <b data-chart-min>—</b></span>
                    <span class="micro-sep"></span>
                    <span class="micro-stat"><span class="micro-label">Макс.</span> <b data-chart-max>—</b></span>
                    <span class="micro-sep"></span>
                    <span class="micro-stat"><span class="micro-label">Сред.</span> <b data-chart-avg>—</b></span>
                </div>
            </div>
        </div>

        <script type="application/json" id="statisticsChartData"><?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
    </section>

    <div class="table-scroll">
        <?php if (!empty($rows)): ?>
        <?php $showWeekCol = ($period === 'week'); ?>
        <table class="table statistics-table">
            <colgroup>
                <?php if ($showWeekCol): ?><col class="col-stat-week"><?php endif; ?>
                <col class="col-stat-period">
                <col class="col-stat-requests">
                <col class="col-stat-flights">
                <col class="col-stat-weight">
                <col class="col-stat-avg-request">
                <col class="col-stat-avg-flight">
            </colgroup>
            <thead>
                <tr>
                    <?php if ($showWeekCol): ?><th>НЕД.</th><?php endif; ?>
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
                    <?php if ($showWeekCol): ?>
                    <td class="cell-num"><?= $row['week_number'] !== null ? (int) $row['week_number'] : '—' ?></td>
                    <?php endif; ?>
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
    var CHART_SVG_VIEWBOX = { w: 1200, h: 210 };
    var PADDING = { left: 48, right: 24, top: 18, bottom: 34 };
    var GRID_LINES = 4;
    var LINE_WIDTH = 2.5;
    var DOT_RADIUS = 3.5;
    var DOT_HOVER_RADIUS = 5;
    var SMOOTH_TENSION = 0.18;

    var metricConfigs = {
        weight: {
            label: 'Масса',
            key: 'weight',
            unit: 'кг',
            unitLabel: 'Масса, т',
            color: '#A1622A',
            hover: '#7A421C',
            soft: 'rgba(161, 98, 42, 0.055)'
        },
        requests: {
            label: 'Заявки',
            key: 'requests',
            unit: '',
            unitLabel: 'Заявки, шт.',
            color: '#4E6F86',
            hover: '#35566B',
            soft: 'rgba(78, 111, 134, 0.055)'
        },
        flights: {
            label: 'Рейсы',
            key: 'flights',
            unit: '',
            unitLabel: 'Рейсы, шт.',
            color: '#6F7458',
            hover: '#54593F',
            soft: 'rgba(111, 116, 88, 0.055)'
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
    var microSummary = chartPanel.querySelector('[data-chart-micro-summary]');
    var microMin = chartPanel.querySelector('[data-chart-min]');
    var microMax = chartPanel.querySelector('[data-chart-max]');
    var microAvg = chartPanel.querySelector('[data-chart-avg]');
    var switchBtns = chartPanel.querySelectorAll('.chart-metric-btn');

    var chartMetricInput = document.getElementById('chartMetricInput');
    var currentMetric = chartMetricInput ? chartMetricInput.value : 'requests';
    if (!metricConfigs[currentMetric]) currentMetric = 'requests';
    var guideLine = null;

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

    // Build smooth Catmull-Rom → cubic Bezier path
    function buildSmoothPath(points, tension) {
        if (points.length < 2) return '';
        tension = tension || 0.3;
        var d = '';
        var n = points.length;

        for (var i = 0; i < n - 1; i++) {
            var p0 = i > 0 ? points[i - 1] : points[i];
            var p1 = points[i];
            var p2 = points[i + 1];
            var p3 = i < n - 2 ? points[i + 2] : p2;

            var cp1x = p1.x + (p2.x - p0.x) * tension;
            var cp1y = p1.y + (p2.y - p0.y) * tension;
            var cp2x = p2.x - (p3.x - p1.x) * tension;
            var cp2y = p2.y - (p3.y - p1.y) * tension;

            if (i === 0) {
                d += 'M' + p1.x.toFixed(1) + ',' + p1.y.toFixed(1);
            }
            d += ' C' + cp1x.toFixed(1) + ',' + cp1y.toFixed(1) +
                 ' ' + cp2x.toFixed(1) + ',' + cp2y.toFixed(1) +
                 ' ' + p2.x.toFixed(1) + ',' + p2.y.toFixed(1);
        }
        return d;
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

        // Use unified scale_max if available, else fallback to local nice max
        var maxVal;
        if (chartData.scale_max && typeof chartData.scale_max[metric] === 'number' && chartData.scale_max[metric] > 0) {
            maxVal = chartData.scale_max[metric];
        } else {
            maxVal = findNiceMax(items, metric);
        }
        if (maxVal <= 0) {
            svg.setAttribute('hidden', '');
            if (emptyEl) {
                emptyEl.removeAttribute('hidden');
                emptyEl.textContent = 'Нет данных для графика.';
            }
            return;
        }

        // Compute point positions
        var points = [];
        var stepX = n > 1 ? pw / (n - 1) : 0;
        for (var i = 0; i < n; i++) {
            var val = items[i][cfg.key];
            var px = PADDING.left + i * stepX;
            var py = PADDING.top + ph - (val / maxVal) * ph;
            points.push({ x: px, y: Math.round(py) });
        }
        // Single point case: center it
        if (n === 1) {
            points[0].x = PADDING.left + pw / 2;
        }

        // Unit label
        if (cfg.unitLabel) {
            var unitText = svgEl('text', {
                'x': String(PADDING.left),
                'y': '14',
                'fill': 'rgba(74, 68, 61, 0.48)',
                'font-size': '9',
                'font-weight': '700',
                'class': 'chart-axis-label'
            });
            unitText.textContent = cfg.unitLabel;
            svg.appendChild(unitText);
        }

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

            var yLabel = svgEl('text', {
                'x': String(PADDING.left - 6),
                'y': String(Math.round(gy) + 3),
                'text-anchor': 'end',
                'fill': 'rgba(74, 68, 61, 0.58)',
                'font-size': '9',
                'font-weight': '700',
                'class': 'chart-axis-label'
            });
            yLabel.textContent = (metric === 'weight') ? String(Math.round(gv / 1000)) : (cfg.unit ? formatNumber(gv) + ' ' + cfg.unit : formatNumber(gv));
            svg.appendChild(yLabel);
        }

        // Add subtle area fill under the line
        if (n > 1) {
            var areaD = buildSmoothPath(points, SMOOTH_TENSION);
            if (areaD) {
                var lastX = points[n - 1].x;
                var baseY = PADDING.top + ph;
                var firstX = points[0].x;
                areaD += ' L' + lastX.toFixed(1) + ',' + baseY.toFixed(1) +
                         ' L' + firstX.toFixed(1) + ',' + baseY.toFixed(1) + ' Z';
                var area = svgEl('path', {
                    'd': areaD,
                    'fill': cfg.soft,
                    'class': 'chart-area'
                });
                svg.appendChild(area);
            }
        }

        // Smooth line path
        var lineD = buildSmoothPath(points, SMOOTH_TENSION);
        if (lineD) {
            var path = svgEl('path', {
                'd': lineD,
                'fill': 'none',
                'stroke': cfg.color,
                'stroke-width': String(LINE_WIDTH),
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'class': 'chart-line'
            });
            svg.appendChild(path);
        }

        // Guide line element (hidden by default)
        guideLine = svgEl('line', {
            'x1': '0', 'y1': '0', 'x2': '0', 'y2': '0',
            'stroke': 'rgba(74, 68, 61, 0.16)',
            'stroke-width': '1',
            'stroke-linecap': 'round',
            'stroke-dasharray': '2 3',
            'class': 'chart-guide-line'
        });
        guideLine.setAttribute('hidden', '');
        svg.appendChild(guideLine);

        // Dots
        for (var j = 0; j < n; j++) {
            var pt = points[j];

            var circle = svgEl('circle', {
                'cx': String(Math.round(pt.x)),
                'cy': String(Math.round(pt.y)),
                'r': String(DOT_RADIUS),
                'fill': '#fefdf8',
                'stroke': cfg.color,
                'stroke-width': '1.5',
                'class': 'chart-dot',
                'data-index': String(j)
            });

            circle.addEventListener('mouseenter', function(e) {
                var idx = parseInt(this.getAttribute('data-index'), 10);
                if (isNaN(idx) || !items[idx] || !points[idx]) return;

                var hoverPoint = points[idx];

                this.setAttribute('r', String(DOT_HOVER_RADIUS));
                this.setAttribute('stroke', cfg.hover);
                this.setAttribute('stroke-width', '2');
                // Show guide-line under the hovered point
                if (guideLine) {
                    guideLine.removeAttribute('hidden');
                    guideLine.setAttribute('x1', String(Math.round(hoverPoint.x)));
                    guideLine.setAttribute('y1', String(Math.round(hoverPoint.y) + DOT_HOVER_RADIUS + 2));
                    guideLine.setAttribute('x2', String(Math.round(hoverPoint.x)));
                    guideLine.setAttribute('y2', String(PADDING.top + ph));
                }
                showTooltip(e, items[idx]);
            });

            circle.addEventListener('mouseleave', function() {
                this.setAttribute('r', String(DOT_RADIUS));
                this.setAttribute('stroke', cfg.color);
                this.setAttribute('stroke-width', '1.5');
                if (guideLine) guideLine.setAttribute('hidden', '');
                hideTooltip();
            });

            circle.addEventListener('mousemove', function(e) {
                updateTooltipPosition(e);
            });

            svg.appendChild(circle);
        }

        // X labels
        var showEvery = n <= 12 ? 1 : Math.ceil(n / 12);
        for (var k = 0; k < n; k++) {
            if (k % showEvery !== 0 && k !== n - 1) continue;
            var ptX = points[k];
            var xLabel = svgEl('text', {
                'x': String(Math.round(ptX.x)),
                'y': String(CHART_SVG_VIEWBOX.h - PADDING.bottom + 14),
                'text-anchor': 'middle',
                'fill': 'rgba(74, 68, 61, 0.58)',
                'font-size': '9',
                'font-weight': '700',
                'class': 'chart-x-label'
            });
            xLabel.textContent = items[k].short_label || items[k].label;
            svg.appendChild(xLabel);
        }
    }

    function showTooltip(e, item) {
        if (!tooltip) return;
        tooltip.removeAttribute('hidden');

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

    function updateMicroSummary(metric) {
        if (!microMin || !microMax || !microAvg || !microSummary) return;
        var items = chartData.items;
        if (!items || items.length === 0) {
            microSummary.setAttribute('hidden', '');
            return;
        }
        microSummary.removeAttribute('hidden');
        var cfg = metricConfigs[metric];
        var min = Infinity, max = -Infinity, sum = 0, count = 0;
        for (var i = 0; i < items.length; i++) {
            var v = items[i][cfg.key];
            if (v < min) min = v;
            if (v > max) max = v;
            sum += v;
            count++;
        }
        if (count === 0) {
            microMin.textContent = '\u2014';
            microMax.textContent = '\u2014';
            microAvg.textContent = '\u2014';
            return;
        }
        var avg = Math.round(sum / count);
        microMin.textContent = cfg.unit ? min.toLocaleString('ru-RU') + ' ' + cfg.unit : min.toLocaleString('ru-RU');
        microMax.textContent = cfg.unit ? max.toLocaleString('ru-RU') + ' ' + cfg.unit : max.toLocaleString('ru-RU');
        microAvg.textContent = cfg.unit ? avg.toLocaleString('ru-RU') + ' ' + cfg.unit : avg.toLocaleString('ru-RU');
    }

    function setActiveMetric(metric) {
        currentMetric = metric;
        switchBtns.forEach(function (btn) {
            var isActive = btn.getAttribute('data-chart-metric') === metric;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        renderChart(metric);
        updateMicroSummary(metric);
        if (chartMetricInput) chartMetricInput.value = metric;
    }

    switchBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setActiveMetric(this.getAttribute('data-chart-metric'));
        });
    });

    // Initial render — must sync active state, chart, micro-summary and hidden input
    setActiveMetric(currentMetric);
})();
</script>
