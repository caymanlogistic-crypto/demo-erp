<?php
/**
 * Контент страницы «Отчётность» (TransportERP shell).
 *
 * Переменные:
 *   $period       — week|month|custom
 *   $dateType     — delivery|pickup
 *   $dimension    — fo|region|status
 *   $chartMetric  — requests|weight
 *   $dateFrom     — YYYY-MM-DD
 *   $dateTo       — YYYY-MM-DD
 *   $warning      — string|null
 *   $summary      — [rows_total, requests_total, weight_total_kg, avg_request_kg]
 *   $rows         — array of report rows
 *   $unmatched    — int
 *   $chartData    — [enabled, items]
 *   $service      — ReportsService
 */

use App\Modules\Reports\Services\ReportsService;

/** @var string $period */
/** @var string $dateType */
/** @var string $dimension */
/** @var string $chartMetric */
/** @var string $dateFrom */
/** @var string $dateTo */
/** @var string|null $warning */
/** @var array $summary */
/** @var array $rows */
/** @var int $unmatched */
/** @var array $chartData */
/** @var ReportsService $service */

$totalRows = $summary['rows_total'] ?? count($rows);

$chartTitle = match ($dimension) {
    'fo'     => 'Распределение по федеральным округам',
    'region' => 'Топ регионов',
    'status' => 'Распределение по статусам',
};
$chartSubtitle = 'Масса и заявки по выбранному основанию';
?>
<!-- reports page: dimension=<?= htmlspecialchars($dimension, ENT_QUOTES, 'UTF-8') ?> period=<?= htmlspecialchars($period, ENT_QUOTES, 'UTF-8') ?> dateType=<?= htmlspecialchars($dateType, ENT_QUOTES, 'UTF-8') ?> chartMetric=<?= htmlspecialchars($chartMetric, ENT_QUOTES, 'UTF-8') ?> rows=<?= $totalRows ?> unmatched=<?= (int) $unmatched ?> -->

<!-- Page header -->
<div class="page-head">
    <div class="page-head-left">
        <div class="page-title">Отчётность</div>
        <div class="page-summary"><span>Сводные отчёты по заявкам, статусам, регионам и федеральным округам</span></div>
    </div>
</div>

<!-- Filter bar (как Statistics) -->
<form class="reports-filters" data-reports-filter-form method="get" action="">
    <input type="hidden" name="module" value="reports">
    <input type="hidden" name="chart_metric" id="chartMetricInput" value="<?= htmlspecialchars($chartMetric, ENT_QUOTES, 'UTF-8') ?>">

    <div class="filter-field" style="width: 140px;">
        <label for="periodSelect">Группировка</label>
        <select id="periodSelect" name="period">
            <option value="week"   <?= $period === 'week'   ? 'selected' : '' ?>>По неделям</option>
            <option value="month"  <?= $period === 'month'  ? 'selected' : '' ?>>По месяцам</option>
            <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Произвольный период</option>
        </select>
    </div>

    <div class="filter-field" style="width: 130px;">
        <label for="dateTypeSelect">Строить по</label>
        <select id="dateTypeSelect" name="date_type">
            <option value="delivery" <?= $dateType === 'delivery' ? 'selected' : '' ?>>Доставка</option>
            <option value="pickup"   <?= $dateType === 'pickup'   ? 'selected' : '' ?>>Вывоз</option>
        </select>
    </div>

    <div class="filter-field" style="width: 140px;">
        <label for="dimensionSelect">Разрез</label>
        <select id="dimensionSelect" name="dimension">
            <option value="fo"     <?= $dimension === 'fo'     ? 'selected' : '' ?>>По ФО</option>
            <option value="region" <?= $dimension === 'region' ? 'selected' : '' ?>>По регионам</option>
            <option value="status" <?= $dimension === 'status' ? 'selected' : '' ?>>По статусам</option>
        </select>
    </div>

    <div class="filter-field reports-date-range-field" data-custom-period-field<?= $period !== 'custom' ? ' hidden' : '' ?> style="width: 150px;">
        <label for="filterDateFrom">Дата с</label>
        <input type="date" id="filterDateFrom" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>"<?= $period !== 'custom' ? ' disabled' : '' ?>>
    </div>

    <div class="filter-field reports-date-range-field" data-custom-period-field<?= $period !== 'custom' ? ' hidden' : '' ?> style="width: 150px;">
        <label for="filterDateTo">Дата по</label>
        <input type="date" id="filterDateTo" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>"<?= $period !== 'custom' ? ' disabled' : '' ?>>
    </div>

    <div class="filter-actions" data-custom-period-action<?= $period !== 'custom' ? ' hidden' : '' ?>>
        <button type="submit" class="btn btn-toolbar">Применить</button>
        <a class="btn btn-toolbar" href="?module=reports" style="text-decoration: none;">Сбросить</a>
    </div>
</form>

<?php if ($warning !== null): ?>
<div class="form-alert alert-warning" style="margin: 0;"><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Table card -->
<div class="table-card reports-table-card" style="flex: 1; min-height: 0;">
    <div class="table-toolbar">
        <div class="summary-left">
            <span class="summary-main">
                <span class="summary-main-dot"></span>
                <span class="summary-main-label">Найдено:</span>
                <b><?= $totalRows ?></b>
                <span class="summary-main-unit">строк</span>
            </span>
        </div>
        <div class="summary-right">
            <span class="summary-chip summary-chip-requests">
                <span class="summary-chip-dot"></span>
                <span>Заявок</span>
                <b><?= number_format((int) ($summary['requests_total'] ?? 0), 0, '.', ' ') ?></b>
            </span>
            <span class="summary-chip summary-chip-weight">
                <span class="summary-chip-dot"></span>
                <span>Масса</span>
                <b><?= number_format((int) ($summary['weight_total_kg'] ?? 0), 0, '.', ' ') ?> кг</b>
            </span>
            <span class="summary-chip summary-chip-avg">
                <span class="summary-chip-dot"></span>
                <span>Средняя заявка</span>
                <b><?= ($summary['avg_request_kg'] ?? 0) > 0 ? number_format((int) ($summary['avg_request_kg'] ?? 0), 0, '.', ' ') . ' кг' : '—' ?></b>
            </span>
        </div>
    </div>

    <!-- Chart panel -->
    <section class="reports-chart-panel" data-reports-chart>
        <div class="reports-chart-inner">
            <div class="reports-chart-head">
                <div class="reports-chart-heading">
                    <div class="reports-chart-title"><?= htmlspecialchars($chartTitle, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="reports-chart-subtitle"><?= htmlspecialchars($chartSubtitle, ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="reports-chart-switch" role="group" aria-label="Метрика графика">
                    <button type="button" class="chart-metric-btn<?= $chartMetric === 'weight' ? ' active' : '' ?>" data-chart-metric="weight" aria-pressed="<?= $chartMetric === 'weight' ? 'true' : 'false' ?>">Масса</button>
                    <button type="button" class="chart-metric-btn<?= $chartMetric === 'requests' ? ' active' : '' ?>" data-chart-metric="requests" aria-pressed="<?= $chartMetric === 'requests' ? 'true' : 'false' ?>">Заявки</button>
                </div>
            </div>

            <div class="reports-chart-body">
                <!-- reports chart: enabled type=bar metric=<?= htmlspecialchars($chartMetric, ENT_QUOTES, 'UTF-8') ?> items=<?= count($chartData['items'] ?? []) ?> -->
                <svg class="reports-chart-svg" viewBox="0 0 800 220" preserveAspectRatio="none" role="img" aria-label="График отчётности"<?= $chartData['enabled'] ? '' : ' hidden' ?>></svg>

                <div class="reports-chart-empty"<?= $chartData['enabled'] ? ' hidden' : '' ?>>
                    <?= !empty($rows) ? 'Нет данных для графика.' : 'Нет данных для отображения.' ?>
                </div>
            </div>
        </div>

        <script type="application/json" id="reportsChartData"><?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
    </section>

    <div class="table-scroll">
        <?php if (!empty($rows)): ?>
        <table class="table reports-table">
            <colgroup>
                <col class="col-reports-period">
                <?php if ($dimension === 'fo'): ?>
                <col class="col-reports-district">
                <col class="col-reports-requests">
                <col class="col-reports-weight">
                <col class="col-reports-avg">
                <col class="col-reports-share-r">
                <col class="col-reports-share-w">
                <?php elseif ($dimension === 'region'): ?>
                <col class="col-reports-region">
                <col class="col-reports-fo">
                <col class="col-reports-requests">
                <col class="col-reports-weight">
                <col class="col-reports-avg">
                <col class="col-reports-share-r">
                <col class="col-reports-share-w">
                <?php else: ?>
                <col class="col-reports-status">
                <col class="col-reports-requests">
                <col class="col-reports-weight">
                <col class="col-reports-avg">
                <col class="col-reports-share-r">
                <col class="col-reports-share-w">
                <?php endif; ?>
            </colgroup>
            <thead>
                <tr>
                    <th>ПЕРИОД</th>
                    <?php if ($dimension === 'fo'): ?>
                    <th>ФО</th>
                    <th style="text-align: right;">ЗАЯВОК</th>
                    <th style="text-align: right;">МАССА, КГ</th>
                    <th style="text-align: right;">СР. ЗАЯВКА, КГ</th>
                    <th style="text-align: right;">ДОЛЯ ЗАЯВОК</th>
                    <th style="text-align: right;">ДОЛЯ МАССЫ</th>
                    <?php elseif ($dimension === 'region'): ?>
                    <th>РЕГИОН</th>
                    <th>ФО</th>
                    <th style="text-align: right;">ЗАЯВОК</th>
                    <th style="text-align: right;">МАССА, КГ</th>
                    <th style="text-align: right;">СР. ЗАЯВКА, КГ</th>
                    <th style="text-align: right;">ДОЛЯ ЗАЯВОК</th>
                    <th style="text-align: right;">ДОЛЯ МАССЫ</th>
                    <?php else: ?>
                    <th>СТАТУС</th>
                    <th style="text-align: right;">ЗАЯВОК</th>
                    <th style="text-align: right;">МАССА, КГ</th>
                    <th style="text-align: right;">СР. ЗАЯВКА, КГ</th>
                    <th style="text-align: right;">ДОЛЯ ЗАЯВОК</th>
                    <th style="text-align: right;">ДОЛЯ МАССЫ</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalWeightSum = 0.0;
                $totalRequestsSum = 0;
                foreach ($rows as $r) { $totalWeightSum += $r['weight_kg'] ?? 0; $totalRequestsSum += $r['requests'] ?? 0; }
                $periodLabel = $service->formatPeriodLabel($period === 'month' ? date('Y-m') : ($period === 'week' ? date('o-\WW') : 'all'), $period, $dateFrom, $dateTo);
                foreach ($rows as $row):
                    $rWeight = $row['weight_kg'] ?? 0;
                    $rRequests = $row['requests'] ?? 0;
                    $rAvg = $row['avg_request_kg'] ?? 0;
                    $shareR = $totalRequestsSum > 0 ? round(($rRequests / $totalRequestsSum) * 100, 1) : 0;
                    $shareW = $totalWeightSum > 0 ? round(($rWeight / $totalWeightSum) * 100, 1) : 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?></td>
                    <?php if ($dimension === 'fo'): ?>
                    <td class="report-cell-district" title="<?= htmlspecialchars($row['district_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($row['district_short'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="cell-num"><?= ReportsService::formatCount($rRequests) ?></td>
                    <td class="cell-weight"><?= ReportsService::formatWeight($rWeight) ?></td>
                    <td class="cell-weight"><?= $rAvg > 0 ? number_format($rAvg, 0, '.', ' ') . ' кг' : '—' ?></td>
                    <td class="cell-num"><?= $shareR > 0 ? number_format($shareR, 1, ',', '') . ' %' : '—' ?></td>
                    <td class="cell-num"><?= $shareW > 0 ? number_format($shareW, 1, ',', '') . ' %' : '—' ?></td>
                    <?php elseif ($dimension === 'region'): ?>
                    <td class="report-cell-region" title="<?= htmlspecialchars($row['region'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <span class="report-region-name"><?= htmlspecialchars($row['region'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($row['district_short'])): ?>
                        <span class="report-muted"><?= htmlspecialchars($row['district_short'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="report-cell-district"><?= htmlspecialchars($row['district_short'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="cell-num"><?= ReportsService::formatCount($rRequests) ?></td>
                    <td class="cell-weight"><?= ReportsService::formatWeight($rWeight) ?></td>
                    <td class="cell-weight"><?= $rAvg > 0 ? number_format($rAvg, 0, '.', ' ') . ' кг' : '—' ?></td>
                    <td class="cell-num"><?= $shareR > 0 ? number_format($shareR, 1, ',', '') . ' %' : '—' ?></td>
                    <td class="cell-num"><?= $shareW > 0 ? number_format($shareW, 1, ',', '') . ' %' : '—' ?></td>
                    <?php else: ?>
                    <td class="report-cell-district"><?= htmlspecialchars($row['status_label'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="cell-num"><?= ReportsService::formatCount($rRequests) ?></td>
                    <td class="cell-weight"><?= ReportsService::formatWeight($rWeight) ?></td>
                    <td class="cell-weight"><?= $rAvg > 0 ? number_format($rAvg, 0, '.', ' ') . ' кг' : '—' ?></td>
                    <td class="cell-num"><?= $shareR > 0 ? number_format($shareR, 1, ',', '') . ' %' : '—' ?></td>
                    <td class="cell-num"><?= $shareW > 0 ? number_format($shareW, 1, ',', '') . ' %' : '—' ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 13px;">
            Нет данных для отображения
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var form = document.querySelector('[data-reports-filter-form]');
    var periodSelect = document.getElementById('periodSelect');
    var dateTypeSelect = document.getElementById('dateTypeSelect');
    var dimensionSelect = document.getElementById('dimensionSelect');
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
        if (!form || isCustom()) return;

        var dateFrom = form.querySelector('[name="date_from"]');
        var dateTo = form.querySelector('[name="date_to"]');

        if (dateFrom) dateFrom.disabled = true;
        if (dateTo) dateTo.disabled = true;

        form.submit();
    }

    if (periodSelect) {
        periodSelect.addEventListener('change', function () {
            updateCustomVisibility();
            if (!isCustom()) submitAuto();
        });
    }

    if (dateTypeSelect) {
        dateTypeSelect.addEventListener('change', function () {
            if (!isCustom()) submitAuto();
        });
    }

    if (dimensionSelect) {
        dimensionSelect.addEventListener('change', function () {
            if (!isCustom()) submitAuto();
        });
    }

    updateCustomVisibility();
})();
</script>

<script>
(function () {
    var CHART_SVG_VIEWBOX = { w: 800, h: 220 };
    var PADDING = { left: 90, right: 70, top: 14, bottom: 14 };
    var BAR_HEIGHT = 18;
    var BAR_GAP = 4;

    var metricConfigs = {
        weight: { label: 'Масса', key: 'weight', unit: 'кг', color: '#A1622A', hover: '#7A421C', soft: 'rgba(161, 98, 42, 0.14)' },
        requests: { label: 'Заявки', key: 'requests', unit: '', color: '#4E6F86', hover: '#35566B', soft: 'rgba(78, 111, 134, 0.14)' }
    };

    var NS = 'http://www.w3.org/2000/svg';

    var chartPanel = document.querySelector('[data-reports-chart]');
    var jsonEl = document.getElementById('reportsChartData');
    var chartData = null;

    if (jsonEl) {
        try { chartData = JSON.parse(jsonEl.textContent); } catch (e) { chartData = null; }
    }

    if (!chartPanel || !chartData || !chartData.enabled) return;

    var svg = chartPanel.querySelector('.reports-chart-svg');
    var emptyEl = chartPanel.querySelector('.reports-chart-empty');
    var switchBtns = chartPanel.querySelectorAll('.chart-metric-btn');
    var chartMetricInput = document.getElementById('chartMetricInput');
    var currentMetric = chartMetricInput ? chartMetricInput.value : 'requests';
    if (!metricConfigs[currentMetric]) currentMetric = 'requests';

    function formatNumber(value) {
        if (value >= 1000000) return (value / 1000000).toFixed(1).replace('.0', '') + ' млн';
        if (value >= 1000) return (value / 1000).toFixed(0) + ' тыс';
        return String(value);
    }

    function svgEl(name, attrs) {
        var el = document.createElementNS(NS, name);
        for (var key in attrs) {
            if (attrs.hasOwnProperty(key)) el.setAttribute(key, attrs[key]);
        }
        return el;
    }

    function renderChart(metric) {
        if (!svg) return;
        while (svg.firstChild) svg.removeChild(svg.firstChild);

        var items = chartData.items;
        if (!items || items.length === 0) {
            svg.setAttribute('hidden', '');
            if (emptyEl) emptyEl.removeAttribute('hidden');
            return;
        }

        svg.removeAttribute('hidden');
        if (emptyEl) emptyEl.setAttribute('hidden', '');

        var cfg = metricConfigs[metric];
        var n = items.length;
        var barAreaHeight = n * (BAR_HEIGHT + BAR_GAP);
        var totalH = Math.max(220, barAreaHeight + PADDING.top + PADDING.bottom + 10);
        svg.setAttribute('viewBox', '0 0 800 ' + totalH);

        var barAreaW = CHART_SVG_VIEWBOX.w - PADDING.left - PADDING.right;
        var barStartY = PADDING.top;

        var maxVal = 0;
        for (var i = 0; i < n; i++) {
            var v = items[i][cfg.key];
            if (v > maxVal) maxVal = v;
        }
        if (maxVal <= 0) {
            svg.setAttribute('hidden', '');
            if (emptyEl) emptyEl.removeAttribute('hidden');
            return;
        }

        var magnitude = Math.pow(10, Math.floor(Math.log10(maxVal)));
        maxVal = Math.ceil(maxVal / magnitude) * magnitude;
        if (maxVal <= 0) { svg.setAttribute('hidden', ''); return; }

        for (var i = 0; i < n; i++) {
            var item = items[i];
            var val = item[cfg.key] || 0;
            var barW = (val / maxVal) * barAreaW;
            var y = barStartY + i * (BAR_HEIGHT + BAR_GAP);
            var x = PADDING.left;

            var labelText = svgEl('text', {
                'x': String(PADDING.left - 6),
                'y': String(y + BAR_HEIGHT / 2 + 4),
                'text-anchor': 'end',
                'fill': 'rgba(74, 68, 61, 0.72)',
                'font-size': '10',
                'font-weight': '600',
                'class': 'chart-axis-label'
            });
            labelText.textContent = item.short_label || item.label;
            svg.appendChild(labelText);

            if (barW > 0) {
                var rect = svgEl('rect', {
                    'x': String(x),
                    'y': String(y),
                    'width': String(Math.max(barW, 2)),
                    'height': String(BAR_HEIGHT),
                    'fill': cfg.color,
                    'rx': '1',
                    'ry': '1',
                    'class': 'chart-bar'
                });
                svg.appendChild(rect);

                var valText = svgEl('text', {
                    'x': String(x + barW + 6),
                    'y': String(y + BAR_HEIGHT / 2 + 4),
                    'fill': 'rgba(74, 68, 61, 0.58)',
                    'font-size': '10',
                    'font-weight': '700',
                    'class': 'chart-value-label'
                });
                valText.textContent = cfg.unit ? formatNumber(val) + ' ' + cfg.unit : val.toLocaleString('ru-RU');
                svg.appendChild(valText);
            }
        }
    }

    function setActiveMetric(metric) {
        currentMetric = metric;
        switchBtns.forEach(function (btn) {
            var isActive = btn.getAttribute('data-chart-metric') === metric;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        renderChart(metric);
        if (chartMetricInput) chartMetricInput.value = metric;
    }

    switchBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setActiveMetric(this.getAttribute('data-chart-metric'));
        });
    });

    setActiveMetric(currentMetric);
})();
</script>
